# Herramienta de optimización de tickets y acompañamiento docente

Universidad Mariano Gálvez de Guatemala. Investigación de Operaciones, sexto semestre. Modalidad individual. Fecha de entrega confirmada: 30 de octubre de 2026.

Autor: **Carlos Augusto Lopez Urizar**, estudiante de **6.º semestre**. Carné, sección y catedrático: pendientes de completar por el estudiante antes de la entrega.

## 1. Análisis del proceso

La Mesa de Entrada recibe solicitudes HelpDesk, registra prioridad, modalidad, dimensionamiento y fechas, y asigna un gestor. El reparto rotativo trata todos los tickets como unidades iguales. Eso oculta diferencias entre contratación externa, coordinación de cooperantes, gestión interna, grupos y sesiones. El sistema propuesto calcula unidades de carga y sugiere asignaciones según disponibilidad.

La preparación, ejecución y cierre de Ruta A permanecen en Docencia. En Ruta B, DFV asume la operación asincrónica después de la coordinación: se conserva una carga administrativa menor, pero no se generan supervisiones diarias. La contratación externa necesita más preparación y puede permanecer en alerta aunque se reasigne a otro gestor.

## 2. Datos y calidad

El archivo entregado contiene 113 filas de tickets, con 112 códigos únicos. Sus estados de origen son 44 abiertos, 67 resueltos y 2 cancelados. Se identificaron 334 grupos, 389 sesiones y 1,441 registros del catálogo de docentes. El ticket 6212-PFIN-26 se repite; su segunda fila se conserva íntegra en incidencias, sin fusionarla silenciosamente.

Las anomalías incluyen fechas textuales ambiguas, años fuera del rango del caso, fin anterior al inicio, asignaciones anteriores al ingreso, modalidades sin fecha de sesión y valores de texto inconsistentes. El importador aplica trim y normalización de categorías, registra los cambios y omite la interpretación automática de fechas ambiguas. Se lee el dato fuente y no se confía en las fórmulas de los tableros históricos. En particular, Programación calcula antigüedad desde el inicio del evento, mientras que el modelo utiliza el ingreso del ticket.

En la copia importada hay seis tickets abiertos incompletos: 5795-PFAN-26, 5796-PFAN-26, 5797-PFAN-26, 6384-PFIN-26, 6389-PFIN-26 y 6404-PFIN-26. Sin su confirmación una corrida total no es aplicable. El usuario puede elegir el alcance parcial con exclusiones explícitas y conservarlos pendientes.

## 3. Modelo y algoritmo

La formulación completa se encuentra en MODELO.md, anexo a este informe. El peso combina tipo de docente y complejidad operativa de grupos por sesiones por grupo, reducida en Ruta B. La función objetivo minimiza el rango de carga ajustado por capacidad y agrega una penalización de urgencia dependiente del gestor. Se considera antigüedad desde ingreso, prioridad y días hábiles disponibles.

Las restricciones duras son asignación única, disponibilidad de gestores, máximo una supervisión por gestor y fecha, 100% de cobertura crítica, muestreo mínimo configurable y exclusión de Ruta B del calendario. El responsable administrativo puede ser distinto al supervisor de apoyo. Una falta de capacidad se informa, sin mover sesiones ni relajar reglas.

Se implementan OR-Tools CP-SAT y una heurística PHP de asignación y mejora local. Un validador independiente revisa sus resultados antes de persistir. El muestreo usa semilla configurable. CP-SAT reporta el estado del solver y conserva la solución factible si termina el tiempo del desempate.

## 4. Arquitectura y herramienta

Laravel 12 y MySQL 8 constituyen la arquitectura de destino. Livewire 3 conecta formularios y filtros con servicios del servidor. TailAdmin Free Laravel aporta componentes Blade, estilos Tailwind, ApexCharts y FullCalendar. La lógica de negocio se mantiene en servicios independientes de las vistas. La comunicación con Python es JSON mediante un proceso con tiempo límite.

Las entidades principales son usuarios y roles, gestores, ausencias, tickets, grupos, sesiones, docentes, vínculos docente–grupo, parámetros, feriados, corridas, supervisiones e historiales. La aplicación de una propuesta se realiza en transacción, con índices únicos para impedir duplicación de supervisiones y choques diarios dentro de una corrida. Las vistas previas tienen una huella para rechazar propuestas desactualizadas.

La aplicación incluye ingesta con previsualización, incidencias, Mesa de Entrada, sugerencia de gestor, gestión de recursos, simulador de docente, agenda, panel con filtros, tres pruebas y exportación Excel/PDF. Los roles limitan acciones y datos; un gestor consulta sus tickets y supervisiones, incluido apoyo.

## 5. Validación

Se verificaron reglas mediante pruebas PHP y Python y revisión de páginas en el navegador. La aplicación funciona con MySQL 8.0.44, base docencia_io, administrada con Workbench, y Apache de XAMPP. Las pruebas automáticas usan una base SQLite aislada para no alterar los datos del proyecto. Los resultados y comandos exactos se mantienen en VERIFICACION.md.

Sobre el archivo real y el horizonte desde 26/10/2026, una corrida parcial de 38 tickets mantuvo seis pendientes. La heurística consiguió un rango de carga de 0.20 unidades; CP-SAT obtuvo 0.10 en la ejecución comprobada. En ambos casos se verificaron cero colisiones de supervisión en el alcance incluido. Esos resultados dependen de parámetros, estado y alcance. No representan una garantía de resultado idéntico ante cualquier cambio.

Las tres pruebas de la herramienta guardan evidencia y restauran los datos: optimización inicial, baja del Gestor 1 y cambio de dos tickets a contratación externa. Las pruebas automáticas incluyen una instancia inviable con seis sesiones críticas y cinco gestores, ausencias, muestreo reproducible, reimportación, datos incompletos, protección de vistas previas y permisos.

## 6. Manuales y límites

MANUAL_USUARIO.md contiene la operación y el guion del video. README.md contiene instalación y verificación. Se deben revisar los feriados locales y traslados y completar las incidencias de datos antes de certificar una planificación total. Las unidades de carga son comparativas y requieren calibración con tiempos de trabajo observados.

## 7. Anexo de Evidencia y Auditoría de Uso de IA

BITACORA_IA.md forma parte obligatoria del informe: identifica la herramienta, transcribe prompts principales y registra errores reales, sus causas y correcciones. El estudiante debe conservar capturas de la conversación y explicar personalmente el razonamiento, las decisiones y los límites en un video de máximo 15 minutos, apareciendo en cámara.
