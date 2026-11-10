# Manual de usuario

Proyecto IO UMG · Carlos Augusto Lopez Urizar · 6.º semestre

Versión: 28/10/2026. Estas instrucciones describen la aplicación local y sus botones actuales.

## 1. Acceso y recorrido inicial

### Ingresar

1. Compruebe que Apache esté iniciado en XAMPP y que el servicio MySQL80 esté funcionando.
2. Abra **http://127.0.0.1:8000/** en el navegador. También puede abrir **iniciar.bat**, que dirige a esa dirección.
3. Ingrese con la cuenta académica: **admin@admin.com**, contraseña **123**.
4. Utilice el menú lateral para cambiar de módulo y cierre la sesión al terminar.

MySQL Workbench permite administrar la base de datos; el servicio MySQL80 debe estar activo para que la aplicación guarde y consulte información.

### Módulos del menú

| Módulo | ¿Para qué se utiliza? |
| --- | --- |
| Panel ejecutivo | Consultar carga, alertas, filtros e indicadores. |
| Mesa de Entrada | Crear y corregir tickets, eventos y sesiones. |
| Gestores y ausencias | Agregar gestores y registrar disponibilidad. |
| Cambio de docente | Comparar el efecto de cambiar el tipo de docente. |
| Calendario | Proponer y revisar las supervisiones por fecha. |
| Importar Excel | Previsualizar y cargar el archivo de origen. |
| Calidad de datos | Consultar duplicados, fechas dudosas e incidencias. |
| Configuración | Ajustar reglas, días no hábiles y cuentas. |
| Pruebas de validación | Ejecutar los tres escenarios de demostración. |

### Permisos

**Administrador:** accede a todos los módulos. **Mesa de Entrada:** registra e importa datos, consulta el tablero, simula y puede aplicar propuestas; no administra recursos, configuración ni pruebas. **Gestor:** consulta sus tickets, su carga y sus supervisiones, incluyendo apoyos; no modifica registros. No existe registro público de cuentas.

## 2. Importar el archivo y revisar los datos

### Cargar un Excel

1. Entre en **Importar Excel** y seleccione el archivo original, por ejemplo **Control_Prueba_IO.xlsx**.
2. Use un archivo **.xlsx**, de hasta **15 MB**, con la estructura de las hojas **Registro**, **Sesiones** y **EjecucionXGrupos**. No cambie sus encabezados ni organización.
3. Pulse **Previsualizar archivo** y revise las cantidades y los códigos mostrados.
4. Pulse **Confirmar importación** y espere el resultado.
5. Abra **Calidad de datos** antes de generar una propuesta.

### Interpretar las incidencias

La lista identifica hoja, fila, ticket y detalle del problema. En un duplicado puede consultar la fila original. Una fecha textual ambigua o un año imposible necesita confirmación con la fuente; el sistema no debe adivinarla.

Para corregir un ticket existente, abra **Mesa de Entrada → Editar** y revise sus sesiones. Calidad de datos sirve para consultar incidencias; no dispone de un botón para resolverlas automáticamente.

### Reimportación y alcance

Volver a importar evita duplicar los registros ya reconocidos y conserva las asignaciones y correcciones existentes. **No utilice la reimportación como sobrescritura masiva** cuando cambie el Excel; revise los registros afectados.

El archivo original contiene seis tickets abiertos incompletos: **5795-PFAN-26, 5796-PFAN-26, 5797-PFAN-26, 6384-PFIN-26, 6389-PFIN-26 y 6404-PFIN-26**. Estas cantidades pueden variar después de corregir los datos.

Puede completar la información confirmada o activar **Planificar solo tickets con fechas y sesiones completas**. Los excluidos conservan su asignación y se listan como pendientes. El resultado no certifica su cobertura.

## 3. Crear y corregir tickets

### Registrar un evento

1. Abra **Mesa de Entrada** y pulse **+ Nuevo ticket**.
2. Complete código, nombre del evento, dependencia, estado y prioridad.
3. Seleccione modalidad y tipo de docente: **Interno, Cooperante o Externo**.
4. Registre fecha de ingreso, inicio y fin, grupos y sesiones por grupo. El ingreso sirve para evaluar antigüedad; las fechas del evento describen su ejecución.
5. Añada observaciones y seleccione el gestor. **Sugerir gestor** compara disponibilidad, capacidad y carga resultante; revise la sugerencia antes de guardar.

