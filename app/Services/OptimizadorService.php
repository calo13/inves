<?php

namespace App\Services;

use App\Models\Corrida;
use App\Models\Gestor;
use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

class OptimizadorService
{
    public function entrada(string $fecha, bool $soloPlanificables = false): array
    {

        $p = Parametros::todos();
        $carga = app(CargaService::class);

        $gs = Gestor::where('activo', true)->orderBy('id')->get()->map(fn ($g) => ['id' => $g->id, 'nombre' => $g->nombre, 'capacidad' => (float) $g->capacidad_relativa, 'ausencias' => DB::table('ausencias_gestor')->where('gestor_id', $g->id)->get(['desde', 'hasta'])->map(fn ($x) => (array) $x)->all()])->all();

        $ts = Ticket::where('estado', 'Abierto')->orderBy('id')->get();
        $base = array_fill_keys(array_column($gs, 'id'), 0);

        foreach ($ts as $t) {
            if (isset($base[$t->gestor_id])) {
                $base[$t->gestor_id] += $carga->peso($t, $p);
            }
        }

        $tickets = [];
        $errors = [];
        $sessions = [];
        $excluidos = [];
        $fixed = array_fill_keys(array_column($gs, 'id'), 0);

        foreach ($ts as $t) {

            $invalid = ! $t->fecha_inicio || ! $t->fecha_fin || ($t->ruta === 'A' && ($t->sesiones->isEmpty() || $t->sesiones->contains(fn ($s) => ! $s->fecha)));

            if ($invalid && $soloPlanificables) {
                $excluidos[] = ['codigo' => $t->codigo, 'motivo' => 'Fechas o sesiones incompletas; conserva su asignación original.', 'original' => $t->toArray()];
                if (isset($fixed[$t->gestor_id])) {
                    $fixed[$t->gestor_id] += $carga->peso($t, $p);
                }

continue;
            }

            $weight = $carga->peso($t, $p);
            $urg = ['Urgente' => 4, 'Alta' => 3, 'Media' => 2, 'Baja' => 1][$t->prioridad];
            $age = $t->fecha_ingreso ? max(0, (int) $t->fecha_ingreso->diffInDays(Carbon::parse($fecha), false)) : 0;

            $days = $t->fecha_inicio ? $carga->habiles($fecha, $t->fecha_inicio->toDateString()) : 0;
            $slack = max(0, $p['plazos'][$t->tipo_docente][0] - $days);
            $score = $urg * (1 + $slack) + min($age, 365) / 30;

            $costs = [];
            foreach ($gs as $g) {
                $costs[$g['id']] = round($score * max(0, $base[$g['id']] - ($t->gestor_id === $g['id'] ? $weight : 0)) / $g['capacidad'], 2);
            }

            $tickets[] = ['id' => $t->id, 'codigo' => $t->codigo, 'gestor_id' => $t->gestor_id, 'peso' => $weight, 'prioridad' => $score, 'costos' => $costs, 'desde' => $fecha, 'hasta' => max($fecha, $t->fecha_fin?->toDateString() ?? $fecha)];

            if (! $t->fecha_inicio || ! $t->fecha_fin) {
                $errors[] = $t->codigo.': falta confirmar inicio o fin.';
            }

            if ($t->ruta === 'B') {
                continue;
            }if ($t->sesiones->isEmpty()) {
                $errors[] = $t->codigo.': no tiene sesiones registradas.';
            }

            foreach ($t->sesiones as $s) {
                if (! $s->fecha) {
                    $errors[] = $t->codigo.': sesión '.$s->numero.' sin fecha.';

                    continue;
                }if ($s->fecha->toDateString() < $fecha) {
                    continue;
                }

                $sessions[] = ['id' => $s->id, 'ticket_id' => $t->id, 'fecha' => $s->fecha->toDateString(), 'critica' => $s->es_critica || $t->critica()];

            }

        }

        $scale = max(1, array_sum(array_column($tickets, 'peso'))) * max(1, ...array_column($tickets, 'prioridad'));
        foreach ($tickets as &$ticket) {
            foreach ($ticket['costos'] as &$cost) {
                $cost = round($cost / $scale, 4);
            }
        }unset($ticket,$cost);

        return ['fecha' => $fecha, 'solo_planificables' => $soloPlanificables, 'excluidos' => $excluidos, 'cargas_fijas' => $fixed, 'gestores' => $gs, 'tickets' => $tickets, 'sesiones' => $sessions, 'parametros' => $p, 'errores_datos' => array_values(array_unique($errors))];

    }

