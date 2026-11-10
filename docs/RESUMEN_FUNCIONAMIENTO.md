# Resumen de funcionamiento

**Proyecto IO UMG — Optimización de tickets y acompañamiento docente**

Universidad Mariano Gálvez de Guatemala · Investigación de Operaciones

**Estudiante:** Carlos Augusto Lopez Urizar · **6.º semestre**

**Documento:** 28/10/2026 · **Entrega del proyecto:** 30/10/2026

## ¿Para qué sirve?

La aplicación ayuda a distribuir el trabajo de atención de eventos docentes entre gestores y a seleccionar qué sesiones deben supervisarse. Permite importar tickets desde Excel, registrar eventos y sesiones, revisar problemas de datos, considerar ausencias y consultar los resultados en un tablero y un calendario.

## ¿Cómo funciona?

1. **Registrar y revisar.** Se importan o crean los tickets, con fechas, modalidad, tipo de docente, grupos y sesiones. Las inconsistencias se muestran en Calidad de datos.
2. **Calcular la carga.** Cada ticket recibe un peso según el tipo de docente y el trabajo operativo. El sistema compara esa carga con la capacidad relativa de cada gestor; tener igual número de tickets no significa tener igual carga.
3. **Preparar una propuesta.** Con una fecha de referencia y los gestores disponibles, la aplicación propone una distribución y un calendario. Las fechas originales de las sesiones permanecen fijas.
4. **Validar y aplicar.** Se revisan cobertura, muestra mínima, ausencias y choques. Solo una propuesta validada y vigente puede aplicarse. Generar una vista previa todavía no cambia las asignaciones.
5. **Consultar y presentar.** El tablero, el calendario y las exportaciones permiten explicar el resultado. Las pruebas guardan evidencias y restauran los datos de trabajo al terminar.

## Reglas principales

- Cada gestor puede supervisar como máximo una sesión al día, aunque los horarios sean distintos.
- Las sesiones críticas requieren cobertura del 100%; para las regulares, la muestra mínima inicial es del 25%.
- La modalidad Virtual Asincrónico sigue la Ruta B hacia DFV y no ocupa supervisiones del calendario.
- Los registros incompletos deben corregirse o excluirse explícitamente mediante el alcance parcial. La cobertura reportada corresponde únicamente a las sesiones incluidas.

La aplicación utiliza Laravel, Livewire y la plantilla TailAdmin. Guarda los datos en MySQL y utiliza un motor de optimización para apoyar decisiones del proyecto académico.
