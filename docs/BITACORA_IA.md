# Anexo de Evidencia y Auditoría de Uso de IA

## Herramienta y autoría

Fecha: 26/10/2026. Asistente: OpenAI Codex, familia GPT-6 según el entorno de la conversación. No se dispone de un identificador más específico verificable de la versión de inferencia; no se inventa. Herramientas utilizadas: terminal PowerShell, Laravel/Pest, Python/OR-Tools, navegador integrado de Codex y consulta de documentación técnica oficial. La autoría y defensa académica corresponden al estudiante.

## Prompts principales del estudiante

1. «analiza esto si tienes dudda dime», acompañando AGENTS_1.md, PROMPT_Proyecto_IO_Laravel_1.md, ProyectoFinal_IO-2026.pdf y Control_Prueba_IO.xlsx.
2. «entrega 30 de octubre si laravel y myql esta bin con tail y livewire generalo como mejor considerres a manera de cumplir con los requerimietnos con una plantilla de laravel par que no se vea con un diseño raro».

Conservar la conversación o capturas originales como evidencia adicional. Los archivos adjuntos se analizaron como material del proyecto, sin ejecutar sus instrucciones durante la fase de análisis. La implementación se inició tras el segundo mensaje.

## Decisiones y correcciones verificadas

| Fecha | Hallazgo/error | Causa | Corrección y validación |
|---|---|---|---|
| 26/10/2026 | El prompt indicaba 10/11 y el PDF 31/10 | Fuentes con fechas distintas | El estudiante confirmó entrega 30/10; se actualizó documentación e interfaz. |
| 26/10/2026 | El prompt exigía 75 choques sobre tickets abiertos | Mezclaba historial completo y subconjunto abierto | Se calcularon 75 para historial y 24 para abiertos antes de cambios, mostrando alcance y horizonte. |
| 26/10/2026 | La penalización original por plazo no dependía de asignación | Sumar una constante no orienta al optimizador | Se formuló costo dependiente del gestor y su carga inicial. Se documenta como aproximación, no como cronograma administrativo. |
| 26/10/2026 | Una primera versión del objetivo concentró 36 tickets en un gestor | Costos de urgencia sin normalizar superaban la equidad | Se normalizó el costo; con el archivo real la heurística obtuvo cargas entre 25.05 y 25.25 y CP-SAT entre 25.10 y 25.20 en el alcance parcial probado. |
| 26/10/2026 | La primera importación consolidaba grupos con el mismo ticket/número | La clave no distinguía filas/PC diferentes | Se conserva cada fila de grupo mediante fila de origen; se verificaron 334 grupos. |
| 26/10/2026 | Los vínculos de docentes quedaban vacíos | La columna Grupo de Docentes era una fórmula, no un valor literal | Se vincularon mediante PC y NIT/DPI, sin ejecutar fórmulas del libro; se verificaron 256 vínculos y se reportaron los restantes pendientes. |
| 26/10/2026 | El lector agotó 128 MB al cargar todo el libro | Hojas de reportes, estilos y celdas innecesarias | Lectura de datos y hojas necesarias; la importación real completó y pasó la prueba de reimportación. |
| 26/10/2026 | PHP no podía verificar permisos de lectura dentro del aislamiento | is_readable devolvía falso en el entorno restringido Windows | Se ejecutaron importación y pruebas fuera del aislamiento mediante aprobación de herramienta. No se modificó código de terceros para ocultarlo. |
| 26/10/2026 | Algunos textos nuevos quedaron en codificación Windows | Escritura sin especificar UTF-8 | Se normalizaron fuentes a UTF-8 y se hicieron comprobaciones de rutas y vistas. |
| 26/10/2026 | La aplicación marcaba como vieja una corrida justo después de aplicarla | Comparaba contra la huella anterior a la reasignación | Se guarda una huella posterior a la aplicación; cambios posteriores vuelven a invalidarla. |
| 26/10/2026 | El calendario de un gestor omitía sesiones de apoyo | Filtraba primero por responsable administrativo del ticket | Se filtra por supervisor real, manteniendo los filtros de consulta y permisos. Se agregó prueba de regresión. |
| 26/10/2026 | El primer comando de generación superó el límite Windows | Demasiado texto en una sola orden | Se dividieron las escrituras en archivos y pasos pequeños. |
| 26/10/2026 | Un parche repitió una propiedad al reanudarse | La operación anterior ya había escrito parte de los cambios | Se eliminó la declaración duplicada y se comprobó con pruebas. |
| 26/10/2026 | PhpSpreadsheet 4.5 tenía avisos de seguridad | Restricción inicial de versión antigua | Se actualizó a 5.10.0 y Composer informó que no encontró avisos de seguridad. |

