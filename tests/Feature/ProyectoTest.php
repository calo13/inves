<?php

use App\Livewire\Panel;
use App\Models\Corrida;
use App\Models\Gestor;
use App\Models\Sesion;
use App\Models\Ticket;
use App\Models\User;
use App\Services\CargaService;
use App\Services\ImportadorExcel;
use App\Services\OptimizadorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

uses(RefreshDatabase::class);
beforeEach(function () {
    $this->seed();
    $this->admin = User::where('email', 'admin@admin.com')->first();
});
function ticketIO(array $over = []): Ticket
{
    return Ticket::create(array_merge(['codigo' => uniqid('TEST-'), 'estado' => 'Abierto', 'prioridad' => 'Alta', 'modalidad' => 'Presencial', 'ruta' => 'A', 'evento_formativo' => 'Formación de prueba', 'fecha_ingreso' => '2026-10-01', 'fecha_inicio' => '2026-11-02', 'fecha_fin' => '2026-11-06', 'tipo_docente' => 'Interno', 'grupos_programados' => 1, 'sesiones_por_grupo' => 2, 'gestor_id' => Gestor::first()->id], $over));
}
function sesionIO(Ticket $t, string $date, bool $crit = true): Sesion
{
    return Sesion::create(['ticket_id' => $t->id, 'numero' => '1', 'fecha' => $date, 'es_critica' => $crit, 'fila_origen' => (Sesion::max('fila_origen') ?? 1000) + 1]);
}
it('calcula días hábiles y pesos sin duplicar grupos', function () {
    $c = app(CargaService::class);
    $t = ticketIO(['grupos_programados' => 2, 'sesiones_por_grupo' => 3]);
    expect($c->peso($t))->toBe(2.5);
    expect($c->habiles('2026-10-16', '2026-10-21'))->toBe(2);
    $t->tipo_docente = 'Externo';
    expect($c->peso($t))->toBe(5.0);
});
it('cubre obligatorias con apoyo y deja fuera Ruta B', function () {
    $t = ticketIO();
    sesionIO($t, '2026-11-02');
    sesionIO($t, '2026-11-02');
    $b = ticketIO(['modalidad' => 'Virtual Asincrónico', 'ruta' => 'B']);
    sesionIO($b, '2026-11-02');
    $s = app(OptimizadorService::class);
    $r = $s->simular('2026-10-26');
    expect($r->estado)->toBe('Validada');
    expect($r->resultado['validacion']['obligatorias'])->toBe(2);
    expect($r->resultado['validacion']['colisiones'])->toBe(0);
    $s->aplicar($r);
    expect(DB::table('supervisiones')->count())->toBe(2);
});
it('redistribuye con cuatro gestores y conserva cerrados', function () {
    $closed = ticketIO(['estado' => 'Resuelto']);
    $old = $closed->gestor_id;
    Gestor::first()->update(['activo' => false]);
    foreach (range(1, 4) as $n) {
        $t = ticketIO();
        sesionIO($t, '2026-11-02');
    }$r = app(OptimizadorService::class)->simular('2026-10-26');
    expect($r->estado)->toBe('Validada');
    expect(count($r->parametros['gestores']))->toBe(4);
    app(OptimizadorService::class)->aplicar($r);
    expect($closed->fresh()->gestor_id)->toBe($old);
    expect(Ticket::where('estado', 'Abierto')->where('gestor_id', $old)->count())->toBe(0);
});
it('rechaza capacidad insuficiente sin inventar fechas', function () {
    $t = ticketIO();
    foreach (range(1, 6) as $n) {
        sesionIO($t, '2026-11-02');
    }$r = app(OptimizadorService::class)->simular('2026-10-26');
    expect($r->estado)->toBe('No aplicable');
    expect($r->resultado['validacion']['obligatorias'])->toBe(5);
    expect(fn () => app(OptimizadorService::class)->aplicar($r))->toThrow(RuntimeException::class);
});
it('respeta ausencias y la semilla del muestreo', function () {
    $g = Gestor::first();
    DB::table('ausencias_gestor')->insert(['gestor_id' => $g->id, 'desde' => '2026-11-01', 'hasta' => '2026-11-30', 'motivo' => 'Vacaciones']);
    $t = ticketIO();
    foreach (range(2, 9) as $n) {
        sesionIO($t, '2026-11-'.str_pad($n, 2, '0', STR_PAD_LEFT), false);
    }$s = app(OptimizadorService::class);
    $a = $s->simular('2026-10-26');
    $b = $s->simular('2026-10-26');
    expect($a->estado)->toBe('Validada');
    expect($a->resultado['supervisiones'])->toBe($b->resultado['supervisiones']);
    expect($a->resultado['validacion']['regulares'])->toBe(2);
    expect(collect($a->resultado['supervisiones'])->pluck('gestor_id')->contains($g->id))->toBeFalse();
});
it('no aplica vistas previas desactualizadas', function () {
    $t = ticketIO();
    sesionIO($t, '2026-11-02');
    $r = app(OptimizadorService::class)->simular('2026-10-26');
    $t->update(['tipo_docente' => 'Externo']);
    expect(fn () => app(OptimizadorService::class)->aplicar($r))->toThrow(RuntimeException::class, 'Los datos cambiaron');
});
it('importa el archivo real sin duplicar y conserva cambios', function () {
    $s = app(ImportadorExcel::class);
    $path = base_path('database/fixtures/Control_Prueba_IO.xlsx');
    $a = $s->importar($path);
    expect($a['tickets'])->toBe(112);
    expect($a['sesiones'])->toBe(389);
    expect(DB::table('grupos')->count())->toBe(334);
    expect(DB::table('docente_grupo')->count())->toBeGreaterThan(250);
    expect(Ticket::where('estado', 'Abierto')->count())->toBe(44);
    $t = Ticket::where('tipo_docente', 'Interno')->first();
    $t->update(['tipo_docente' => 'Externo']);
    $s->importar($path);
    expect(Ticket::count())->toBe(112);
    expect($t->fresh()->tipo_docente)->toBe('Externo');
    expect(DB::table('incidencias_importacion')->where('detalle', 'like', 'Ticket duplicado%')->count())->toBe(1);
});
it('muestra todos los módulos al administrador', function () {
    foreach (['/', '/tickets', '/recursos', '/simulador', '/calendario', '/importacion', '/calidad', '/parametros', '/pruebas'] as $path) {
        $this->actingAs($this->admin)->get($path)->assertOk();
    }
});
it('aplica filtros Livewire y limita al gestor a sus propios tickets', function () {
    $u = User::factory()->create();
    $u->assignRole('gestor');
    $g = Gestor::first();
    $g->update(['user_id' => $u->id]);
    $own = ticketIO(['codigo' => 'PROPIO']);
    $other = ticketIO(['codigo' => 'AJENO', 'gestor_id' => Gestor::skip(1)->first()->id]);
    Livewire::actingAs($u)->test(Panel::class)->assertSee('Panel ejecutivo')->set('busqueda', 'AJENO')->assertSee('No hay próximos inicios');
    $this->actingAs($u)->get('/recursos')->assertForbidden();
    Livewire::actingAs($u)->test(Panel::class)->call('simular')->assertForbidden();
    $this->actingAs($u)->get('/exportar/pdf')->assertOk();
});
it('simula y aplica cambio de docente con historial', function () {
    $t = ticketIO();
    sesionIO($t, '2026-11-02');
    Livewire::actingAs($this->admin)->test(Panel::class, ['modulo' => 'simulador'])->set('fecha', '2026-10-26')->set('ticketCambio', (string) $t->id)->set('tipoNuevo', 'Externo')->call('simularCambio')->assertSet('simulacion.despues.semaforo.estado', 'Rojo');
    expect($t->fresh()->tipo_docente)->toBe('Interno');
    Livewire::actingAs($this->admin)->test(Panel::class, ['modulo' => 'simulador'])->set('ticketCambio', (string) $t->id)->call('aplicarCambio')->assertHasNoErrors();
    expect($t->fresh()->tipo_docente)->toBe('Externo');
    expect(DB::table('historial_tipo_docente')->count())->toBe(1);
});
it('ejecuta las tres pruebas con restauración de datos', function () {
    foreach (range(1, 4) as $n) {
        $t = ticketIO();
        sesionIO($t, '2026-11-02');
    }foreach ([1, 2, 3] as $n) {
        Livewire::actingAs($this->admin)->test(Panel::class, ['modulo' => 'pruebas'])->set('fecha', '2026-10-26')->call('prueba', $n)->assertHasNoErrors();
    }expect(Corrida::count())->toBe(3);
    expect(Gestor::where('activo', true)->count())->toBe(5);
    expect(Ticket::where('tipo_docente', 'Externo')->count())->toBe(0);
});
it('exporta un Excel válido con filtros', function () {
    ticketIO(['codigo' => 'FILTRADO']);
    ticketIO(['prioridad' => 'Baja']);
    $r = $this->actingAs($this->admin)->get('/exportar/xlsx?prioridad=Alta')->assertOk();
    expect(substr($r->streamedContent(), 0, 2))->toBe('PK');
});

