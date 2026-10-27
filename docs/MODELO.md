# Modelo de Investigación de Operaciones

## Alcance y datos

G: gestores activos, de tamaño dinámico. T: tickets abiertos incluidos en el horizonte. S: sesiones de Ruta A, con fecha confirmada desde la fecha de referencia. S_ob y S_reg son particiones según criterios críticos. D: fechas reales de esas sesiones; las sesiones no se mueven. Los días hábiles se utilizan para plazos administrativos, excluyendo sábados, domingos y feriados configurados. No se eliminan sesiones existentes de fin de semana si el archivo las contiene.

Los registros incompletos bloquean por defecto la aplicación. Cuando el usuario elige el alcance parcial, se excluyen explícitamente de T y de S, permanecen pendientes y su carga se conserva como fija B_g para gestores activos. Ninguna certificación de cobertura abarca sesiones sin fecha o excluidas.

## Peso y calibración

W_i = α × P(tipo_docente_i) + β × F(modalidad_i) × grupos_i × sesiones_por_grupo_i.

P = Interno 1.0, Cooperante 1.8 y Externo 3.5. F = Presencial 1.0, Sincrónico 1.0, Mixto 1.2 y Asincrónico 0.2. Valores iniciales α=1 y β=0.25: una sesión equivale a una cuarta parte de la gestión administrativa interna. Son unidades comparativas, no horas observadas. Esta propuesta requiere calibración con tiempos reales antes de uso productivo.

Ejemplo con dos grupos y tres sesiones por grupo:

| α | β | Interno presencial | Externo presencial | Interno asincrónico |
|---|---|---|---|---|
| 1 | 0.10 | 1.60 | 4.10 | 1.12 |
| 1 | 0.25 | 2.50 | 5.00 | 1.30 |
| 1 | 0.50 | 4.00 | 6.50 | 1.60 |

Se usa sesiones **por grupo**, no el total de sesiones ya multiplicado por grupos. Ruta B mantiene gestión administrativa y cierre, pero nunca genera supervisiones.

## Variables y disponibilidad

x_ig binaria: ticket i a gestor g. y_sg binaria: gestor g supervisa la sesión s en su fecha fija d_s. La variable de fecha adicional del enunciado se elimina porque d_s ya está definido en el archivo. x e y no son idénticas: se admite apoyo de otro gestor.

A_ig=1 si el gestor está activo y no tiene ausencia entre la fecha de referencia y el fin del ticket. La política es conservadora: cualquier intersección bloquea la asignación administrativa. Para una sesión se revisa solamente su fecha. Tickets cerrados quedan fuera del conjunto de decisiones.

C_g = (B_g + suma_i W_i × x_ig) / capacidad_relativa_g.

## Objetivo y prioridad

Minimizar w1 × (Cmax − Cmin) + w2 × suma_ig K_ig × x_ig.

La penalización no es una constante por ticket: depende del gestor elegido. Sea:

- u_i = prioridad (4,3,2,1) × [1 + max(0, plazo_min_i − días_hábiles_disponibles_i)] + min(antigüedad_i,365)/30.
- L_g: carga actual del gestor al generar la corrida, antes de optimizar.
- K_ig = u_i × max(0,L_g − W_i si g es el responsable actual) / capacidad_relativa_g, normalizado por el producto de la suma de pesos y el máximo u_i.

K representa una penalización de espera aproximada basada en la carga inicial. No estima una fecha real de finalización ni elimina un retraso ya ocurrido. Su normalización evita que la magnitud de prioridades anule el objetivo de equidad. w1=10 y w2=1 inicialmente, configurables. El semáforo mantiene una advertencia independiente del objetivo: rojo por debajo del mínimo, amarillo entre mínimo y máximo, verde desde el máximo. Sin inicio confirmado no se calcula semáforo.

## Restricciones

1. Suma_g x_ig=1 para cada ticket incluido.
2. x_ig=0 si A_ig=0.
3. Suma de y_sg en cada fecha y gestor ≤1.
4. Suma_g y_sg=1 para cada sesión obligatoria.
5. Suma de y_sg en sesiones regulares ≥ceil(θ × |S_reg|). La implementación elige exactamente el mínimo para no aumentar carga de supervisión innecesariamente.
6. y_sg=0 cuando g está ausente el día de s.
7. Cada sesión se supervisa como máximo una vez. No hay y para Ruta B.
8. Cmax≥C_g≥Cmin. Capacidad relativa es estrictamente positiva.

Si las sesiones obligatorias de una fecha superan a los gestores disponibles, la instancia es inviable. No se relaja la cobertura ni la capacidad diaria. Se informa el conflicto para revisar dotación o programación con autorización humana.

## Motores

**CP-SAT, OR-Tools:** enteros escalados a centésimas para pesos y costos, carga normalizada con división entera. Primero optimiza equidad y penalización. Guarda la solución factible y luego intenta mejorar el muestreo, la variedad de tickets cubiertos y la preferencia por el gestor responsable, sin empeorar el objetivo principal. La semilla y un desempate hash hacen reproducible la selección con un trabajador. Si el desempate agota tiempo, conserva la primera solución factible. Se registra el estado del solver; una solución FEASIBLE no se presenta como prueba de óptimo global.

**PHP:** ordena por urgencia y peso, asigna a gestores disponibles y realiza movimientos y permutas que reduzcan el mismo objetivo. Programa primero obligatorias y después sesiones regulares con desempate hash por semilla, favoreciendo tickets aún no cubiertos. La supervisión puede requerir apoyo. La heurística encuentra soluciones, pero no certifica optimalidad.

## Validación y persistencia

Validador independiente: verifica asignación, gestor activo, ausencias, pertenencia al horizonte, fechas inalteradas, sesiones únicas, capacidad diaria, 100% de obligatorias y mínimo de muestreo. Índices únicos por corrida–sesión y corrida–gestor–fecha protegen la persistencia. Las corridas son vistas previas hasta confirmación. Antes de aplicar se comparan los datos con una huella del estado original y se rechazan resultados desactualizados. La aplicación es transaccional y deja historial.

## Medición del antes y después

Una colisión nominal es una combinación gestor–día con más de una sesión de Ruta A, antes de decidir cuáles supervisar. Una colisión de supervisión se calcula sobre las sesiones efectivamente seleccionadas. No son el mismo denominador. El historial completo tiene 75 combinaciones nominales y hasta seis sesiones por gestor/día. Solo abiertos, antes de las modificaciones del sistema y sin corte de fechas: 24 combinaciones y hasta cuatro sesiones. Los valores se calculan, nunca se fijan en la interfaz.

La comparación debe usar idéntico filtro de estado, horizonte y alcance. La baja del Gestor 1 puede ser inviable para fechas históricas con cinco críticas simultáneas. La herramienta debe mostrarlo, no fingir una corrida exitosa.
