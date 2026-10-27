<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class Parametros
{
    public static function todos(): array
    {
        return DB::table('parametros')->pluck('valor', 'clave')->map(fn ($v) => json_decode($v, true))->all();
    }

    private static function numeros(mixed $v): mixed
    {
        if (is_array($v)) {
            return array_map([self::class, 'numeros'], $v);
        }

return is_numeric($v) ? 0 + $v : $v;
    }

    public static function guardar(string $k, mixed $v): void
    {
        DB::table('parametros')->updateOrInsert(['clave' => $k], ['valor' => json_encode(self::numeros($v))]);
    }
}
