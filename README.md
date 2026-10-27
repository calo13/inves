# inves

Optimización de tickets y acompañamiento docente.

Proyecto de Investigación de Operaciones, UMG, sexto semestre. Entrega confirmada: **30/10/2026**.

Aplicación Laravel 12, PHP 8.2+, Livewire 3 y MySQL 8. Interfaz basada en la plantilla oficial **TailAdmin Free Laravel**, con Blade, Tailwind CSS v4, ApexCharts y FullCalendar. Se conserva la licencia MIT del repositorio original. No es una maqueta: las acciones utilizan servicios y base de datos.

## Abrir el proyecto académico de este equipo

La aplicación local utiliza **MySQL 8.0.44**, base `docencia_io`, administrada desde Workbench, y **Apache de XAMPP**. Se trasladaron los datos originales y se conservó el respaldo SQLite. El entorno Python `.venv` y los recursos visuales compilados están instalados.

En este equipo, Apache de XAMPP sirve el proyecto directamente en **http://127.0.0.1:8000**. Mantener Apache iniciado desde el panel de XAMPP y ejecutar `iniciar.bat` para abrirlo. No ejecutar a la vez `php artisan serve` en el puerto 8000. El detalle de esta configuración está en `docs/XAMPP_MYSQL.md`.

Abrir http://127.0.0.1:8000. El acceso de esta tarea académica es `admin@admin.com`, contraseña `123`. Autor: **Carlos Augusto Lopez Urizar**, estudiante de **6.º semestre**. En Configuración se puede cambiar y crear cuentas de Mesa de Entrada y de gestores.

## Instalación en Windows con MySQL

Requisitos: PHP 8.2 o superior con bcmath, ctype, curl, dom, fileinfo, gd, iconv, mbstring, mysqli, pdo_mysql, simplexml, xmlreader, xmlwriter y zip; Composer 2; Node 22; MySQL 8; Python 3.12 recomendado para OR-Tools.

1. Crear una base **nueva y vacía** `docencia_io` desde MySQL o phpMyAdmin. Crear o elegir un usuario con acceso a esa base.
2. En una instalación nueva, copiar `.env.example` a `.env`. En esta copia de demostración, editar las variables de conexión del `.env` existente, conservando `APP_KEY`. Ajustar `DB_CONNECTION=mysql`, `DB_DATABASE=docencia_io`, usuario y contraseña. No compartir `.env`.
3. Instalar y preparar:

```powershell
composer install
npm.cmd ci
php artisan key:generate
php artisan config:clear
php artisan migrate --seed
npm.cmd run build
php artisan io:demo
php artisan serve --host=127.0.0.1 --port=8000
```

En una copia ya configurada no regenerar la clave. `io:demo` carga el archivo incluido `database/fixtures/Control_Prueba_IO.xlsx`, sin borrar tablas. Alternativa: subir otro Control.xlsx desde Importar Excel. Para archivos grandes se recomienda `memory_limit=512M` en php.ini; el importador ya omite hojas de reportes y estilos innecesarios.

## Motor Python y respaldo PHP

```powershell
py -3.12 -m venv .venv
.\.venv\Scripts\python.exe -m pip install -r optimizador/requirements.txt
```

En `.env`:

```dotenv
IO_MOTOR=python
IO_PYTHON="C:/ruta/al/proyecto/.venv/Scripts/python.exe"
```

`php artisan config:clear` aplica los cambios. Python recibe y devuelve JSON mediante Symfony Process, sin concatenar comandos de shell. Si Python no se puede ejecutar, se utiliza la heurística PHP y se registra el motivo. Para usar PHP directamente: `IO_MOTOR=php`.

## Flujo principal

