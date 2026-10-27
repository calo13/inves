<?php

namespace App\Services;

use App\Models\Gestor;
use App\Models\Sesion;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class ImportadorExcel
{
    private string $hash;

    private function incidencia(string $hoja, int $fila, ?string $codigo, string $detalle, array $original = []): void
    {
        DB::table('incidencias_importacion')->updateOrInsert(['clave' => hash('sha256', "$this->hash|$hoja|$fila|$detalle")], ['hoja' => $hoja, 'fila' => $fila, 'codigo' => $codigo, 'detalle' => $detalle, 'original' => json_encode($original, JSON_UNESCAPED_UNICODE), 'resuelta' => false, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function fecha($v, string $hoja, int $fila, ?string $codigo): ?string
    {
        if ($v === null || $v === '') {
            return null;
        }
        if (! is_numeric($v)) {
            $this->incidencia($hoja, $fila, $codigo, 'Fecha de texto pendiente de confirmación; no se interpreta automáticamente.', ['valor' => $v]);

            return null;
        }
        try {
            $d = Date::excelToDateTimeObject((float) $v);
            if ((int) $d->format('Y') < 2025 || (int) $d->format('Y') > 2030) {
                throw new \RuntimeException;
            }

return $d->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            $this->incidencia($hoja, $fila, $codigo, 'Fecha fuera del rango de datos 2025–2030.', ['valor' => $v]);

            return null;
        }
    }

    public function previsualizar(string $path): array
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $reader->setReadEmptyCells(false);
        $reader->setLoadSheetsOnly(['Registro', 'Sesiones', 'EjecucionXGrupos']);
        $w = $reader->load($path);
        foreach (['Registro', 'Sesiones', 'EjecucionXGrupos'] as $s) {
            if (! $w->getSheetByName($s)) {
                throw new \RuntimeException("Falta la hoja $s.");
            }
        }
        if ($w->getSheetByName('Registro')->getCell('B7')->getValue() !== 'Ticket #') {
            throw new \RuntimeException('La cabecera de Registro debe comenzar en B7.');
        }
        $rows = $w->getSheetByName('Registro')->rangeToArray('B8:AD'.$w->getSheetByName('Registro')->getHighestDataRow(), null, false, false);
        $rows = array_values(array_filter($rows, fn ($r) => ! empty($r[0])));
        $w->disconnectWorksheets();
        unset($w);

        return ['registros' => count($rows), 'muestra' => array_slice(array_map(fn ($r) => ['codigo' => $r[0], 'estado' => $r[2], 'evento' => $r[6]], $rows), 0, 5), 'hash' => hash_file('sha256', $path)];
    }

    public function importar(string $path): array
    {
        $preview = $this->previsualizar($path);
        $this->hash = $preview['hash'];
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $reader->setReadEmptyCells(false);
        $reader->setLoadSheetsOnly(['Registro', 'Sesiones', 'EjecucionXGrupos', 'Docentes', 'DB_Docentes']);
        $w = $reader->load($path);

        return DB::transaction(function () use ($w) {
            $seen = [];
            $n = 0;
            $s = $w->getSheetByName('Registro');
            for ($r = 8; $r <= $s->getHighestDataRow(); $r++) {
                $v = $s->rangeToArray("A$r:AD$r", null, false, false)[0];
                $code = trim((string) $v[1]);
                if (! $code) {
                    continue;
                }
                if (isset($seen[$code])) {
                    $this->incidencia('Registro', $r, $code, 'Ticket duplicado: segunda fila conservada en incidencias; no se sobrescribe el primero.', $v);

                    continue;
                }$seen[$code] = true;
                $gestor = Gestor::firstOrCreate(['nombre' => trim((string) $v[12]) ?: 'Sin asignar']);
                $ing = $this->fecha($v[9], 'Registro', $r, $code);
                $as = $this->fecha($v[11], 'Registro', $r, $code);
                $ini = $this->fecha($v[17], 'Registro', $r, $code);
                $fin = $this->fecha($v[18], 'Registro', $r, $code);
                if (! $ini || ! $fin) {
                    $this->incidencia('Registro', $r, $code, 'Inicio o fin ausente: revisar antes de planificar.');
                }
                if ($as && $ing && $as < $ing) {
                    $this->incidencia('Registro', $r, $code, 'Asignación anterior al ingreso.');
                }
                if ($as && $ini && $as > $ini) {
                    $this->incidencia('Registro', $r, $code, 'Asignación posterior al inicio: verificar fecha.');
                }
                if ($ini && $fin && $fin < $ini) {
                    $this->incidencia('Registro', $r, $code, 'Fin anterior al inicio: fin pendiente de corrección.', ['fin' => $fin]);
                    $fin = null;
                }
                $mode = trim((string) $v[5]);
                $type = ucfirst(mb_strtolower(trim((string) $v[28])));
                $estado = ucfirst(mb_strtolower(trim((string) $v[3])));
                $priority = ucfirst(mb_strtolower(trim((string) $v[4])));
                if (! in_array($mode, ['Presencial', 'Virtual Sincrónico', 'Virtual Asincrónico', 'Mixto']) || ! in_array($type, ['Interno', 'Externo', 'Cooperante']) || ! in_array($estado, ['Abierto', 'Resuelto', 'Cancelado']) || ! in_array($priority, ['Urgente', 'Alta', 'Media', 'Baja'])) {
                    $this->incidencia('Registro', $r, $code, 'Categoría desconocida: registro en cuarentena.', $v);

                    continue;
                }
                $obs = (string) $v[29];
                // Reimportar conserva las decisiones del sistema; solo crea tickets que aún no existen.
                Ticket::firstOrCreate(['codigo' => $code], ['estado' => $estado, 'prioridad' => $priority, 'modalidad' => $mode, 'ruta' => $mode === 'Virtual Asincrónico' ? 'B' : 'A', 'evento_formativo' => (string) $v[7], 'dependencia' => $v[10], 'fecha_ingreso' => $ing, 'fecha_asignacion' => $as, 'gestor_id' => $gestor->id, 'tipo_docente' => $type, 'codigo_insumo' => $v[13], 'requisicion' => $v[15], 'costo_docente' => is_numeric($v[16]) ? $v[16] : null, 'fecha_inicio' => $ini, 'fecha_fin' => $fin, 'grupos_programados' => max(1, (int) $v[20]), 'sesiones_por_grupo' => max(1, (int) $v[21]), 'docente_nuevo' => str_contains($obs, 'OBLIGATORIO - Docente Nuevo'), 'primera_vez' => str_contains($obs, 'OBLIGATORIO - Primera Vez'), 'tema_nuevo' => str_contains($obs, 'OBLIGATORIO - Tema Nuevo'), 'observaciones' => $obs]);
                $n++;
            }
            $s = $w->getSheetByName('EjecucionXGrupos');
            for ($r = 5; $r <= $s->getHighestDataRow(); $r++) {
                $v = $s->rangeToArray("A$r:T$r", null, false, false)[0];
                if (! $v[1]) {
                    continue;
                }$ticket = Ticket::where('codigo', trim($v[1]))->first();
                if (! $ticket) {
                    $this->incidencia('EjecucionXGrupos', $r, $v[1], 'Grupo sin ticket válido.');

                    continue;
                }
                $state = mb_strtolower(trim((string) $v[11]));
                $states = ['finalizado' => 'Finalizado', 'en proceso' => 'En Proceso', 'no iniciado' => 'No Iniciado', 'suspedido temporalmente' => 'Suspendido temporalmente'];
                DB::table('grupos')->updateOrInsert(['fila_origen' => $r], ['ticket_id' => $ticket->id, 'numero' => (string) $v[5], 'pc' => $v[6], 'nombramiento' => $v[7], 'fecha_inicio' => $this->fecha($v[8], 'EjecucionXGrupos', $r, $v[1]), 'fecha_fin' => $this->fecha($v[9], 'EjecucionXGrupos', $r, $v[1]), 'estado' => $states[$state] ?? trim((string) $v[11]), 'solicitados' => max(0, (int) $v[12]), 'nombrados' => max(0, (int) $v[13]), 'aprobados' => max(0, (int) $v[14]), 'reprobados' => max(0, (int) $v[15]), 'inactivos' => max(0, (int) $v[16])]);
                if (($states[$state] ?? null) !== trim((string) $v[11])) {
                    $this->incidencia('EjecucionXGrupos', $r, $v[1], 'Estado normalizado.', ['original' => $v[11], 'normalizado' => $states[$state] ?? $state]);
                }
            }
            $s = $w->getSheetByName('Sesiones');
            for ($r = 5; $r <= $s->getHighestDataRow(); $r++) {
                $v = $s->rangeToArray("A$r:K$r", null, false, false)[0];
                if (! $v[1]) {
                    continue;
                }$ticket = Ticket::where('codigo', trim($v[1]))->first();
                if (! $ticket) {
                    $this->incidencia('Sesiones', $r, $v[1], 'Sesión sin ticket válido.');

                    continue;
                }
                $date = $this->fecha($v[7], 'Sesiones', $r, $v[1]);
                if (! $date && $ticket->ruta === 'A') {
                    $this->incidencia('Sesiones', $r, $v[1], 'Sesión de Ruta A sin fecha válida.');
                }
                $obs = mb_strtoupper((string) $v[10]);
                $critical = str_contains($obs, 'OBLIGATORIO');
                $group = DB::table('grupos')->where('ticket_id', $ticket->id)->where('pc', (string) $v[4])->value('id');
                if (! $group) {
                    $matches = DB::table('grupos')->where('ticket_id', $ticket->id)->where('numero', (string) $v[5])->get();
                    $group = $matches->count() === 1 ? $matches->first()->id : null;
                }
                if (! $group) {
                    $this->incidencia('Sesiones', $r, $v[1], 'No se encontró grupo para la sesión.');
                }
                Sesion::firstOrCreate(['fila_origen' => $r], ['ticket_id' => $ticket->id, 'grupo_id' => $group, 'numero' => (string) $v[6], 'fecha' => $date, 'hora_inicio' => is_numeric($v[8]) ? Date::excelToDateTimeObject($v[8])->format('H:i') : $v[8], 'hora_fin' => is_numeric($v[9]) ? Date::excelToDateTimeObject($v[9])->format('H:i') : $v[9], 'es_critica' => $critical, 'observaciones' => $v[10]]);
            }
            if ($s = $w->getSheetByName('DB_Docentes')) {
                for ($r = 2; $r <= $s->getHighestDataRow(); $r++) {
                    $v = $s->rangeToArray("A$r:F$r", null, false, false)[0];
                    if (! $v[1]) {
                        continue;
                    }$tipo = ucfirst(mb_strtolower(trim((string) $v[3])));
                    if ($tipo !== $v[3]) {
                        $this->incidencia('DB_Docentes', $r, null, 'Tipo docente normalizado.', ['original' => $v[3], 'normalizado' => $tipo]);
                    }
                    DB::table('docentes')->updateOrInsert(['nit' => trim((string) $v[1])], ['documento' => $v[0], 'nombre' => $v[2], 'tipo' => $tipo, 'correo' => $v[4], 'telefono' => (string) $v[5]]);
                }
            }
            if ($s = $w->getSheetByName('Docentes')) {
                $heads = [];
                foreach ($s->rangeToArray('A4:N4', null, false, false)[0] as $i => $h) {
                    $heads[$i] = mb_strtolower((string) $h);
                }
                foreach ($heads as $i => $h) {
                    if (str_contains($h, 'nit')) {
                        $nitcol = $i;
                    }if (str_contains($h, 'grupo')) {
                        $grpcol = $i;
                    }if (str_contains($h, 'solicitud')) {
                        $ticketcol = $i;
                    }if (str_contains($h, 'horas')) {
                        $hourscol = $i;
                    }if ($h === 'pc') {
                        $pccol = $i;
                    }
                }
                if (isset($nitcol,$grpcol,$ticketcol)) {
                    for ($r = 5; $r <= $s->getHighestDataRow(); $r++) {
                        $v = $s->rangeToArray("A$r:N$r", null, false, false)[0];
                        if (empty($v[$ticketcol])) {
                            continue;
                        }$tid = Ticket::where('codigo', $v[$ticketcol])->value('id');
                        $gid = isset($pccol) ? DB::table('grupos')->where('ticket_id', $tid)->where('pc', (string) $v[$pccol])->value('id') : null;
                        $did = DB::table('docentes')->where(fn ($q) => $q->where('nit', (string) $v[$nitcol])->orWhere('documento', (string) $v[$nitcol]))->value('id');
                        if ($gid && $did) {
                            DB::table('docente_grupo')->updateOrInsert(['grupo_id' => $gid, 'docente_id' => $did],['horas' => isset($hourscol) && is_numeric($v[$hourscol]) ? $v[$hourscol] : null]);
                        } else {
                            $this->incidencia('Docentes',$r,$v[$ticketcol],'Vínculo docente–grupo pendiente: falta catálogo o grupo.');
                        }
                    }
                }
            }
            $w->disconnectWorksheets();

            return ['leidos' => $n, 'tickets' => Ticket::count(), 'sesiones' => Sesion::count(), 'incidencias' => DB::table('incidencias_importacion')->count()];
        });
    }
}