    public function disponible(array $g, string $desde, string $hasta): bool
    {
        foreach ($g['ausencias'] as $a) {
            if ($desde <= $a['hasta'] && $hasta >= $a['desde']) {
                return false;
            }
        }

return true;
    }

    public function metricas(array $in, array $assign, array $supervisiones = []): array
    {

        $loads = array_fill_keys(array_column($in['gestores'], 'id'), 0);
        $count = $loads;
        foreach ($in['cargas_fijas'] ?? [] as $g => $load) {
            if (isset($loads[$g])) {
                $loads[$g] += $load;
            }
        }$caps = array_column($in['gestores'], 'capacidad', 'id');
        $slots = [];

        foreach ($in['tickets'] as $t) {
            $gid = $assign[$t['id']] ?? $t['gestor_id'];
            if (isset($loads[$gid])) {
                $loads[$gid] += $t['peso'];
                $count[$gid]++;
            }
        }

        foreach ($in['sesiones'] as $s) {
            $g = $assign[$s['ticket_id']] ?? null;
            if ($g) {
                $slots[$g.'|'.$s['fecha']] = ($slots[$g.'|'.$s['fecha']] ?? 0) + 1;
            }
        }

        $normalized = [];
        foreach ($loads as $g => $l) {
            $normalized[$g] = round($l / $caps[$g], 2);
        }

        return ['cargas' => $loads, 'normalizadas' => $normalized, 'tickets' => $count, 'desviacion' => $normalized ? round(max($normalized) - min($normalized), 2) : 0, 'colisiones_nominales' => count(array_filter($slots, fn ($n) => $n > 1))];

    }

    private function objetivo(array $in, array $assign): float
    {
        $m = $this->metricas($in, $assign);
        $cost = 0;
        foreach ($in['tickets'] as $t) {
            $cost += $t['costos'][$assign[$t['id']]] ?? 0;
        }

return $in['parametros']['w1'] * $m['desviacion'] + $in['parametros']['w2'] * $cost;
    }