### Registrar las sesiones

En **Sesiones nuevas**, pulse **+ Agregar sesión** y complete fecha, hora de inicio, hora de fin y grupo. Repita para cada sesión. Puede usar **Quitar** para retirar una fila nueva antes de guardar.

Marque **Docente Nuevo**, **Primera Vez** o **Tema Nuevo** cuando corresponda: cualquiera de esas condiciones hace obligatorias todas las sesiones del ticket. También puede señalar una sesión crítica específica de forma individual.

### Editar un registro

Localice el ticket, pulse **Editar**, corrija los campos y revise **Sesiones registradas**. Guarde los cambios. Si modificó fechas, sesiones o condiciones que afectan la asignación, genere y revise otra propuesta.

**Estados:** Abierto participa en la planificación; Resuelto y Cancelado no se reasignan. Que un evento ya haya comenzado no cierra automáticamente su ticket. Las sesiones anteriores a **Planificar desde** quedan fuera del horizonte de esa propuesta.

**Resultado esperado:** el ticket queda registrado con sus datos y sesiones. Guardarlo no equivale a haber generado y aplicado un nuevo calendario.

## 4. Gestionar el equipo y las ausencias

### Agregar un gestor

1. Entre en **Gestores y ausencias**.
2. Escriba el nombre y una **capacidad relativa** mayor que cero.
3. Guarde el nuevo integrante y compruebe que figure activo.

La capacidad relativa permite comparar cargas de personas con distinta disponibilidad. No representa un número de horas ni permite supervisar más de una sesión al día. El equipo puede tener distintos números de gestores; la aplicación no depende de una cantidad fija.

### Inhabilitar o reactivar

Use **Inhabilitar** cuando una persona no deba recibir nuevas asignaciones y **Activar** cuando vuelva a estar disponible. El cambio de estado no redistribuye por sí solo todos los tickets existentes.

### Registrar una ausencia

1. Seleccione al gestor.
2. Registre el intervalo de fechas y el motivo: vacaciones, incapacidad o licencia.
3. Guarde y compruebe que aparezca en la lista.
4. Si el período fue registrado por error, elimine la ausencia correspondiente.

Después de cambiar la disponibilidad, pulse **Rebalancear carga · vista previa** o genere una propuesta desde **Calendario**. Revise los resultados y aplíquela únicamente si está validada.

### Si falta personal

Una fecha puede tener más sesiones críticas que gestores disponibles. La propuesta puede quedar **No aplicable**. Revise las ausencias y la programación real y determine si necesita más personal. No elimine una condición crítica real ni cambie fechas ficticiamente para aparentar factibilidad.

Una propuesta generada antes de registrar una ausencia queda desactualizada: debe generarse nuevamente.

## 5. Entender la carga y las alertas

### Peso de un ticket

El cálculo combina trabajo administrativo y operativo:

**Peso = α × peso del tipo de docente + β × factor de modalidad × grupos × sesiones por grupo.**

Con los valores iniciales, α = 1 y β = 0.25. Los pesos docentes son Interno = 1, Cooperante = 1.8 y Externo = 3.5. Los factores son Presencial = 1, Virtual Sincrónico = 1, Mixto = 1.2 y Virtual Asincrónico = 0.2.

**Ejemplo:** un evento presencial de dos grupos y tres sesiones por grupo pesa **2.50 unidades** con docente interno y **5.00 unidades** con docente externo. Son unidades del modelo, no horas ni dinero. Los valores cambian si el administrador ajusta los parámetros.

### Rutas de atención

**Ruta A:** Presencial, Virtual Sincrónico y Mixto pueden requerir acompañamiento en el calendario. **Ruta B:** Virtual Asincrónico se deriva a DFV; conserva trabajo administrativo, pero no ocupa supervisiones.

### Semáforo de plazos

Los plazos se expresan en días hábiles, considerando fines de semana y días no hábiles configurados. Los intervalos iniciales son: Interno **5–8**, Cooperante **10–15** y Externo **20–30** días.

