<?php

use App\Models\User;
use App\Support\Recorrido;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
});

it('ofrece el recorrido en cada inicio de sesión pero no lo reinicia al cambiar de página', function () {
    $credenciales = ['email' => 'admin@admin.com', 'password' => '123'];
    $this->post('/ingresar', $credenciales)->assertRedirect('/')->assertSessionHas('recorrido_pendiente', true);
    $this->get('/')->assertOk()->assertSee('Omitir recorrido')->assertSee('Repetir recorrido guiado')->assertSessionMissing('recorrido_pendiente');
    $this->get('/tickets')->assertOk()->assertSessionMissing('recorrido_pendiente');
    $this->post('/salir')->assertRedirect('/ingresar');
    $this->post('/ingresar', $credenciales)->assertRedirect('/')->assertSessionHas('recorrido_pendiente', true);
});

it('no inicia el recorrido cuando las credenciales son incorrectas', function () {
    $this->post('/ingresar', ['email' => 'admin@admin.com', 'password' => 'incorrecta'])
        ->assertSessionHasErrors('email')->assertSessionMissing('recorrido_pendiente');
    $this->assertGuest();
});

it('incluye todos los módulos para el administrador y conserva la distinción entre simular y aplicar', function () {
    $pasos = Recorrido::pasos(User::where('email', 'admin@admin.com')->first());
    expect(array_unique(array_column($pasos, 'modulo')))->toContain('dashboard', 'tickets', 'recursos', 'simulador', 'calendario', 'importacion', 'calidad', 'parametros', 'pruebas');
    expect(json_encode($pasos, JSON_UNESCAPED_UNICODE))->toContain('sin guardar', 'última corrida aplicada', 'máximo una sesión al día');
});

it('limita las pantallas del recorrido según el rol', function (string $rol, array $permitidos) {
    $user = User::factory()->create();
    $user->assignRole($rol);
    $pasos = Recorrido::pasos($user);
    $modulos = array_values(array_unique(array_column($pasos, 'modulo')));
    sort($modulos);
    sort($permitidos);
    expect($modulos)->toBe($permitidos);
    $this->actingAs($user)->get('/')->assertOk()->assertSee('Omitir recorrido')->assertSee('Repetir recorrido guiado');
})->with([
    ['mesa_entrada', ['dashboard', 'tickets', 'importacion', 'calidad', 'simulador', 'calendario']],
    ['gestor', ['dashboard', 'tickets', 'calendario']],
]);
