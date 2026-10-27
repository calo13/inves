<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gestores', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('nombre')->unique();
            $t->boolean('activo')->default(true);
            $t->decimal('capacidad_relativa', 8, 2)->default(1);
            $t->timestamps();
        });
        Schema::create('ausencias_gestor', function (Blueprint $t) {
            $t->id();
            $t->foreignId('gestor_id')->constrained('gestores');
            $t->date('desde');
            $t->date('hasta');
            $t->string('motivo');
            $t->timestamps();
        });
        Schema::create('parametros', function (Blueprint $t) {
            $t->string('clave')->primary();
            $t->text('valor');
        });
        Schema::create('feriados', function (Blueprint $t) {
            $t->id();
            $t->date('fecha')->unique();
            $t->string('descripcion');
        });
        Schema::create('tickets', function (Blueprint $t) {
            $t->id();
            $t->string('codigo')->unique();
            $t->string('estado');
            $t->string('prioridad');
            $t->string('modalidad');
            $t->string('ruta', 1);
            $t->text('evento_formativo');
            $t->string('dependencia')->nullable();
            $t->dateTime('fecha_ingreso')->nullable();
            $t->dateTime('fecha_asignacion')->nullable();
            $t->foreignId('gestor_id')->nullable()->constrained('gestores');
            $t->string('tipo_docente');
            $t->string('codigo_insumo')->nullable();
            $t->string('requisicion')->nullable();
            $t->decimal('costo_docente', 14, 2)->nullable();
            $t->date('fecha_inicio')->nullable();
            $t->date('fecha_fin')->nullable();
            $t->unsignedInteger('grupos_programados')->default(1);
            $t->unsignedInteger('sesiones_por_grupo')->default(1);
            $t->boolean('docente_nuevo')->default(false);
            $t->boolean('primera_vez')->default(false);
            $t->boolean('tema_nuevo')->default(false);
            $t->text('observaciones')->nullable();
            $t->timestamps();
        });
        Schema::create('grupos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('ticket_id')->constrained();
            $t->string('numero');
            $t->string('pc')->nullable();
            $t->string('nombramiento')->nullable();
            $t->date('fecha_inicio')->nullable();
            $t->date('fecha_fin')->nullable();
            $t->string('estado')->nullable();
            $t->unsignedInteger('solicitados')->default(0);
            $t->unsignedInteger('nombrados')->default(0);
            $t->unsignedInteger('aprobados')->default(0);
            $t->unsignedInteger('reprobados')->default(0);
            $t->unsignedInteger('inactivos')->default(0);
            $t->unsignedInteger('fila_origen')->nullable()->unique();
        });
        Schema::create('sesiones', function (Blueprint $t) {
            $t->id();
            $t->foreignId('ticket_id')->constrained();
            $t->foreignId('grupo_id')->nullable()->constrained('grupos');
            $t->string('numero');
            $t->date('fecha')->nullable();
            $t->string('hora_inicio')->nullable();
            $t->string('hora_fin')->nullable();
            $t->boolean('es_critica')->default(false);
            $t->text('observaciones')->nullable();
            $t->unsignedInteger('fila_origen')->unique();
        });
        Schema::create('docentes', function (Blueprint $t) {
            $t->id();
            $t->string('documento')->nullable();
            $t->string('nit')->unique();
            $t->string('nombre');
            $t->string('tipo');
            $t->string('correo')->nullable();
            $t->string('telefono')->nullable();
        });
        Schema::create('docente_grupo', function (Blueprint $t) {
            $t->id();
            $t->foreignId('docente_id')->constrained();
            $t->foreignId('grupo_id')->constrained('grupos');
            $t->decimal('horas', 8, 2)->nullable();
            $t->unique(['docente_id', 'grupo_id']);
        });
        Schema::create('corridas_optimizacion', function (Blueprint $t) {
            $t->id();
            $t->string('estado');
            $t->string('motor');
            $t->json('parametros');
            $t->json('resultado');
            $t->json('antes');
            $t->timestamp('aplicada_en')->nullable();
            $t->timestamps();
        });
        Schema::create('supervisiones', function (Blueprint $t) {
            $t->id();
            $t->foreignId('corrida_id')->constrained('corridas_optimizacion');
            $t->foreignId('sesion_id')->constrained('sesiones');
            $t->foreignId('gestor_id')->constrained('gestores');
            $t->date('fecha');
            $t->string('tipo');
            $t->unique(['corrida_id', 'sesion_id']);
            $t->unique(['corrida_id', 'gestor_id', 'fecha']);
        });
        Schema::create('historial_asignaciones', function (Blueprint $t) {
            $t->id();
            $t->foreignId('ticket_id')->constrained();
            $t->unsignedBigInteger('gestor_anterior')->nullable();
            $t->unsignedBigInteger('gestor_nuevo');
            $t->string('motivo');
            $t->foreignId('corrida_id')->nullable()->constrained('corridas_optimizacion');
            $t->timestamps();
        });
        Schema::create('historial_tipo_docente', function (Blueprint $t) {
            $t->id();
            $t->foreignId('ticket_id')->constrained();
            $t->foreignId('user_id')->nullable()->constrained();
            $t->string('de');
            $t->string('a');
            $t->decimal('peso_antes', 12, 2);
            $t->decimal('peso_despues', 12, 2);
            $t->timestamps();
        });
        Schema::create('incidencias_importacion', function (Blueprint $t) {
            $t->id();
            $t->string('clave')->unique();
            $t->string('hoja');
            $t->unsignedInteger('fila');
            $t->string('codigo')->nullable();
            $t->text('detalle');
            $t->json('original')->nullable();
            $t->boolean('resuelta')->default(false);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['incidencias_importacion', 'historial_tipo_docente', 'historial_asignaciones', 'supervisiones', 'corridas_optimizacion', 'docente_grupo', 'docentes', 'sesiones', 'grupos', 'tickets', 'feriados', 'parametros', 'ausencias_gestor', 'gestores'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
