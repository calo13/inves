<?php

use App\Models\Corrida;
use App\Services\ImportadorExcel;
use App\Services\OptimizadorService;
use App\Services\ValidadorCalendario;
use Illuminate\Support\Facades\Artisan;

Artisan::command('io:importar {archivo}', function () {
    $r = app(ImportadorExcel::class)->importar($this->argument('archivo'));
    $this->info(json_encode($r, JSON_UNESCAPED_UNICODE));
});
Artisan::command('io:optimizar {--fecha=} {--aplicar} {--solo-planificables} {--solo-planificables}', function () {
    $r = app(OptimizadorService::class)->simular($this->option('fecha') ?: now()->toDateString(), (bool) $this->option('solo-planificables'));
    if ($this->option('aplicar')) {
        app(OptimizadorService::class)->aplicar($r);
    }$this->info(json_encode(['id' => $r->id, 'estado' => $r->estado, 'resultado' => $r->resultado], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
});
Artisan::command('io:validar {corrida}', function () {
    $r = Corrida::findOrFail($this->argument('corrida'));
    $v = app(ValidadorCalendario::class)->validar($r->parametros, $r->resultado);
    $this->info(json_encode($v, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

    return $v['valido'] ? 0 : 1;
});
Artisan::command('io:demo', function () {
    $this->call('db:seed', ['--force' => true]);
    $this->call('io:importar', ['archivo' => base_path('database/fixtures/Control_Prueba_IO.xlsx')]);
});