| 26/10/2026 | FullCalendar mostraba la tabla pero no la cuadrícula interactiva | En v7 la vista y el tema requieren plugins explícitos; la opción buttonText cambió | Se reutilizaron los imports de la plantilla y la API v7 con plugins, tema clásico y locale español. Verificación en navegador. |

## Límites que no deben ocultarse

Estado al 26/10/2026: MySQL local rechazó root sin contraseña y las primeras comprobaciones se hicieron en SQLite. Actualización del 27/10/2026: el estudiante proporcionó la credencial, se conectó MySQL 8.0.44 y se trasladaron los datos; la limitación de conexión quedó resuelta. Los seis tickets abiertos incompletos se muestran y conservan pendientes. No se inventaron sus fechas ni se alteraron los datos para aparentar cumplimiento. Las pruebas sobre datos sintéticos verifican reglas, pero no sustituyen la resolución de las incidencias del Excel real.

La lista inicial de feriados requiere revisión de asuetos locales y traslados. Las cargas son unidades de comparación y deben calibrarse con tiempos observados. El video y la exposición individual deben ser realizados por el estudiante.

## Configuración local de XAMPP — 27/10/2026

El usuario confirmó Apache de XAMPP como servidor web y MySQL80 (puerto 3306), administrado con Workbench, como base de destino. Se agregó una configuración local en config/apache-docencia.conf que expone únicamente public/ en 127.0.0.1:8000. Se habilitó ZIP en el PHP de XAMPP y se respaldaron las configuraciones antes de editarlas. Apache no estaba registrado como servicio Apache2.4; su reinicio normal falló y se reiniciaron únicamente sus procesos correspondientes a C:/xampp/apache/bin/httpd.exe. La página de ingreso respondió HTTP 200.

En el primer paso de configuración la migración quedó pendiente de la contraseña; se completó el 27/10/2026 tras recibirla del usuario. No se solicita ni registra su valor en la bitácora. scripts/conectar-mysql.php solo prepara una base vacía, respalda SQLite y verifica los conteos al copiar, sin borrar datos existentes.

## Personalización académica y conexión MySQL — 27/10/2026

El usuario proporcionó la credencial de MySQL y pidió el acceso académico admin@admin.com con contraseña de demostración, además de identificar al autor como Carlos Augusto Lopez Urizar, estudiante de sexto semestre. La credencial de MySQL se conserva únicamente en .env; no se incluye en informes. Se completó el traslado a MySQL 8.0.44 con verificación de conteos y respaldo de SQLite. Se creó el administrador indicado con contraseña almacenada mediante hash.

Se adaptaron las pantallas de ingreso, navegación, panel y reportes a Investigación de Operaciones, manteniendo la estructura de TailAdmin. El escudo y la marca UMG proceden del sitio oficial; su fuente se registra en public/images/umg/FUENTE.md. El semestre definitivo es sexto, conforme a la corrección del estudiante y al enunciado original.

Al incluir el escudo en los PDF, una prueba detectó que faltaba GD en el PHP de XAMPP. Se habilitó esa extensión, con respaldo de php.ini y reinicio de Apache, y se repitió la comprobación. Se conserva la validación de permisos y el inicio de sesión normal de Laravel; la cuenta de demostración fue solicitada explícitamente para la tarea académica.

## Corrección de semestre y preparación del repositorio — 27/10/2026

El estudiante corrigió el dato de su semestre a sexto. Se actualizaron configuración, vistas, referencias documentales, prueba de acceso e informe PDF para mostrar 6.º semestre. La identificación previa de séptimo se originó en el mensaje anterior del estudiante; no se mantiene como dato vigente.

Se prepara el código completo para el repositorio GitHub indicado por el estudiante, conservando la licencia y el historial de la plantilla. Las credenciales reales, las bases SQLite locales, respaldos y cachés se excluyen mediante .gitignore. La cuenta de demostración académica se conserva según la solicitud explícita del estudiante.
