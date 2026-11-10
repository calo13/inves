<?php

namespace App\Support;

use App\Models\User;

class Recorrido
{
    public static function pasos(User $user): array
    {
        $admin = $user->hasRole('admin');
        $edita = $user->hasAnyRole(['admin', 'mesa_entrada']);
        $pasos = [];
        $add = function (string $modulo, string $titulo, string $descripcion, array $acciones, string $nota, string|array|null $objetivos = null) use (&$pasos) {
            $pasos[] = compact('modulo', 'titulo', 'descripcion', 'acciones', 'nota') + [
                'objetivos' => (array) $objetivos,
                'url' => $modulo === 'dashboard' ? route('dashboard') : route('modulo', $modulo),
            ];
        };

        $add('dashboard', 'Bienvenido a Proyecto IO UMG',
            'Esta aplicación organiza tickets de eventos docentes, compara la carga del equipo y planifica el acompañamiento de las sesiones.',
            ['La zona iluminada muestra el control que estamos explicando.', 'Usa Siguiente y Anterior para recorrer tus pantallas.', 'Puedes omitir la guía y repetirla desde el botón Recorrido.'],
            'Este recorrido solo explica: no importa archivos, modifica tickets ni aplica asignaciones.');
        $add('dashboard', 'Tu punto de partida: el panel',
            $edita ? 'Aquí puedes ver cómo se distribuye el trabajo y qué solicitudes requieren atención.' : 'Aquí consultas la carga y los tickets que corresponden a tu gestor vinculado.',
            ['Compara la carga ponderada y la cantidad de tickets de cada gestor.', 'En el mismo panel encontrarás tickets abiertos, próximas fechas y alertas.'],
            'Dos gestores pueden tener el mismo número de tickets y una carga distinta.',
            'carga-equipo');
        $add('dashboard', 'Encuentra la información que necesitas',
            'Los filtros ajustan la información visible en el panel y en las exportaciones.',
            ['Busca por código de ticket o nombre del evento.', 'Filtra por estado, prioridad, modalidad y tipo de docente.', 'El rango de fechas corresponde al inicio del evento. Si no encuentras un ticket, revisa los filtros.'],
            $edita ? 'Los filtros del tablero no reducen por sí solos el conjunto que se optimiza al generar una propuesta.' : 'Tu cuenta solo permite consultar la información autorizada para tu gestor.',
            'buscar-ticket');

        if ($edita) {
            $add('importacion', 'Selecciona el archivo de trabajo',
                'Usa este selector para cargar el Excel de control, sin registrar cada ticket a mano.',
                ['Elige un archivo .xlsx de hasta 15 MB, con la estructura del archivo original.'],
                'Elegir el archivo no guarda tickets.',
                'archivo-control');
            $add('importacion', 'Revisa el Excel antes de importarlo',
                'Previsualizar archivo te permite comprobar códigos y cantidades.',
                ['Comprueba la muestra y usa Confirmar importación cuando esté correcta.'],
                'Reimportar conserva las asignaciones y correcciones existentes.',
                'previsualizar-archivo');
            $add('calidad', 'Revisa la calidad de los datos',
                'Antes de planificar, comprueba si hay duplicados, fechas ambiguas o información pendiente.',
                ['Cada incidencia identifica hoja, fila, ticket y motivo.', 'Consulta la fuente para confirmar la información correcta.', 'Corrige el ticket y sus sesiones desde Mesa de Entrada.'],
                'No se inventan fechas ni sesiones. Un ticket incompleto puede impedir una planificación completa.',
                'incidencias');
        }

        $add('tickets', $edita ? 'Registra y administra los tickets' : 'Consulta tus tickets',
            $edita ? 'Un ticket reúne los datos del evento y su responsable administrativo.' : 'Mesa de Entrada te permite revisar los eventos que están bajo tu responsabilidad.',
            $edita
                ? ['Usa + Nuevo ticket o Editar en un registro existente.', 'Completa fechas, prioridad, modalidad, tipo de docente, grupos y sesiones.', 'Sugerir gestor compara disponibilidad y carga resultante; revisa la sugerencia antes de guardar.']
                : ['Busca el ticket que necesitas consultar.', 'Revisa sus fechas, prioridad, tipo de docente y carga.', 'Solicita a Mesa de Entrada cualquier corrección necesaria.'],
            $edita ? 'Los tickets abiertos participan en la planificación. Los resueltos y cancelados no se reasignan.' : 'Tu rol es de consulta: no cambia tickets ni asignaciones.',
            $edita ? 'nuevo-ticket' : 'registro-tickets');
        if ($edita) {
            $add('tickets', 'Registra las sesiones y sus condiciones',
                'El calendario necesita las fechas reales de las sesiones del evento.',
                ['Al crear o editar un ticket, agrega sesiones con fecha, horario y grupo.', 'Docente Nuevo, Primera Vez o Tema Nuevo hacen obligatorias todas sus sesiones; también puedes marcar una sesión crítica individual.', 'Al corregir fechas o condiciones, genera una nueva propuesta de calendario.'],
                'Las sesiones críticas requieren cobertura del 100%. La muestra mínima inicial de las regulares es del 25%.',
                ['editar-ticket', 'registro-tickets']);
        }

        if ($admin) {
            $add('recursos', 'Administra el equipo de gestores',
                'Usa Agregar gestor para incorporar a una persona al equipo.',
                ['Indica su nombre y una capacidad relativa mayor que cero.', 'Usa Activar o Inhabilitar para cambiar su disponibilidad general.'],
                'El equipo no está limitado a cinco gestores.',
                'agregar-gestor');
            $add('recursos', 'Registra una ausencia temporal',
                'Elige el gestor, las fechas y el motivo antes de pulsar Guardar ausencia.',
                ['Puedes registrar vacaciones, incapacidad o licencia.', 'Usa Rebalancear carga · vista previa para evaluar cómo cambia la distribución.'],
                'Guardar una ausencia no redistribuye automáticamente las asignaciones existentes.',
                'guardar-ausencia');
            $add('parametros', 'Conoce las reglas y los accesos',
                'Configuración reúne las reglas que usa el modelo y la administración de cuentas.',
                ['La carga combina tipo de docente, modalidad, grupos y sesiones por grupo.', 'Revisa pesos, muestra mínima, plazos y días no hábiles; puedes conservar los valores iniciales.', 'Crea cuentas con el rol correspondiente y vincula cada cuenta de gestor a su integrante.'],
                'Las cuentas nuevas requieren contraseñas de al menos 12 caracteres. Cambiar las reglas puede exigir una nueva propuesta.',
                'muestra-minima');
        }
        if ($edita) {
            $add('simulador', 'Evalúa un cambio de docente',
                'Compara el efecto de pasar un ticket a Interno, Cooperante o Externo.',
                ['Selecciona un ticket abierto, el nuevo tipo y la fecha de referencia.', 'Simular cambio muestra peso, semáforo y carga resultante sin guardar.', 'Aplicar cambio y proponer rebalanceo sí guarda el tipo y genera una propuesta; las asignaciones se aplican después, por separado.'],
                'Cambiar de gestor no elimina un plazo de contratación insuficiente. Revisa la alerta de días hábiles.',
                'simular-cambio');
            $add('calendario', 'Prepara el calendario',
                'El sistema propone responsables y sesiones de supervisión manteniendo las fechas originales.',
                ['Selecciona Planificar desde para definir el horizonte de trabajo.', 'Si faltan datos, corrígelos o activa explícitamente el alcance de tickets completos.', 'Pulsa Generar calendario · vista previa para crear una corrida.'],
                'Los tickets excluidos conservan su asignación. La cobertura solo se certifica sobre las sesiones incluidas.',
                'generar-calendario');
            $add('calendario', 'Revisa antes de aplicar',
                'Una corrida es un resultado de planificación. Generarla todavía no guarda las nuevas asignaciones.',
                ['Compara carga anterior y propuesta, cobertura obligatoria, muestra y choques.', 'Solo una propuesta Validada y vigente permite Aplicar propuesta validada.', 'Si aparece No aplicable, lee los errores; si cambiaron los datos, genera una propuesta nueva.'],
                'Cada gestor puede supervisar como máximo una sesión al día, aunque los horarios sean distintos.',
                ['aplicar-propuesta', 'validacion-corrida', 'generar-calendario']);
        }

        $add('calendario', 'Lee la agenda de acompañamiento',
            $edita ? 'El calendario muestra las supervisiones de la corrida seleccionada.' : 'Aquí consultas tus supervisiones, incluidos los apoyos a otros tickets.',
            ['Usa las vistas de mes o semana y la tabla de fechas.', 'Obligatoria identifica sesiones críticas; Muestreo, sesiones regulares seleccionadas; Apoyo, un supervisor distinto del responsable del ticket.', 'La Ruta B, Virtual Asincrónico, se deriva a DFV y no ocupa supervisiones.'],
            $edita ? 'Comprueba siempre número de corrida, estado y fecha. Una vista previa o una prueba puede diferir del último calendario aplicado.' : 'Revisa tu nombre y las fechas. Para cambiar una asignación, consulta con el equipo responsable.',
            'leyenda-calendario');
        $add('dashboard', 'Presenta los resultados',
            'Los reportes permiten revisar y compartir los datos que puedes consultar.',
            ['Ajusta los filtros antes de descargar.', 'Exportar Excel incluye Tickets, Calendario y Resumen.', 'PDF incluye un resumen y el calendario agrupado por gestor.'],
            'El calendario exportado procede de la última corrida aplicada, no de una propuesta que siga en vista previa.',
            'exportaciones');
        if ($admin) {
            $add('pruebas', 'Comprueba los tres escenarios',
                'Pruebas de validación permite demostrar el comportamiento del modelo y conservar evidencias.',
                ['Prueba 1 compara la distribución inicial con la optimización.', 'Prueba 2 retira al Gestor 1 dentro del escenario; Prueba 3 cambia dos tickets de Interno a Externo.', 'Revisa fecha y alcance. Abre Ver evidencia para consultar cada resultado.'],
                'Las pruebas restauran los datos de trabajo al terminar. No aplican cambios permanentes ni ocultan una falta real de capacidad.',
                'ejecutar-prueba-1');
        }
        $add('dashboard', 'Ya puedes comenzar',
            $edita ? 'Sigue este orden para trabajar con información confirmada y resultados revisables.' : 'Utiliza el panel, tus tickets y el calendario para dar seguimiento a tu trabajo.',
            $edita
                ? ['Importar o registrar → revisar calidad → comprobar disponibilidad.', 'Generar propuesta → revisar validación → aplicar si es válida.', 'Consultar calendario → exportar resultados.']
                : ['Revisa tus tickets y las próximas fechas.', 'Consulta tus supervisiones y apoyos en el calendario.', 'Filtra y descarga la información que necesites.'],
            'El recorrido volverá al iniciar sesión. Si quieres verlo otra vez ahora, usa Recorrido en la barra superior.');

        return $pasos;
    }
}
