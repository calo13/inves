# Verificación de la implementación

## Comprobaciones realizadas

- PHP: `php artisan test` — 18 pruebas aprobadas, 78 aserciones. Base SQLite en memoria.
- Python: `.venv/Scripts/python.exe -m unittest discover -s optimizador -p "test_*.py"` — 4 pruebas aprobadas. CP-SAT verifica apoyo, inviabilidad, ausencias, muestreo y cargas fijas.
- Frontend: `npm.cmd run build` — compilación completada. La librería ApexCharts de la plantilla mantiene un aviso de tamaño; se carga de forma diferida únicamente al mostrar gráficas.
- Vistas: `php artisan view:cache` y rutas de aplicación comprobadas.
- Dependencias: Composer informó que no encontró avisos de seguridad después de actualizar PhpSpreadsheet a 5.10.0.
- Navegador: ingreso real, tablero, filtro de prioridad con actualización de cifras y gráfica, calendario mensual y semanal en español. Se corrigió el registro explícito de plugins de FullCalendar v7.
- PDF técnico: 11 páginas renderizadas con Poppler e inspeccionadas en una hoja de contacto.
- Exportaciones: Excel descargable con firma ZIP válida; PDF generado en las pruebas HTTP.

## Libro proporcionado

La importación preserva 112 códigos únicos de 113 filas de tickets, 334 grupos, 389 sesiones, 1,441 docentes y 256 vínculos docente–grupo verificables. Se registran vínculos y datos pendientes como incidencias. Reimportar no duplica tickets, grupos ni sesiones, y conserva cambios realizados en el sistema.

## Datos reales y alcance

`docs/pruebas_reales.json` contiene las tres pruebas ejecutadas sobre transacciones restauradas: 38 tickets completos, seis excluidos de forma explícita, cinco gestores inicialmente y cuatro en el escenario de baja. Se validan las 20 sesiones críticas incluidas y un mínimo de 10 regulares, con cero choques. El tercer escenario cambia dos tickets Internos a Externo; los pesos aumentan 2.50 unidades cada uno. Las variaciones de carga y el estado del motor se conservan en el JSON.

La corrida aplicada #2 en la copia local tuvo cargas de 25.10 a 25.20, rango 0.10, y pasó `php artisan io:validar 2`. Los resultados de otras corridas pueden variar con la carga inicial, la urgencia, el límite de tiempo y el alcance. No se declara un óptimo global cuando CP-SAT solo informa factibilidad.

## Límites pendientes

Actualización 27/10/2026: MySQL 8.0.44 conectado en 127.0.0.1:3306, base docencia_io. Se trasladaron 112 tickets, 334 grupos, 389 sesiones, 1,441 docentes, 256 vínculos, los historiales y dos corridas. Apache de XAMPP sirve public/ en el puerto 8000. El administrador académico es admin@admin.com. La base SQLite se conserva como respaldo y para pruebas aisladas.

Las incidencias del Excel requieren confirmación humana para certificar una planificación del 100% de los tickets abiertos. Las pruebas no alteran fechas para fingir cobertura completa. El informe académico requiere los datos de autoría y el video individual del estudiante.

## Comprobación académica final — 27/10/2026

Inicio de sesión confirmado en navegador con admin@admin.com. Vistas y PDF muestran el escudo institucional, Carlos Augusto Lopez Urizar y 6.º semestre. Las tres pruebas de optimización se repitieron sobre MySQL dentro de transacciones restauradas: cero choques, 20/20 críticas incluidas y 10/10 sesiones de muestreo mínimo. Rangos de carga observados: 0.10, 0.05 y 0.10 unidades. El PDF de resultados con el escudo se generó correctamente a partir de MySQL. La cobertura sigue limitada a los 38 tickets completos, con seis pendientes identificados.
