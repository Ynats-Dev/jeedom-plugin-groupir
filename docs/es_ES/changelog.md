---
layout: default
lang: es_ES
---

# Registro de cambios de Groupir

# 19/11/2026 - 1.0.0

- Primera versión publicada en el Market de Jeedom
- Eliminada la sección «Desarrollo» de la configuración del plugin
- Limpieza del código
- Interfaz traducida al inglés, alemán, español e italiano
- Documentación: sección «Configuración del plugin»

# 19/11/2026 - 0.5

**Comandos del grupo**
- Comando info: regla a elegir, «todos», «al menos uno» o «al menos N» equipos que coinciden
- Comando info: el estado de escenarios puede contar (activo, inactivo, en curso, detenido), recalculado cada minuto
- Comando acción: varias acciones encadenadas sobre un mismo equipo (p. ej. On, Brillo, Color)
- Comando acción: iniciar, detener, activar o desactivar escenarios
- Comando acción: pausa opcional entre dos acciones (de 0 a 30 segundos)
- Valor de un deslizador: rango del comando como indicación (p. ej. 153 a 500) y entrada limitada a ese rango

**Pestaña Correspondances**
- Comandos, líneas y acciones reordenables mediante arrastrar y soltar
- Comandos plegables, plegados por defecto
- Botones para clonar una línea (comandos encontrados por su nombre en el nuevo equipo, valores conservados) o un comando completo
- Icono de configuración en cada comando: historial, tipo genérico, alertas, visualización
- Las líneas cuyo equipo, comando o escenario se ha eliminado se muestran en rojo
- Guardado rechazado con un mensaje preciso (comando y línea) si una línea está incompleta

**Widget y página**
- Widget propio: estado de cada comando info (punto, número de equipos conformes), color actual de las luces, un botón por comando acción
- Pestaña General: bloque «Escenarios vinculados» (escenarios que usan el grupo y escenarios controlados por el grupo)
- Interfaz renovada al estilo de Scan.Ip y nuevo logotipo

**Rendimiento**
- Solo se recalculan los comandos afectados por un cambio
- Estado del grupo en caché, widget actualizado en directo sin regenerarse

**Correcciones**
- Los comandos del grupo podían borrarse al guardar (versión de desarrollo)

# 18/11/2026 - 0.4

- El plugin pasa a llamarse **Groupir** (id `groupir`) y se limita a agrupar equipos (antes Simple.Scenario)
- Grupos formados por comandos info (valor «ok / ko», personalizable) y comandos acción, cada uno de ellos un subgrupo de equipos
- Selección en línea Objeto > Equipo > Comando con filtros; un equipo ya elegido en un comando deja de proponerse
- Desarrollo: botón «Descargar la rama» en la configuración del plugin