- **Importar Excel:** revisar una previsualización y confirmar la importación.
- **Calidad de datos:** consultar incidencias, incluida la fila duplicada original.
- **Mesa de Entrada:** registrar o editar tickets, marcar criterios críticos, corregir sesiones confirmadas y solicitar sugerencia de gestor.
- **Gestores y ausencias:** crear gestores, cambiar disponibilidad y registrar vacaciones. Simular el rebalanceo antes de aplicarlo.
- **Cambio de docente:** comparar peso y semáforo, aplicar el cambio y revisar la reasignación propuesta.
- **Calendario:** ejecutar una corrida, revisar cobertura y conflictos, y aplicar únicamente una propuesta validada.
- **Panel ejecutivo:** filtrar carga, conteos, prioridades, fechas y cobertura.
- **Pruebas de validación:** ejecutar tres escenarios con restauración transaccional y evidencia guardada.
- **Configuración:** pesos, factores, plazos, capacidad, días no hábiles y cuentas con roles.

## Datos incompletos: decisión explícita

El archivo contiene 113 filas de tickets pero 112 códigos únicos. `6212-PFIN-26` aparece dos veces: se conserva el primer ticket y la segunda fila íntegra queda en incidencias, sin sobrescribir la primera. No se interpreta una fecha textual ambigua ni se inventan sesiones.

Con los datos originales hay **6 tickets abiertos incompletos**. Por defecto una corrida total se bloquea. El usuario puede marcar **Planificar solo tickets con fechas y sesiones completas**. En ese modo quedan 38 tickets en el alcance y 6 pendientes, identificados por código. Los pendientes conservan su asignación y su carga cuenta como fija cuando su gestor sigue activo. El resultado certifica solamente las sesiones incluidas. No debe presentarse como cobertura de toda la base.

Para obtener una corrida completa, confirmar fechas y sesiones de esos tickets desde Mesa de Entrada. Los tickets resueltos y cancelados no se reasignan. La optimización contempla las sesiones desde la fecha de referencia; no reescribe observaciones pasadas ni desplaza eventos para eliminar conflictos.

## Verificación

```powershell
php artisan test
.\.venv\Scripts\python.exe -m unittest discover -s optimizador -p "test_*.py"
npm.cmd run build
php artisan io:optimizar --fecha=2026-10-26 --solo-planificables
php artisan io:validar NUMERO_DE_CORRIDA
```

`io:optimizar` genera una vista previa; agregar `--aplicar` guarda asignaciones únicamente si el validador acepta la corrida. Los tests PHP utilizan una base SQLite en memoria, nunca la base de trabajo. En el entorno restringido de Codex/Windows PHP `is_readable()` devuelve falso para archivos accesibles; la importación y las pruebas se ejecutaron fuera de ese aislamiento con autorización de la herramienta.

## Documentación

- `docs/MODELO.md`: formulación, objetivo y supuestos.
- `docs/MANUAL_USUARIO.md`: operación y guion de las pruebas.
- `docs/BITACORA_IA.md`: decisiones, prompts, errores reales y correcciones.
- `docs/INFORME_TECNICO.md`: base del informe académico.
- `docs/VERIFICACION.md`: resultados de comprobación reales.

Fuentes técnicas primarias: https://github.com/TailAdmin/tailadmin-laravel y https://livewire.laravel.com/docs/3.x/installation. El frontend utiliza las bibliotecas de la plantilla, cargadas localmente y compiladas con Vite. Para entrega sin Internet puede sustituirse la fuente web Outfit por una fuente local; el sistema ya usa sans-serif como respaldo.

No usar `migrate:fresh` sobre una base con información que se quiera conservar. No hay publicación en Internet ni conexión al HelpDesk real. El video individual y los datos de autoría del informe los completa el estudiante.

## Archivos del repositorio

Se incluyen el código, las migraciones, los parámetros de ejemplo, los archivos de dependencias bloqueados, el Excel de prueba y la documentación. No se incluyen .env, contraseñas de MySQL, bases locales, respaldos, logs, cachés ni dependencias instaladas. Después de clonar, seguir la instalación indicada arriba y copiar .env.example a .env.

La configuración de Apache de este equipo es local. Para otro equipo, copiar config/apache-docencia.conf.example como config/apache-docencia.conf y ajustar la ruta del proyecto antes de incluirlo en Apache.

El informe se puede regenerar en Windows con scripts/generar-informe.py y las dependencias de scripts/requirements-informe.txt.
