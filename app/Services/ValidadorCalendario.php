<?php

namespace App\Services;

class ValidadorCalendario
{
    public function validar(array $input, array $result): array
    {
        $errors = [];
        $slots = [];
        $seen = [];
        $regular = 0;
        $ob = 0;
        $required = 0;
        $regs = 0;
        $sessions = array_column($input['sesiones'], null, 'id');
        $gestores = array_column($input['gestores'], null, 'id');
        foreach ($sessions as $s) {
            if ($s['critica']) {
                $required++;
            } else {
                $regs++;
            }
        }
        foreach ($result['supervisiones'] ?? [] as $v) {
            $s = $sessions[$v['sesion_id']] ?? null;
            $g = $gestores[$v['gestor_id']] ?? null;
            if (! $s || ! $g) {
                $errors[] = 'Sesión o gestor ajeno al horizonte.';

                continue;
            }
            $key = $v['gestor_id'].'|'.$v['fecha'];
            if (isset($slots[$key])) {
                $errors[] = 'Colisión: '.$key;
            }$slots[$key] = true;
            if (isset($seen[$s['id']])) {
                $errors[] = 'Sesión duplicada.';
            }$seen[$s['id']] = true;
            if ($v['fecha'] !== $s['fecha']) {
                $errors[] = 'Fecha de sesión alterada.';
            }
            foreach ($g['ausencias'] as $a) {
                if ($s['fecha'] >= $a['desde'] && $s['fecha'] <= $a['hasta']) {
                    $errors[] = 'Supervisión durante ausencia.';
                }
            }
            if ($s['critica']) {
                $ob++;
            } else {
                $regular++;
            }
        }
        if ($ob !== $required) {
            $errors[] = "Cobertura obligatoria $ob/$required.";
        }
        $minimum = (int) ceil($input['parametros']['theta'] * $regs);
        if ($regular < $minimum) {
            $errors[] = "Muestreo $regular/$minimum.";
        }
        $tickets = array_column($input['tickets'], null, 'id');
        $assignments = $result['asignaciones'] ?? [];
        foreach ($tickets as $id => $t) {
            $gid = $assignments[$id] ?? null;
            if (! $gid || ! isset($gestores[$gid])) {
                $errors[] = "Ticket $id sin gestor activo.";

                continue;
            }
            foreach ($gestores[$gid]['ausencias'] as $a) {
                if ($t['desde'] <= $a['hasta'] && $t['hasta'] >= $a['desde']) {
                    $errors[] = "Ticket $id asignado durante ausencia.";
                }
            }
        }
        foreach ($assignments as $id => $gid) {
            if (! isset($tickets[$id])) {
                $errors[] = 'Reasignación de ticket fuera del alcance.';
            }
        }

        return ['valido' => ! $errors, 'errores' => array_values(array_unique($errors)), 'obligatorias' => $ob, 'obligatorias_total' => $required, 'regulares' => $regular, 'muestreo_minimo' => $minimum, 'colisiones' => count($result['supervisiones'] ?? []) - count($slots)];
    }
}