**Verde:** alcanza el máximo de referencia. **Amarillo:** alcanza el mínimo, pero no el máximo. **Rojo:** no alcanza el mínimo. **Sin fecha:** falta información para calcularlo.

La urgencia y la antigüedad ayudan a priorizar la atención. Cambiar de gestor no crea días adicionales ni elimina una alerta de contratación con plazo insuficiente.

## 6. Generar, revisar y aplicar una propuesta

1. Revise tickets, sesiones, gestores y ausencias.
2. Abra **Calendario** y seleccione **Planificar desde**. Utilice la fecha de trabajo real; si demuestra un escenario histórico, indíquelo expresamente.
3. Si necesita un alcance parcial, marque la casilla de tickets completos y revise la lista de excluidos.
4. Pulse **Generar calendario · vista previa**. Se guarda una **corrida**, es decir, un resultado identificable de planificación.
5. Compare la carga anterior y propuesta por gestor. Revise obligatorias cubiertas, muestra lograda frente al mínimo, choques y desviación.
6. Lea los avisos. La validación revisa cobertura crítica del 100%, muestra regular mínima, disponibilidad, asignaciones únicas y máximo de una supervisión por gestor/día.
7. Si el estado es **Validada**, compruebe el alcance y pulse **Aplicar propuesta validada**. Confirme la operación.
8. Consulte el calendario y exporte el resultado aplicado.

### Estados y alcance

| Estado o aviso | ¿Qué debe hacer? |
| --- | --- |
| Validada | Revisar y aplicar para guardar las asignaciones. |
| No aplicable | Leer los errores y corregir datos o disponibilidad real. |
| Aplicada | Consultar asignaciones y calendario guardados. |
| Datos cambiaron | Generar otra propuesta antes de aplicarla. |
| Prueba sin aplicar | Consultar evidencia; no es una asignación permanente. |

**Los filtros visuales del tablero no reducen por sí solos el conjunto que optimiza el botón.** Revise fecha y alcance de la corrida. Una vista previa no sustituye al calendario aplicado y una validación parcial solo se refiere a las sesiones incluidas.

## 7. Consultar el calendario y el tablero

### Calendario

Utilice las vistas de mes o semana y los controles de navegación para recorrer las fechas. La tabla inferior muestra fecha, ticket, gestor y tipo de supervisión.

**Obligatoria** identifica una sesión crítica; **Muestreo**, una sesión regular seleccionada; **Apoyo**, la participación de un supervisor diferente del responsable administrativo del ticket. Consulte también la leyenda de colores.

Las fechas de las sesiones permanecen fijas. El plan selecciona supervisiones y responsables; no cambia automáticamente el día del evento. La regla de una sesión por gestor/día se aplica aunque existan horarios diferentes.

### Panel ejecutivo

Revise tickets abiertos, carga por gestor, distribución de prioridades, próximos inicios y alertas. Compare **carga ponderada** y **cantidad de tickets** como indicadores distintos.

Use búsqueda y filtros de estado, prioridad, modalidad, tipo de docente y rango de fechas. El rango corresponde al **inicio del evento**. Si falta un ticket, compruebe esos filtros y el acceso de su rol.

### Identificar qué está viendo

Compruebe número de corrida, estado y fecha de referencia. El calendario visible puede corresponder a una propuesta o a una prueba seleccionada. Una corrida desactualizada no debe interpretarse como cobertura vigente.

**Antes de presentar un resultado definitivo, aplique la propuesta validada.** Los reportes descargados utilizan el calendario de la última corrida aplicada; pueden diferir de una vista previa que todavía no haya aplicado.

## 8. Simular un cambio de docente

### Comparar sin guardar

1. Abra **Cambio de docente**.
2. Seleccione un **Ticket abierto**.
3. En **Cambiar a**, elija Interno, Cooperante o Externo.
4. Revise la **Fecha de referencia** y pulse **Simular cambio**.
5. Compare situación actual y propuesta: peso, días hábiles, semáforo, carga resultante del gestor y recomendación.

**Simular cambio no guarda el nuevo tipo de docente.** Sirve para evaluar el efecto antes de decidir.

### Guardar el cambio

Pulse **Aplicar cambio y proponer rebalanceo** y confirme cuando decida continuar. Esta acción sí guarda el nuevo tipo de docente y su historial, y prepara una propuesta de redistribución.