it('expone las exclusiones de datos incompletos sin afirmar cobertura total', function () {
    $bad = ticketIO(['fecha_inicio' => null, 'fecha_fin' => null]);
    $good = ticketIO();
    sesionIO($good, '2026-11-02');
    $s = app(OptimizadorService::class);
    $strict = $s->simular('2026-10-26');
    expect($strict->estado)->toBe('No aplicable');
    $partial = $s->simular('2026-10-26', true);
    expect($partial->estado)->toBe('Validada');
    expect(count($partial->parametros['excluidos']))->toBe(1);
    $before = $bad->gestor_id;
    $s->aplicar($partial);
    expect($bad->fresh()->gestor_id)->toBe($before);
    expect($partial->fresh()->resultado)->toHaveKey('huella_aplicada');
});
it('edita una fecha de sesión confirmada y rechaza propuestas antiguas', function () {
    $t = ticketIO();
    $s = sesionIO($t, '2026-11-02');
    $r = app(OptimizadorService::class)->simular('2026-10-26');
    Livewire::actingAs($this->admin)->test(Panel::class, ['modulo' => 'tickets'])->call('editar', $t->id)->set('sesionesExistentes.0.fecha', '2026-11-03')->call('guardarTicket')->assertHasNoErrors();
    expect($s->fresh()->fecha->toDateString())->toBe('2026-11-03');
    expect(fn () => app(OptimizadorService::class)->aplicar($r))->toThrow(RuntimeException::class);
});
it('el gestor ve las sesiones de apoyo que le corresponden', function () {
    $u = User::factory()->create();
    $u->assignRole('gestor');
    $g = Gestor::first();
    $g->update(['user_id' => $u->id]);
    $other = Gestor::skip(1)->first();
    $t = ticketIO(['gestor_id' => $other->id, 'codigo' => 'APOYO-VISIBLE']);
    $s = sesionIO($t, '2026-11-02');
    $in = app(OptimizadorService::class)->entrada('2026-10-26');
    Corrida::create(['estado' => 'Validada', 'motor' => 'Prueba', 'parametros' => $in, 'antes' => [], 'resultado' => ['supervisiones' => [['sesion_id' => $s->id, 'gestor_id' => $g->id, 'fecha' => '2026-11-02', 'tipo' => 'apoyo']], 'huella' => 'test']]);
    Livewire::actingAs($u)->test(Panel::class,['modulo' => 'calendario'])->assertSee('APOYO-VISIBLE');
});

it('permite el acceso académico solicitado y muestra la identidad del proyecto', function () {
    $this->get('/ingresar')->assertOk()->assertSee('Carlos Augusto Lopez Urizar')->assertSee('6.º semestre');
    $this->post('/ingresar', ['email'=>'admin@admin.com','password'=>'123'])->assertRedirect('/');
    $this->assertAuthenticatedAs($this->admin);
    $this->get('/')->assertOk()->assertSee('Universidad Mariano Gálvez de Guatemala')->assertSee('Carlos Augusto Lopez Urizar');
});
