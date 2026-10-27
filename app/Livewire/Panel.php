<?php

namespace App\Livewire;

use App\Models\Corrida;
use App\Models\Gestor;
use App\Models\Sesion;
use App\Models\Ticket;
use App\Models\User;
use App\Services\CargaService;
use App\Services\ImportadorExcel;
use App\Services\OptimizadorService;
use App\Services\Parametros;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Panel extends Component
{
    use WithFileUploads,WithPagination;

    #[Locked]
    public string $modulo = 'dashboard';

    #[Url]
    public string $busqueda = '';

    #[Url]
    public string $estado = 'Abierto';

    #[Url]
    public string $prioridad = '';

    #[Url]
    public string $modalidad = '';

    #[Url]
    public string $tipo = '';

    #[Url]
    public string $desde = '';

    #[Url]
    public string $hasta = '';

    public bool $soloPlanificables = false;

    public string $fecha = '';

    public string $mensaje = '';

    public string $error = '';

    public $archivo;

    public array $preview = [];

    public array $form = [];

    public array $sesionesNuevas = [];

    public array $sesionesExistentes = [];

    public bool $formVisible = false;

    #[Locked]
    public ?int $editId = null;

    #[Locked]
    public ?int $corridaId = null;

    public string $gestorNombre = '';

    public string $capacidad = '1';

    public string $ausenteGestor = '';

    public string $ausenteDesde = '';

    public string $ausenteHasta = '';

    public string $motivo = 'Vacaciones';

    public string $tipoNuevo = 'Externo';

    public string $ticketCambio = '';

    public array $simulacion = [];

    public array $config = [];

    public string $feriadoFecha = '';

    public string $feriadoDescripcion = '';

    public string $usuarioNombre = '';

    public string $usuarioEmail = '';

    public string $usuarioClave = '';

    public string $usuarioRol = 'gestor';

    public string $usuarioGestor = '';

    public function mount(string $modulo = 'dashboard'): void
    {
        abort_unless(in_array($modulo, ['dashboard', 'tickets', 'recursos', 'simulador', 'calendario', 'importacion', 'calidad', 'parametros', 'pruebas']), 404);
        $this->modulo = $modulo;
        if (in_array($modulo, ['recursos', 'parametros', 'pruebas'])) {
            $this->admin();
        } elseif (in_array($modulo, ['importacion', 'calidad', 'simulador'])) {
            $this->editor();
        }
        $this->fecha = now()->toDateString();
        $this->config = Parametros::todos();
        $this->corridaId = Corrida::latest('id')->value('id');
    }

    private function admin(): void
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);
    }

    private function editor(): void
    {
        abort_unless(auth()->user()->hasAnyRole(['admin', 'mesa_entrada']), 403);
    }

    public function updated($name): void
    {
        if (in_array($name, ['busqueda', 'estado', 'prioridad', 'modalidad', 'tipo', 'desde', 'hasta'])) {
            $this->resetPage();
        }if ($name === 'archivo') {
            $this->preview = [];
        }
    }

    public function consulta(bool $soloPropios = true)
    {
        $q = Ticket::with('gestor');
        if ($soloPropios && ! auth()->user()->hasAnyRole(['admin', 'mesa_entrada'])) {
            $q->whereIn('gestor_id', Gestor::where('user_id', auth()->id())->select('id'));
        }

        return $q->when($this->busqueda, fn ($q) => $q->where(fn ($x) => $x->where('codigo', 'like', '%'.$this->busqueda.'%')->orWhere('evento_formativo', 'like', '%'.$this->busqueda.'%')))->when($this->estado, fn ($q) => $q->where('estado', $this->estado))->when($this->prioridad, fn ($q) => $q->where('prioridad', $this->prioridad))->when($this->modalidad, fn ($q) => $q->where('modalidad', $this->modalidad))->when($this->tipo, fn ($q) => $q->where('tipo_docente', $this->tipo))->when($this->desde, fn ($q) => $q->whereDate('fecha_inicio', '>=', $this->desde))->when($this->hasta, fn ($q) => $q->whereDate('fecha_inicio', '<=', $this->hasta));
    }

    public function nuevo(): void
    {
        $this->editor();
        $this->editId = null;
        $this->form = ['codigo' => '', 'evento_formativo' => '', 'estado' => 'Abierto', 'prioridad' => 'Media', 'modalidad' => 'Presencial', 'tipo_docente' => 'Interno', 'fecha_ingreso' => now()->format('Y-m-d\TH:i'), 'fecha_inicio' => '', 'fecha_fin' => '', 'gestor_id' => '', 'grupos_programados' => 1, 'sesiones_por_grupo' => 1, 'dependencia' => '', 'docente_nuevo' => false, 'primera_vez' => false, 'tema_nuevo' => false, 'observaciones' => ''];
        $this->sesionesNuevas = [];
        $this->sesionesExistentes = [];
        $this->formVisible = true;
        $this->simulacion = [];
    }

    public function editar(int $id): void
    {
        $this->editor();
        $this->nuevo();
        $t = Ticket::findOrFail($id);
        $this->editId = $id;
        $this->form = array_intersect_key($t->toArray(), $this->form);
        $this->form['fecha_ingreso'] = $t->fecha_ingreso?->format('Y-m-d\TH:i') ?? '';
        $this->form['fecha_inicio'] = $t->fecha_inicio?->toDateString() ?? '';
        $this->form['fecha_fin'] = $t->fecha_fin?->toDateString() ?? '';
        $this->sesionesExistentes = $t->sesiones->map(fn ($s) => ['id' => $s->id, 'numero' => $s->numero, 'fecha' => $s->fecha?->toDateString() ?? '', 'hora_inicio' => $s->hora_inicio ? substr($s->hora_inicio, 0, 5) : '', 'hora_fin' => $s->hora_fin ? substr($s->hora_fin, 0, 5) : '', 'critica' => $s->es_critica])->all();
    }

    public function agregarSesion(): void
    {
        $this->editor();
        $this->sesionesNuevas[] = ['fecha' => '', 'hora_inicio' => '08:00', 'hora_fin' => '10:00', 'grupo' => '1', 'critica' => false];
    }

    public function quitarSesion(int $i): void
    {
        $this->editor();
        unset($this->sesionesNuevas[$i]);
        $this->sesionesNuevas = array_values($this->sesionesNuevas);
    }

    public function sugerir(): void
    {
        $this->editor();
        $this->validate(['form.tipo_docente' => Rule::in(['Interno', 'Externo', 'Cooperante']), 'form.modalidad' => Rule::in(['Presencial', 'Virtual Sincrónico', 'Virtual Asincrónico', 'Mixto']), 'form.grupos_programados' => 'required|integer|min:1|max:1000', 'form.sesiones_por_grupo' => 'required|integer|min:1|max:1000', 'form.fecha_fin' => 'required|date|after_or_equal:today']);
        $service = app(OptimizadorService::class);
        $in = $service->entrada(now()->toDateString());
        $carga = app(CargaService::class);
        $weight = $carga->peso(new Ticket($this->form));
        $loads = [];
        foreach ($in['gestores'] as $g) {
            if ($service->disponible($g, now()->toDateString(), $this->form['fecha_fin'])) {
                $load = Ticket::where('estado', 'Abierto')->where('gestor_id', $g['id'])->when($this->editId, fn ($q) => $q->where('id', '!=', $this->editId))->get()->sum(fn ($t) => $carga->peso($t));
                $loads[] = ['id' => $g['id'], 'nombre' => $g['nombre'], 'actual' => $load, 'resultante' => $load + $weight, 'normalizada' => ($load + $weight) / $g['capacidad']];
            }
        }
        usort($loads, fn ($a, $b) => $a['normalizada'] <=> $b['normalizada']);
        $this->simulacion = ['sugerencia' => $loads, 'peso' => $weight];
        if ($loads) {
            $this->form['gestor_id'] = $loads[0]['id'];
        }
    }

    public function guardarTicket(): void
    {
        $this->editor();
        $this->validate(['form.codigo' => ['required', 'max:80', Rule::unique('tickets', 'codigo')->ignore($this->editId)], 'form.evento_formativo' => 'required|string|max:1000', 'form.estado' => Rule::in(['Abierto', 'Resuelto', 'Cancelado']), 'form.prioridad' => Rule::in(['Urgente', 'Alta', 'Media', 'Baja']), 'form.modalidad' => Rule::in(['Presencial', 'Virtual Sincrónico', 'Virtual Asincrónico', 'Mixto']), 'form.tipo_docente' => Rule::in(['Interno', 'Externo', 'Cooperante']), 'form.fecha_ingreso' => 'required|date', 'form.fecha_inicio' => 'required|date', 'form.fecha_fin' => 'required|date|after_or_equal:form.fecha_inicio', 'form.gestor_id' => 'required|exists:gestores,id', 'form.grupos_programados' => 'required|integer|min:1|max:1000', 'form.sesiones_por_grupo' => 'required|integer|min:1|max:1000', 'form.docente_nuevo' => 'boolean', 'form.primera_vez' => 'boolean', 'form.tema_nuevo' => 'boolean', 'sesionesNuevas.*.fecha' => 'required|date|after_or_equal:form.fecha_inicio|before_or_equal:form.fecha_fin', 'sesionesNuevas.*.hora_inicio' => 'required|date_format:H:i', 'sesionesNuevas.*.hora_fin' => 'required|date_format:H:i', 'sesionesNuevas.*.grupo' => 'required|integer|min:1', 'sesionesNuevas.*.critica' => 'boolean', 'sesionesExistentes.*.id' => 'required|integer', 'sesionesExistentes.*.fecha' => 'nullable|date', 'sesionesExistentes.*.hora_inicio' => 'nullable|date_format:H:i', 'sesionesExistentes.*.hora_fin' => 'nullable|date_format:H:i', 'sesionesExistentes.*.critica' => 'boolean']);
        $g = Gestor::findOrFail($this->form['gestor_id']);
        if (! $g->activo || DB::table('ausencias_gestor')->where('gestor_id', $g->id)->where('desde', '<=', $this->form['fecha_fin'])->where('hasta', '>=', now()->toDateString())->exists()) {
            $this->addError('form.gestor_id', 'El gestor no está disponible durante la ventana del ticket.');

            return;
        }
        foreach ($this->sesionesNuevas as $i => $s) {
            if ($s['hora_fin'] <= $s['hora_inicio']) {
                $this->addError("sesionesNuevas.$i.hora_fin", 'La hora final debe ser posterior a la inicial.');

                return;
            }
        }
        DB::transaction(function () {
            $old = $this->editId ? Ticket::findOrFail($this->editId) : null;
            $values = $this->form;
            $values['ruta'] = $values['modalidad'] === 'Virtual Asincrónico' ? 'B' : 'A';
            $values['fecha_asignacion'] = $old?->fecha_asignacion ?? now();
            $t = Ticket::updateOrCreate(['id' => $this->editId], $values);
            if (! $old || $old->gestor_id != $t->gestor_id) {
                DB::table('historial_asignaciones')->insert(['ticket_id' => $t->id, 'gestor_anterior' => $old?->gestor_id, 'gestor_nuevo' => $t->gestor_id, 'motivo' => 'manual', 'created_at' => now(), 'updated_at' => now()]);
            }
            if ($old && $old->tipo_docente !== $t->tipo_docente) {
                $this->historialTipo($old, $t);
            }
            foreach ($this->sesionesExistentes as $s) {
                $t->sesiones()->whereKey($s['id'])->update(['fecha' => $s['fecha'] ?: null, 'hora_inicio' => $s['hora_inicio'] ?: null, 'hora_fin' => $s['hora_fin'] ?: null, 'es_critica' => $s['critica']]);
            }
            foreach ($this->sesionesNuevas as $i => $s) {
                $gid = DB::table('grupos')->where('ticket_id', $t->id)->where('numero', $s['grupo'])->value('id') ?? DB::table('grupos')->insertGetId(['ticket_id' => $t->id, 'numero' => $s['grupo']]);
                Sesion::create(['ticket_id' => $t->id, 'grupo_id' => $gid, 'numero' => (string) ($t->sesiones()->count() + 1), 'fecha' => $s['fecha'], 'hora_inicio' => $s['hora_inicio'], 'hora_fin' => $s['hora_fin'], 'es_critica' => $s['critica'], 'fila_origen' => (Sesion::max('fila_origen') ?? 10000) + 1]);
            }
        });
        $this->formVisible = false;
        $this->mensaje = 'Ticket guardado. Genere una nueva corrida para actualizar las supervisiones.';
    }

    private function historialTipo(Ticket $old, Ticket $new): void
    {
        $c = app(CargaService::class);
        DB::table('historial_tipo_docente')->insert(['ticket_id' => $new->id, 'user_id' => auth()->id(), 'de' => $old->tipo_docente, 'a' => $new->tipo_docente, 'peso_antes' => $c->peso($old), 'peso_despues' => $c->peso($new), 'created_at' => now(), 'updated_at' => now()]);
    }

    public function previsualizar(): void
    {
        $this->editor();
        $this->validate(['archivo' => 'required|file|mimes:xlsx|max:15360']);
        try {
            $this->preview = app(ImportadorExcel::class)->previsualizar($this->archivo->getRealPath());
            $this->error = '';
        } catch (\Throwable $e) {
            $this->error = $e->getMessage();
        }
    }

    public function importar(): void
    {
        $this->editor();
        $this->validate(['archivo' => 'required|file|mimes:xlsx|max:15360']);
        if (! $this->preview) {
            return;
        }try {
            $r = app(ImportadorExcel::class)->importar($this->archivo->getRealPath());
            $this->mensaje = 'Importación terminada: '.$r['tickets'].' tickets únicos y '.$r['sesiones'].' sesiones. Revise Calidad de datos.';
            $this->preview = [];
            $this->archivo = null;
        } catch (\Throwable $e) {
            report($e);
            $this->error = 'No se importó el archivo: '.$e->getMessage();
        }
    }

    public function crearGestor(): void
    {
        $this->admin();
        $this->validate(['gestorNombre' => 'required|string|max:100|unique:gestores,nombre', 'capacidad' => 'required|numeric|min:0.1|max:10']);
        Gestor::create(['nombre' => $this->gestorNombre, 'capacidad_relativa' => $this->capacidad]);
        $this->gestorNombre = '';
        $this->mensaje = 'Gestor agregado; ya está disponible para nuevas asignaciones.';
    }

    public function alternarGestor(int $id): void
    {
        $this->admin();
        $g = Gestor::findOrFail($id);
        $g->update(['activo' => ! $g->activo]);
        $this->mensaje = 'Disponibilidad actualizada. Simule el rebalanceo antes de aplicarlo.';
    }

    public function guardarAusencia(): void
    {
        $this->admin();
        $this->validate(['ausenteGestor' => 'required|exists:gestores,id', 'ausenteDesde' => 'required|date', 'ausenteHasta' => 'required|date|after_or_equal:ausenteDesde', 'motivo' => 'required|string|max:100']);
        DB::table('ausencias_gestor')->insert(['gestor_id' => $this->ausenteGestor, 'desde' => $this->ausenteDesde, 'hasta' => $this->ausenteHasta, 'motivo' => $this->motivo, 'created_at' => now(), 'updated_at' => now()]);
        $this->mensaje = 'Ausencia registrada. Genere una simulación para revisar el rebalanceo.';
    }

    public function eliminarAusencia(int $id): void
    {
        $this->admin();
        DB::table('ausencias_gestor')->where('id', $id)->delete();
        $this->mensaje = 'Ausencia eliminada.';
    }

    public function simularCambio(): void
    {
        $this->editor();
        $this->validate(['ticketCambio' => 'required|exists:tickets,id', 'tipoNuevo' => Rule::in(['Interno', 'Externo', 'Cooperante']), 'fecha' => 'required|date']);
        $t = Ticket::findOrFail($this->ticketCambio);
        abort_unless($t->estado === 'Abierto', 422);
        $c = app(CargaService::class);
        $before = ['tipo' => $t->tipo_docente, 'peso' => $c->peso($t), 'semaforo' => $c->semaforo($t, $this->fecha)];
        $t->tipo_docente = $this->tipoNuevo;
        $delta = $c->peso($t) - $before['peso'];
        $gs = Gestor::where('activo', true)->get();
        $total = Ticket::where('estado', 'Abierto')->get()->sum(fn ($ticket) => $c->peso($ticket));
        $gestorLoad = Ticket::where('estado', 'Abierto')->where('gestor_id', $t->gestor_id)->get()->sum(fn ($ticket) => $c->peso($ticket)) + $delta;
        $target = ($total + $delta) / max(0.1, $gs->sum('capacidad_relativa'));
        $over = $gestorLoad / max(0.1, $t->gestor?->capacidad_relativa ?? 1) > $target * Parametros::todos()['umbral'];
        $this->simulacion = ['sobrecargado' => $over, 'carga_resultante' => round($gestorLoad, 2), 'recomendacion' => $over ? 'El gestor supera el umbral de carga. Se recomienda generar un rebalanceo.' : 'El gestor permanece dentro del umbral de carga configurado.', 'antes' => $before, 'despues' => ['tipo' => $t->tipo_docente, 'peso' => $c->peso($t), 'semaforo' => $c->semaforo($t, $this->fecha)], 'gestor' => $t->gestor?->nombre];
    }

    public function aplicarCambio(): void
    {
        $this->editor();
        $this->simularCambio();
        DB::transaction(function () {
            $t = Ticket::findOrFail($this->ticketCambio);
            $old = clone $t;
            $t->update(['tipo_docente' => $this->tipoNuevo]);
            $this->historialTipo($old, $t);
        });
        $this->simular();
        $this->mensaje = 'Cambio aplicado y registrado. Revise la propuesta de rebalanceo.';
    }

    public function simular(): void
    {
        $this->editor();
        $this->validate(['fecha' => 'required|date']);
        $this->error = '';
        $run = app(OptimizadorService::class)->simular($this->fecha, $this->soloPlanificables);
        $this->corridaId = $run->id;
        $this->mensaje = 'Simulación #'.$run->id.': '.$run->estado.'. Aún no se han modificado las asignaciones.';
    }

    public function seleccionarCorrida(int $id): void
    {
        $this->editor();
        $this->corridaId = Corrida::findOrFail($id)->id;
    }

    public function aplicar(): void
    {
        $this->editor();
        try {
            app(OptimizadorService::class)->aplicar(Corrida::findOrFail($this->corridaId));
            $this->mensaje = 'Asignaciones y calendario aplicados.';
            $this->error = '';
        } catch (\Throwable $e) {
            $this->error = $e->getMessage();
        }
    }

    public function guardarParametros(): void
    {
        $this->admin();
        $this->validate(['config.alpha' => 'required|numeric|min:0.01|max:100', 'config.beta' => 'required|numeric|min:0.001|max:100', 'config.theta' => 'required|numeric|min:0.25|max:1', 'config.semilla' => 'required|integer|min:0|max:2147483647', 'config.w1' => 'required|numeric|min:0.01|max:1000', 'config.w2' => 'required|numeric|min:0|max:1000', 'config.umbral' => 'required|numeric|min:1|max:10', 'config.tiempo_limite' => 'required|integer|min:1|max:120', 'config.pesos.*' => 'required|numeric|min:0.01|max:100', 'config.factores.*' => 'required|numeric|min:0|max:100', 'config.plazos.*.*' => 'required|integer|min:0|max:365']);
        foreach ($this->config['plazos'] as $type => $v) {
            if ($v[0] > $v[1]) {
                $this->addError('config', 'El plazo mínimo no puede superar al máximo.');

                return;
            }
        }foreach (array_intersect_key($this->config, Parametros::todos()) as $k => $v) {
            Parametros::guardar($k, $v);
        }$this->mensaje = 'Parámetros guardados. Las cargas se recalculan con los valores actuales.';
    }

    public function guardarFeriado(): void
    {
        $this->admin();
        $this->validate(['feriadoFecha' => 'required|date', 'feriadoDescripcion' => 'required|string|max:150']);
        DB::table('feriados')->updateOrInsert(['fecha' => $this->feriadoFecha], ['descripcion' => $this->feriadoDescripcion]);
        $this->mensaje = 'Día no hábil guardado.';
    }

    public function eliminarFeriado(int $id): void
    {
        $this->admin();
        DB::table('feriados')->where('id', $id)->delete();
    }

    public function crearUsuario(): void
    {
        $this->admin();
        $this->validate(['usuarioNombre' => 'required|string|max:100', 'usuarioEmail' => 'required|email|unique:users,email', 'usuarioClave' => 'required|string|min:12', 'usuarioRol' => Rule::in(['admin', 'mesa_entrada', 'gestor']), 'usuarioGestor' => 'nullable|exists:gestores,id']);
        if ($this->usuarioRol === 'gestor' && ! $this->usuarioGestor) {
            $this->addError('usuarioGestor', 'Seleccione el gestor de esta cuenta.');

            return;
        }if ($this->usuarioGestor && Gestor::find($this->usuarioGestor)->user_id) {
            $this->addError('usuarioGestor', 'El gestor ya tiene una cuenta vinculada.');

            return;
        }DB::transaction(function () {
            $u = User::create(['name' => $this->usuarioNombre, 'email' => $this->usuarioEmail, 'password' => Hash::make($this->usuarioClave)]);
            $u->assignRole($this->usuarioRol);
            if ($this->usuarioRol === 'gestor') {
                Gestor::findOrFail($this->usuarioGestor)->update(['user_id' => $u->id]);
            }
        });
        $this->reset('usuarioNombre', 'usuarioEmail', 'usuarioClave', 'usuarioGestor');
        $this->mensaje = 'Cuenta creada.';
    }

    public function prueba(int $n): void
    {
        $this->admin();
        $this->validate(['fecha' => 'required|date']);
        abort_unless(in_array($n, [1, 2, 3]), 422);
        $snapshot = null;
        DB::beginTransaction();
        try {
            $baseline = app(OptimizadorService::class)->simular($this->fecha, $this->soloPlanificables);
            $changes = [];
            if ($n === 2) {
                $g = Gestor::where('nombre', 'Gestor 1')->firstOrFail();
                $g->update(['activo' => false]);
                $changes[] = ['gestor' => $g->nombre, 'estado' => 'Inactivo durante la prueba'];
            }
            if ($n === 3) {
                $tickets = Ticket::where('estado', 'Abierto')->where('tipo_docente', 'Interno')->orderByRaw('CASE WHEN fecha_inicio >= ? THEN 0 ELSE 1 END', [$this->fecha])->orderBy('fecha_inicio')->limit(2)->get();
                if ($tickets->count() < 2) {
                    throw new \RuntimeException('Se requieren dos tickets abiertos con docente Interno.');
                }foreach ($tickets as $t) {
                    $old = clone $t;
                    $t->update(['tipo_docente' => 'Externo']);
                    $c = app(CargaService::class);
                    $changes[] = ['ticket' => $t->codigo, 'peso_antes' => $c->peso($old), 'peso_despues' => $c->peso($t), 'plazo' => $c->semaforo($t, $this->fecha)];
                }
            }
            $run = $n === 1 ? $baseline : app(OptimizadorService::class)->simular($this->fecha, $this->soloPlanificables);
            $snapshot = $run->getAttributes();
            $result = $run->resultado;
            $result['prueba'] = $n;
            $result['cambios_prueba'] = $changes;
            $result['comparacion_prueba'] = ['antes' => $n === 1 ? $baseline->antes : $baseline->resultado['metricas'], 'despues' => $run->resultado['metricas']];
            $snapshot['resultado'] = $result;
            $snapshot['parametros'] = $run->parametros;
            $snapshot['antes'] = $run->antes;
            unset($snapshot['id'],$snapshot['created_at'],$snapshot['updated_at']);
        } catch (\Throwable $e) {
            $this->error = $e->getMessage();
        } finally {
            DB::rollBack();
        }
        if ($snapshot) {
            $snapshot['estado'] = 'Prueba '.$n.' (sin aplicar)';
            $run = Corrida::create($snapshot);
            $this->corridaId = $run->id;
            $this->mensaje = 'Prueba ejecutada. Se conservaron las evidencias y se restauraron los datos originales.';
        }
    }

    public function render()
    {
        $c = app(CargaService::class);
        $p = Parametros::todos();
        $all = $this->consulta()->get();
        $gestores = Gestor::orderBy('id')->get();
        if (auth()->user()->hasRole('gestor') && ! auth()->user()->hasAnyRole(['admin', 'mesa_entrada'])) {
            $gestores = $gestores->where('user_id', auth()->id());
        }
        $loads = $gestores->map(fn ($g) => ['id' => $g->id, 'nombre' => $g->nombre, 'activo' => $g->activo, 'tickets' => $all->where('gestor_id', $g->id)->count(), 'carga' => round($all->where('gestor_id', $g->id)->sum(fn ($t) => $c->peso($t, $p)), 2), 'capacidad' => $g->capacidad_relativa])->values();
        $run = $this->corridaId ? Corrida::find($this->corridaId) : null;
        $visibleIds = $all->modelKeys();
        $calendar = [];
        $stale = false;
        if ($run) {
            $stale = hash('sha256', json_encode(app(OptimizadorService::class)->entrada($run->parametros['fecha'], $run->parametros['solo_planificables'] ?? false))) !== ($run->resultado['huella_aplicada'] ?? $run->resultado['huella'] ?? '');
            $idsCalendario = auth()->user()->hasAnyRole(['admin', 'mesa_entrada']) ? $visibleIds : $this->consulta(false)->pluck('id')->all();
            $sessionIds = Sesion::whereIn('ticket_id', $idsCalendario)->pluck('id')->all();
            $isEditor = auth()->user()->hasAnyRole(['admin', 'mesa_entrada']);
            $calendar = array_values(array_filter($run->resultado['supervisiones'] ?? [], fn ($s) => (in_array($s['sesion_id'], $sessionIds) && ($isEditor || $gestores->contains('id', $s['gestor_id'])))));
        }
        $sessionMap = Sesion::with('ticket')->whereIn('id', array_column($calendar, 'sesion_id'))->get()->keyBy('id');
        $futureSessions = Sesion::whereIn('ticket_id', $visibleIds)->whereDate('fecha', '>=', $this->fecha)->get()->filter(fn ($s) => $all->firstWhere('id', $s->ticket_id)?->ruta === 'A');
        $covered = count(array_filter($calendar, fn ($s) => in_array($s['sesion_id'], $futureSessions->modelKeys())));
        $total = $futureSessions->count();
        $coverage = $total ? round(100 * $covered / $total, 1) : null;

        return view('livewire.panel', compact('all', 'gestores', 'loads', 'run', 'c', 'p', 'calendar', 'sessionMap', 'stale', 'coverage', 'total') + ['tickets' => $this->consulta()->orderByDesc('fecha_ingreso')->paginate(15), 'incidencias' => DB::table('incidencias_importacion')->orderByDesc('id')->paginate(20,pageName: 'incidencias'), 'ausencias' => DB::table('ausencias_gestor')->join('gestores','gestores.id','=','ausencias_gestor.gestor_id')->select('ausencias_gestor.*','gestores.nombre')->orderBy('desde')->get(), 'feriados' => DB::table('feriados')->orderBy('fecha')->get(), 'corridas' => Corrida::latest('id')->limit(12)->get()])->layout('layouts.io');
    }
}