Revise el resultado de esa propuesta. Para guardar las nuevas asignaciones necesita pulsar, por separado, **Aplicar propuesta validada**, cuando el resultado permita hacerlo.

### Ejemplo de interpretación

Un ticket que cambia de Interno a Externo puede aumentar su peso y requerir mayor anticipación. El sistema puede recomendar rebalancear la carga. Aunque el reparto mejore, la alerta de plazo seguirá roja si los días disponibles son insuficientes.

Si solo quiere comparar, use la simulación. Si ya guardó una modificación equivocada, revise y corrija el tipo real del ticket y genere una nueva propuesta.

## 9. Configurar reglas, usuarios y reportes

### Parámetros y días no hábiles

Como administrador, entre en **Configuración**. Revise pesos administrativos y operativos, factores por modalidad, plazos docentes y muestra mínima. La muestra admite valores de **0.25 a 1**, equivalentes al 25% y al 100%.

Los campos de equidad, urgencia, sobrecarga, semilla y tiempo límite controlan el modelo. Para la demostración inicial puede conservar los valores entregados. Pulse **Guardar parámetros** al terminar.

En **Días no hábiles**, registre fecha y descripción y pulse **Agregar día**. Use **Quitar** para retirar un registro equivocado. Revise los asuetos locales pertinentes. Los cambios que afecten el cálculo requieren una nueva propuesta.

### Cuentas de acceso

En **Crear cuenta de acceso**, complete nombre, correo, contraseña y rol. Las cuentas nuevas requieren **al menos 12 caracteres** de contraseña. Si el rol es gestor, seleccione su gestor vinculado y pulse **Crear cuenta**.

La cuenta académica inicial **admin@admin.com / 123** se entrega para esta demostración. Para cambiar la contraseña del administrador, use **Cambiar mi contraseña**, escriba la actual y repita la nueva.

### Descargar reportes

Desde los controles de exportación del tablero, descargue **Excel** o **PDF**. Los filtros y el acceso del usuario determinan los registros del reporte.

**Excel:** hojas Tickets, Calendario y Resumen. **PDF:** resumen ejecutivo y calendario agrupado por gestor. El calendario exportado procede de la última corrida aplicada. Si necesita presentar una propuesta nueva, revísela y aplíquela antes de descargar.

## 10. Pruebas, problemas frecuentes y demostración

### Ejecutar las tres pruebas

Como administrador, abra **Pruebas de validación**, revise fecha y alcance y ejecute los botones correspondientes. Cada escenario restaura los datos de trabajo al terminar y conserva una evidencia que puede consultar con **Ver evidencia**.

**Prueba 1:** compara las asignaciones iniciales con la optimización. **Prueba 2:** desactiva al Gestor 1 dentro del escenario y calcula con el equipo restante. **Prueba 3:** cambia dos tickets abiertos de Interno a Externo y muestra el efecto en peso y plazos. Los cambios de las pruebas no son permanentes.

Si usa registros incompletos, corríjalos con información confirmada o declare el alcance parcial. Un escenario sin capacidad suficiente puede resultar no aplicable; explique la causa mostrada.

### Problemas frecuentes

| Situación | Acción recomendada |
| --- | --- |
| El sitio no abre o no carga datos | Revisar Apache en XAMPP, MySQL80 y la dirección local. |
| No encuentro un ticket | Revisar filtros, estado y permisos del usuario. |
| No puedo aplicar la propuesta | Revisar estado, errores y aviso de datos modificados; generar otra si corresponde. |
| Hay tickets sin fechas o sesiones | Editar con datos confirmados o usar alcance parcial explícito. |
| No caben las sesiones críticas | Revisar gestores y ausencias; aumentar disponibilidad real o corregir programación confirmada. |
| El reporte difiere de la vista previa | Comprobar filtros y aplicar la propuesta validada antes de exportar. |

### Recorrido sugerido para exponer

Explique el objetivo; muestre un ticket, su carga y sesiones; revise calidad de datos; genere una propuesta; describa cobertura y choques; aplique si es válida; consulte calendario y reportes. Ejecute las tres pruebas y explique sus evidencias. Distinga siempre una simulación, una prueba y una asignación aplicada.
