---
layout: default
lang: es_ES
---

Descripción
===

Groupir permite **agrupar equipos** (y escenarios). Cada grupo, por ejemplo «Luces del salón», es un equipo de Jeedom con sus propios comandos:

- **comandos info**, que indican si los equipos del grupo están en el estado esperado (p. ej. `on` vale `ok` si todas las luces están encendidas);
- **comandos acción**, que controlan todos los equipos a la vez (p. ej. `Encender` enciende las luces y ajusta su brillo y su color).

Estos comandos se usan como cualquier comando de Jeedom: escenarios, widgets, historial, alertas...

![Lista de grupos](../images/Groupes.png)

Configuración del plugin
===

Tras instalarlo desde el Market, active el plugin. **No se necesita ninguna configuración global**: cada grupo se configura desde su equipo, en la pestaña *Correspondencias*.

La función **cron** está activa: cada minuto, el plugin recalcula los grupos cuyos comandos info dependen del estado de un escenario. El nivel de log *Debug* detalla cada recálculo y cada acción ejecutada, lo que ayuda al diagnóstico.

La interfaz está disponible en francés, inglés, alemán, español e italiano.

Crear un grupo
===

Haga clic en **Añadir un grupo**, asígnele un nombre y configure las dos pestañas.

Pestaña «General»
---

Nombre, objeto padre, categoría, opciones *Activar* / *Visible* y descripción, como en cualquier equipo.

El bloque **Escenarios vinculados** muestra:

- los escenarios que **usan este grupo** (uno de sus comandos como disparador, condición o acción);
- los escenarios **controlados por este grupo** (iniciados, detenidos, activados o desactivados por uno de sus comandos acción).

Un clic abre el escenario.

![Pestaña General](../images/Groupe.General.png)

Pestaña «Correspondencias»
---

El grupo contiene **comandos**, cada uno de ellos un subgrupo de equipos:

- **Comando info** (etiqueta azul): calcula un estado a partir de los equipos;
- **Comando acción** (etiqueta naranja): ejecuta acciones sobre los equipos.

Asigne un **nombre** al comando: será el nombre del comando del grupo. Después añada una línea por equipo con el botón **Equipo** (o por escenario con el botón **Escenario**). Cada línea se rellena de izquierda a derecha: **Objeto**, **Equipo**, **Comando**. Cada lista tiene un campo *Filtro*; un filtro que deja un solo resultado lo selecciona automáticamente, y un equipo ya presente en el comando deja de proponerse.

![Pestaña Correspondencias](../images/Correspondances.png)

### Comando info

Para cada equipo: el comando info, una condición (*igual a*, *distinto de*, *mayor que*, *contiene*...) y el **valor esperado** (el valor actual se muestra como indicación).

Los **valores devueltos por el comando** dependen de una regla:

- **si todos coinciden** (por defecto): todas las líneas deben coincidir;
- **si al menos uno coincide**: p. ej. «al menos una luz encendida»;
- **si al menos N coinciden**: p. ej. al menos 2 ventanas abiertas de 4.

El comando toma entonces el valor **alors** (entonces, por defecto `ok`) o, si no, el valor **sinon** (si no, por defecto `ko`). Si ambos valores son numéricos (p. ej. `1` / `0`), el comando es numérico y puede historizarse en gráfico.

Una línea **Escenario** se refiere al estado de un escenario: **está activo**, **está inactivo**, **está en curso** o **está detenido**. Jeedom no notifica los cambios de estado de un escenario: estas líneas se recalculan cada minuto (o inmediatamente con el comando *Refrescar*).

![Comando info](../images/Correspondances.Info.png)

### Comando acción

Para cada equipo: uno o varios comandos acción, ejecutados en orden. El botón **+ Commande** encadena varias acciones sobre un mismo equipo (p. ej. `On`, luego `Brillo` a 254, luego `Color`).

El campo **Valor** se adapta al tipo de comando: número (con el rango permitido como indicación, p. ej. `153 a 500`, y la entrada limitada a ese rango), color, lista o mensaje. Para un comando sin valor (p. ej. `On`), no se muestra ningún campo.

Una línea **Escenario** inicia, detiene, activa o desactiva un escenario, en orden con los equipos (p. ej. encender las luces y luego iniciar el escenario «Ambiente de noche»).

La **pausa entre dos acciones** (de 0 a 30 segundos) espacia cada acción: útil para las bombillas que ignoran un color enviado justo después de encenderse.

![Comando acción](../images/Correspondances.Action.png)

### Organizar los comandos

- <i class="fas fa-grip-vertical"></i> **Arrastrar y soltar**: reordena los comandos (y por tanto los botones del widget), las líneas y las acciones de un equipo.
- <i class="fas fa-chevron-right"></i> **Plegar / desplegar**: los comandos aparecen plegados al abrir el grupo; los botones de arriba a la derecha los pliegan o despliegan todos.
- <i class="fas fa-clone"></i> **Clonar una línea**: añade una línea con el mismo objeto y las mismas acciones; al elegir el equipo, se seleccionan sus comandos con el mismo nombre y los mismos valores.
- <i class="fas fa-clone"></i> **Clonar un comando**: duplica el comando completo, con el nombre «… (copie)».
- <i class="fas fa-cog"></i> **Configuración**: abre la configuración Jeedom del comando (historial, tipo genérico, alertas, visualización), una vez guardado el grupo.

Una línea cuyo equipo, comando o escenario se ha eliminado se muestra **en rojo**. No se puede guardar mientras haya una línea incompleta; un mensaje indica el comando y la línea que hay que corregir.

Comandos de un grupo
===

| Comando | Tipo | Función |
|---|---|---|
| *uno por comando info* | info | Valor «alors» o «sinon» según la regla |
| *uno por comando acción* | action | Ejecuta las líneas del comando, en orden |
| Refrescar | action | Recalcula todos los comandos info del grupo |

Ejemplo en un escenario: disparador *[Salón][Luces del salón][on] igual a ok*, acción *[Salón][Luces del salón][Encender]*.

Widget
===

Cada grupo tiene su propio widget:

![Widgets](../images/Widget.png)

- una línea por comando info, con un punto verde o rojo, el número de equipos conformes (p. ej. `2/2`) y el valor del comando;
- el **color actual** de cada luz del grupo (leído en la info de estado de su comando de color, o en una info de tipo genérico *Color de luz*);
- un botón por comando acción;
- el icono <i class="fas fa-sync-alt"></i> recalcula el estado del grupo.

El widget se actualiza en directo. Un comando oculto (icono <i class="fas fa-cog"></i> > *Mostrar*) no aparece en él.
