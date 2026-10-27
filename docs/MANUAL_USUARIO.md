# Manual de usuario y demostración

## Acceso y roles

Ingresar con la cuenta entregada al instalar. Administrador controla todos los módulos, parámetros y cuentas. Mesa de Entrada importa, registra tickets, simula cambios y propone o aplica asignaciones. Gestor consulta sus tickets, carga y calendario de supervisión, incluyendo el apoyo que le corresponde. No hay registro público.

## Importación

1. Abrir Importar Excel y seleccionar el archivo.
2. Previsualizar y revisar códigos de muestra y número de filas.
3. Confirmar importación y abrir Calidad de datos.
4. Revisar duplicados y fechas dudosas con la fuente. No sustituirlas por suposiciones.

Reimportar conserva las decisiones de tickets y las sesiones ya registradas. Si la fuente cambia, revisar y editar los registros existentes desde el sistema; no utilizar la reimportación como sobrescritura masiva.

## Tickets y sesiones

En Mesa de Entrada, elegir Nuevo ticket, completar ingreso, inicio, fin, modalidad, tipo de docente y dimensionamiento. Marcar Docente Nuevo, Primera Vez o Tema Nuevo cuando corresponda. Sugerir gestor muestra la carga antes y después, ajustada por disponibilidad y capacidad. Puede aceptarse o seleccionarse otro gestor disponible.

Agregar sesiones con su fecha, horario y grupo. Una sesión crítica específica puede marcarse individualmente; las casillas críticas del ticket hacen obligatorias todas sus sesiones. Para corregir un registro importado, usar Editar y revisar Sesiones registradas. Toda corrección requiere regenerar el calendario.

## Calendario y rebalanceo

Seleccionar Planificar desde. Por defecto se exige información completa. Si existen tickets pendientes, se permite elegir explícitamente el alcance de tickets planificables. La propuesta lista las exclusiones y no cambia esos tickets. Revisar cargas, cobertura obligatoria, muestra mínima y avisos. Aplicar solamente una corrida validada y vigente. Si falta capacidad, revisar equipo o programación real. No eliminar la restricción de una sesión por gestor/día.

## Ausencias

Gestores y ausencias permite agregar nuevos integrantes, inhabilitar de inmediato o registrar un rango de vacaciones/incapacidad/licencia. La modificación bloquea nuevas asignaciones; para modificar las existentes hay que generar, revisar y aplicar el rebalanceo. Una vista previa anterior a una ausencia nueva deja de ser aplicable.

## Cambio de docente

Seleccionar un ticket abierto y el nuevo tipo. La simulación muestra peso, días hábiles y semáforo sin guardar. Aplicar cambio registra el historial y crea una propuesta de rebalanceo. Una reasignación no resuelve por sí misma un plazo de contratación imposible; la alerta se mantiene.

## Pruebas obligatorias y video

Usar la fecha real de la demostración o una fecha histórica indicada expresamente. No alterar la fecha del sistema. Cada botón ejecuta una transacción de prueba y restaura los registros originales; las evidencias se conservan en corridas. Para mostrar operaciones permanentes utilizar los módulos normales.

1. Optimización inicial: observar cargas y choques nominales, ejecutar Prueba 1, explicar la cobertura del calendario seleccionado.
2. Vacaciones: Prueba 2 desactiva al Gestor 1 durante el escenario y recalcula con el equipo restante. Si el horizonte contiene más críticas que capacidad, explicar la inviabilidad.
3. Docente externo: Prueba 3 elige dos tickets abiertos Internos, favoreciendo eventos aún no iniciados, cambia a Externo y muestra pesos y plazos. El cambio se revierte al cerrar la prueba; la evidencia queda guardada.

Con el archivo original, primero revisar los seis tickets incompletos o activar el alcance parcial y explicarlo en cámara. No presentar esa demostración como una prueba sobre el 100% de registros.

Guion sugerido de máximo 15 minutos: 2 min proceso y datos; 3 min modelo y reglas; 6 min tres pruebas; 2 min tablero y exportaciones; 2 min errores de IA, correcciones y conclusiones. El estudiante debe aparecer en cámara y comprobar que el enlace del video permite acceso.

## Tablero y reportes

Los filtros de prioridad, modalidad, docente, estado y rango de inicio actualizan el tablero y los enlaces de exportación. Carga ponderada y cantidad de tickets son métricas diferentes. La cobertura de supervisión indica corrida y fecha de referencia; una corrida desactualizada no se muestra como resultado vigente. Excel contiene Tickets, Calendario y Resumen. PDF contiene resumen y calendario por gestor de la última corrida aplicada.
