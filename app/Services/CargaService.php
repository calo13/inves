<?php

namespace App\Services;

use App\Models\Ticket;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class CargaService
{
    public function peso(Ticket $t, ?array $p = null): float
    {
        $p ??= Parametros::todos();

        return round($p['alpha'] * $p['pesos'][$t->tipo_docente] + $p['beta'] * $p['factores'][$t->modalidad] * $t->grupos_programados * $t->sesiones_por_grupo, 2);
    }

    public function habiles(string $desde, string $hasta): int
    {
        $a = CarbonImmutable::parse($desde)->startOfDay();
        $b = CarbonImmutable::parse($hasta)->startOfDay();
        if ($b < $a) {
            return -$this->habiles($hasta, $desde);
        }
        $feriados = DB::table('feriados')->pluck('fecha')->all();
        $n = 0;
        for ($d = $a->addDay(); $d <= $b; $d = $d->addDay()) {
            if (! $d->isWeekend() && ! in_array($d->toDateString(), $feriados)) {
                $n++;
            }
        }

        return $n;
    }

    public function semaforo(Ticket $t, string $fecha, ?array $p = null): array
    {
        $p ??= Parametros::todos();
        if (! $t->fecha_inicio) {
            return ['estado' => 'Sin fecha', 'dias' => null];
        }$n = $this->habiles($fecha, $t->fecha_inicio->toDateString());
        [$min,$max] = $p['plazos'][$t->tipo_docente];

        return ['estado' => $n < $min ? 'Rojo' : ($n < $max ? 'Amarillo' : 'Verde'), 'dias' => $n];
    }
}
