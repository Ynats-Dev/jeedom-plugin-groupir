---
layout: default
lang: en_US
---

# Groupir changelog

# 19/11/2026 - 1.0.0

- First release on the Jeedom Market
- Info commands: Binary, Numeric or Other type (on / off by default), suggested from the selected info commands
- Numeric: average, highest, lowest, sum, median, number of matches or according to the rule; decimals and unit reused automatically
- "Check data freshness" option: devices without recent update or in timeout are ignored, with a warning in the widget and in the log
- Info command: the same device can appear on several lines (not the same info command)
- Commands can be enabled / disabled
- Editor: current value in the command header, group name shown, scenarios in a separate block (info), scenario lines can be cloned
- Display tab hidden in the command configuration (no effect on the group widget)
- Removed the "Development" section from the plugin configuration
- Code cleanup
- Interface translated into English, German, Spanish and Italian
- Documentation: "Plugin configuration" section

# 19/11/2026 - 0.5

**Group commands**
- Info command: choice of rule, "all", "at least one" or "at least N" matching devices
- Info command: the state of scenarios can count (active, inactive, running, stopped), recalculated every minute
- Action command: several actions chained on the same device (e.g. On, Brightness, Colour)
- Action command: start, stop, enable or disable scenarios
- Action command: optional pause between two actions (0 to 30 seconds)
- Slider value: the command range shown as a hint (e.g. 153 to 500) and input limited to that range

**Correspondances tab**
- Commands, lines and actions can be reordered by drag and drop
- Collapsible commands, collapsed by default
- Buttons to clone a line (commands found by name on the new device, values kept) or a whole command
- Configuration icon on each command: history, generic type, alerts, display
- Lines whose device, command or scenario has been deleted are shown in red
- Saving refused with a precise message (command and line) when a line is incomplete

**Widget and page**
- Dedicated widget: state of each info command (dot, number of matching devices), current colour of the lights, one button per action command
- General tab: "Linked scenarios" block (scenarios using the group and scenarios controlled by the group)
- Interface redesigned in the Scan.Ip style and new logo

**Performance**
- Only the commands affected by a change are recalculated
- Group state cached, widget updated live without being regenerated

**Fixes**
- Group commands could be erased when saving (development version)

# 18/11/2026 - 0.4

- The plugin becomes **Groupir** (id `groupir`) and is limited to grouping devices (formerly Simple.Scenario)
- Groups made of info commands ("ok / ko" value, customisable) and action commands, each one being a sub-group of devices
- Inline selection Object > Device > Command with filters; a device already chosen in a command is no longer offered
- Development: "Pull branch" button in the plugin configuration
