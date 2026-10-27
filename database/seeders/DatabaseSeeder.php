<?php

namespace Database\Seeders;

use App\Models\Gestor;
use App\Models\User;
use App\Services\Parametros;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['admin', 'mesa_entrada', 'gestor'] as $r) {
            Role::findOrCreate($r, 'web');
        }
        foreach (['pesos' => ['Interno' => 1, 'Cooperante' => 1.8, 'Externo' => 3.5], 'factores' => ['Presencial' => 1, 'Virtual Sincrónico' => 1, 'Mixto' => 1.2, 'Virtual Asincrónico' => 0.2], 'plazos' => ['Interno' => [5, 8], 'Cooperante' => [10, 15], 'Externo' => [20, 30]], 'alpha' => 1, 'beta' => 0.25, 'theta' => 0.25, 'semilla' => 2026, 'umbral' => 1.25, 'w1' => 10, 'w2' => 1, 'tiempo_limite' => 15] as $k => $v) {
            if (! DB::table('parametros')->where('clave', $k)->exists()) {
                Parametros::guardar($k, $v);
            }
        }
        if (! User::where('email', 'admin@admin.com')->exists()) {
            $password = env('DEMO_PASSWORD', '123');
            $u = User::create(['name' => config('academico.estudiante'), 'email' => 'admin@admin.com', 'password' => Hash::make($password)]);
            $u->assignRole('admin');
            $this->command?->warn('Acceso académico: admin@admin.com / '.$password);
        }
        foreach (range(1, 5) as $n) {
            Gestor::firstOrCreate(['nombre' => 'Gestor '.$n]);
        }
        // Lista inicial nacional de referencia. Las fechas locales y asuetos trasladados se revisan en Parámetros.
        foreach (['2026-01-01' => 'Año nuevo', '2026-04-02' => 'Jueves Santo', '2026-04-03' => 'Viernes Santo', '2026-04-04' => 'Sábado Santo', '2026-05-01' => 'Día del trabajo', '2026-06-30' => 'Día del ejército (revisar traslado)', '2026-09-15' => 'Independencia', '2026-10-20' => 'Revolución', '2026-11-01' => 'Todos los Santos', '2026-12-25' => 'Navidad'] as $d => $v) {
            DB::table('feriados')->updateOrInsert(['fecha' => $d], ['descripcion' => $v]);
        }
    }
}