    public function heuristica(array $in): array
    {

        $gs = $in['gestores'];
        $loads = $in['cargas_fijas'] ?? array_fill_keys(array_column($gs, 'id'), 0);
        $assign = [];
        $errors = [];
        $tickets = $in['tickets'];

        usort($tickets, fn ($a, $b) => ($b['prioridad'] <=> $a['prioridad']) ?: ($b['peso'] <=> $a['peso']));

        foreach ($tickets as $t) {
            $best = null;
            $bestScore = INF;

            foreach ($gs as $g) {
                if (! $this->disponible($g, $t['desde'], $t['hasta'])) {
                    continue;
                }$score = $in['parametros']['w1'] * ($loads[$g['id']] + $t['peso']) / $g['capacidad'] + $in['parametros']['w2'] * $t['costos'][$g['id']];
                if ($score < $bestScore) {
                    $bestScore = $score;
                    $best = $g['id'];
                }
            }

            if (! $best) {
                $errors[] = $t['codigo'].': sin gestor disponible durante la ventana.';

                continue;
            }$assign[$t['id']] = $best;
            $loads[$best] += $t['peso'];

        }

        // Búsqueda local con movimientos y permutas; cada mejora debe reducir el objetivo completo.

        if (count($assign) === count($tickets)) {
            for ($pass = 0; $pass < 10; $pass++) {

                $changed = false;
                $objective = $this->objetivo($in, $assign);

                foreach ($tickets as $t) {
                    foreach ($gs as $g) {
                        if (! $this->disponible($g, $t['desde'], $t['hasta'])) {
                            continue;
                        }$trial = $assign;
                        $trial[$t['id']] = $g['id'];
                        $v = $this->objetivo($in, $trial);
                        if ($v < $objective - 0.001) {
                            $assign = $trial;
                            $objective = $v;
                            $changed = true;
                        }
                    }
                }

                foreach ($tickets as $a) {
                    foreach ($tickets as $b) {
                        if ($a['id'] >= $b['id']) {
                            continue;
                        }$ga = collect($gs)->firstWhere('id', $assign[$a['id']]);
                        $gb = collect($gs)->firstWhere('id', $assign[$b['id']]);
                        if (! $this->disponible($gb, $a['desde'], $a['hasta']) || ! $this->disponible($ga, $b['desde'], $b['hasta'])) {
                            continue;
                        }$trial = $assign;
                        [$trial[$a['id']],$trial[$b['id']]] = [$assign[$b['id']], $assign[$a['id']]];
                        $v = $this->objetivo($in, $trial);
                        if ($v < $objective - 0.001) {
                            $assign = $trial;
                            $objective = $v;
                            $changed = true;
                        }
                    }
                }

                if (! $changed) {
                    break;
                }

            }
        }

        $slots = [];
        $super = [];
        $regCount = [];
        $critical = array_values(array_filter($in['sesiones'], fn ($s) => $s['critica']));
        $regular = array_values(array_filter($in['sesiones'], fn ($s) => ! $s['critica']));

        $schedule = function ($s) use (&$slots, &$super, &$regCount, $gs, $assign) {
            $preferred = $assign[$s['ticket_id']] ?? null;
            $available = $gs;
            usort($available, fn ($a, $b) => (($a['id'] === $preferred ? 0 : 1) <=> ($b['id'] === $preferred ? 0 : 1)) ?: count(array_filter($super, fn ($x) => $x['gestor_id'] === $a['id'])) <=> count(array_filter($super, fn ($x) => $x['gestor_id'] === $b['id'])));

            foreach ($available as $g) {
                $key = $g['id'].'|'.$s['fecha'];
                if (isset($slots[$key]) || ! $this->disponible($g, $s['fecha'], $s['fecha'])) {
                    continue;
                }$slots[$key] = true;
                $super[] = ['sesion_id' => $s['id'], 'gestor_id' => $g['id'], 'fecha' => $s['fecha'], 'tipo' => $g['id'] !== $preferred ? 'apoyo' : ($s['critica'] ? 'obligatoria' : 'muestreo')];
                if (! $s['critica']) {
                    $regCount[$s['ticket_id']] = ($regCount[$s['ticket_id']] ?? 0) + 1;
                }

return true;
            }

return false;
        };

        foreach ($critical as $s) {
            if (! $schedule($s)) {
                $errors[] = 'Capacidad insuficiente para sesión obligatoria '.$s['id'].' el '.$s['fecha'];
            }
        }

        $needed = (int) ceil($in['parametros']['theta'] * count($regular));
        $seed = $in['parametros']['semilla'];
        $covered = 0;

        while ($regular && $covered < $needed) {
            usort($regular, fn ($a, $b) => (($regCount[$a['ticket_id']] ?? 0) <=> ($regCount[$b['ticket_id']] ?? 0)) ?: strcmp(hash('sha256', $seed.'|'.$a['id']), hash('sha256', $seed.'|'.$b['id'])));
            $s = array_shift($regular);
            if ($schedule($s)) {
                $covered++;
            }
        }

        if ($covered < $needed) {
            $errors[] = "No hay capacidad para el muestreo mínimo ($covered/$needed).";
        }

        return ['asignaciones' => $assign, 'supervisiones' => $super, 'errores' => $errors, 'motor' => 'Heurística PHP'];

    }

    public function simular(string $fecha, bool $soloPlanificables = false): Corrida
    {

        $in = $this->entrada($fecha, $soloPlanificables);
        $beforeAssign = array_column($in['tickets'], 'gestor_id', 'id');
        $start = microtime(true);
        $fallback = null;

        if (config('io.motor') === 'python') {
            try {
                $process = new Process([config('io.python'), base_path('optimizador/optimizar.py')]);
                $payload = $in;
                $payload['incumbente'] = $this->heuristica($in);
                $process->setInput(json_encode($payload));
                $process->setTimeout($in['parametros']['tiempo_limite'] + 10);
                $process->mustRun();
                $result = json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR);
            } catch (\Throwable $e) {
                $fallback = 'Python no disponible: '.$e->getMessage();
                $result = $this->heuristica($in);
            }
        } else {
            $result = $this->heuristica($in);
        }

        $validation = app(ValidadorCalendario::class)->validar($in, $result);
        $result['validacion'] = $validation;
        $result['errores'] = array_values(array_unique(array_merge($result['errores'] ?? [], $in['errores_datos'], $validation['errores'])));
        $result['metricas'] = $this->metricas($in, $result['asignaciones']);
        $result['segundos'] = round(microtime(true) - $start, 3);
        $result['fallback'] = $fallback;
        $result['huella'] = hash('sha256', json_encode($in));

        return Corrida::create(['estado' => $result['errores'] ? 'No aplicable' : 'Validada', 'motor' => $result['motor'], 'parametros' => $in, 'antes' => $this->metricas($in, $beforeAssign), 'resultado' => $result]);

    }

    public function aplicar(Corrida $run): void
    {

        DB::transaction(function () use ($run) {
            $run = Corrida::whereKey($run->id)->lockForUpdate()->firstOrFail();
            if ($run->aplicada_en) {
                throw new \RuntimeException('Esta corrida ya fue aplicada.');
            }if ($run->estado !== 'Validada') {
                throw new \RuntimeException('La corrida tiene incidencias pendientes.');
            }

            // Serializar las decisiones de asignación y rechazar una vista previa desactualizada.

            Ticket::where('estado', 'Abierto')->lockForUpdate()->get();
            Gestor::lockForUpdate()->get();
            DB::table('ausencias_gestor')->lockForUpdate()->get();

            $input = $this->entrada($run->parametros['fecha'], $run->parametros['solo_planificables'] ?? false);
            if (hash('sha256', json_encode($input)) !== $run->resultado['huella']) {
                throw new \RuntimeException('Los datos cambiaron. Genere una nueva simulación.');
            }

            if (! app(ValidadorCalendario::class)->validar($input, $run->resultado)['valido']) {
                throw new \RuntimeException('El validador independiente rechazó la corrida.');
            }

            foreach ($run->resultado['asignaciones'] as $tid => $gid) {
                $t = Ticket::findOrFail($tid);
                if ($t->gestor_id != $gid) {
                    DB::table('historial_asignaciones')->insert(['ticket_id' => $tid, 'gestor_anterior' => $t->gestor_id, 'gestor_nuevo' => $gid, 'motivo' => 'optimización', 'corrida_id' => $run->id, 'created_at' => now(), 'updated_at' => now()]);
                    $t->update(['gestor_id' => $gid]);
                }
            }

            foreach ($run->resultado['supervisiones'] as $s) {
                DB::table('supervisiones')->insert($s + ['corrida_id' => $run->id]);
            }$result = $run->resultado;
            $result['huella_aplicada'] = hash('sha256',json_encode($this->entrada($run->parametros['fecha'],$run->parametros['solo_planificables'] ?? false)));
            $run->update(['aplicada_en' => now(), 'estado' => 'Aplicada', 'resultado' => $result]);

        });

    }
}
