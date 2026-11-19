---
layout: default
lang: en_US
---

Description
===

Groupir lets you **group devices** (and scenarios). Each group, for example "Living room lights", is a Jeedom device with its own commands:

- **info commands**, which tell whether the devices of the group are in the expected state (e.g. `on` is `ok` when all the lights are on);
- **action commands**, which control all the devices at once (e.g. `Turn on` switches the lights on and sets their brightness and colour).

These commands work like any other Jeedom command: scenarios, widgets, history, alerts...

![Group list](../images/Groupes.png)

Plugin configuration
===

After installing it from the Market, enable the plugin. **No global configuration is needed**: each group is configured from its device, in the *Mappings* tab.

The **cron** feature is enabled: every minute, the plugin recalculates the groups whose info commands depend on the state of a scenario. The *Debug* log level details each recalculation and each action run, which helps with troubleshooting.

The interface is available in French, English, German, Spanish and Italian.

Creating a group
===

Click **Add a group**, give it a name, then configure the two tabs.

"General" tab
---

Name, parent object, category, *Enable* / *Visible* options and description, as for any device.

The **Linked scenarios** block lists:

- the scenarios that **use this group** (one of its commands as a trigger, condition or action);
- the scenarios **controlled by this group** (started, stopped, enabled or disabled by one of its action commands).

Click a scenario to open it.

![General tab](../images/Groupe.General.png)

"Mappings" tab
---

The group contains **commands**, each one being a sub-group of devices:

- **Info command** (blue badge): computes a state from the devices;
- **Action command** (orange badge): runs actions on the devices.

Give the command a **name**: it becomes the name of the group command. Then add one line per device with the **Device** button (or per scenario with the **Scenario** button). Each line is filled from left to right: **Object**, **Device**, **Command**. Every list has a *Filter* field; a filter that leaves a single result selects it automatically, and a device already used in the command is no longer offered.

![Mappings tab](../images/Correspondances.png)

### Info command

For each device: the info command, a condition (*equal to*, *different from*, *greater than*, *contains*...) and the **expected value** (the current value is shown as a hint).

The **values returned by the command** depend on a rule:

- **if all match** (default): every line must match;
- **if at least one matches**: e.g. "at least one light on";
- **if at least N match**: e.g. at least 2 windows open out of 4.

The command then takes the **then** value (default `ok`), otherwise the **else** value (default `ko`). If both values are numeric (e.g. `1` / `0`), the command is numeric and can be historised as a graph.

A **Scenario** line checks the state of a scenario: **is active**, **is inactive**, **is running** or **is stopped**. Jeedom does not report scenario state changes, so these lines are recalculated every minute (or immediately with the *Refresh* command).

![Info command](../images/Correspondances.Info.png)

### Action command

For each device: one or more action commands, run in order. The **+ Commande** button chains several actions on the same device (e.g. `On`, then `Brightness` at 254, then `Colour`).

The **Value** field adapts to the command type: number (with the allowed range as a hint, e.g. `153 to 500`, and input limited to that range), colour, list or message. For a command without a value (e.g. `On`), no field is shown.

A **Scenario** line starts, stops, enables or disables a scenario, in order with the devices (e.g. turn the lights on, then start the "Evening mood" scenario).

The **pause between two actions** (0 to 30 seconds) spaces out each action: useful for bulbs that ignore a colour sent right after being switched on.

![Action command](../images/Correspondances.Action.png)

### Organising commands

- <i class="fas fa-grip-vertical"></i> **Drag and drop**: reorders the commands (and therefore the widget buttons), the lines and the actions of a device.
- <i class="fas fa-chevron-right"></i> **Collapse / expand**: commands are collapsed when the group opens; the buttons at the top right collapse or expand them all.
- <i class="fas fa-clone"></i> **Clone a line**: adds a line with the same object and the same actions; once the device is chosen, its commands with the same names are selected with the same values.
- <i class="fas fa-clone"></i> **Clone a command**: duplicates the whole command, named "… (copie)".
- <i class="fas fa-cog"></i> **Configuration**: opens the Jeedom configuration of the command (history, generic type, alerts, display), once the group has been saved.

A line whose device, command or scenario has been deleted is shown **in red**. Saving is refused while a line is incomplete, with a message giving the command and the line to fix.

Group commands
===

| Command | Type | Role |
|---|---|---|
| *one per info command* | info | "then" or "else" value according to the rule |
| *one per action command* | action | Runs the lines of the command, in order |
| Refresh | action | Recalculates all the info commands of the group |

Example in a scenario: trigger *[Living room][Living room lights][on] equal to ok*, action *[Living room][Living room lights][Turn on]*.

Widget
===

Each group has its own widget:

![Widgets](../images/Widget.png)

- one line per info command, with a green or red dot, the number of matching devices (e.g. `2/2`) and the value of the command;
- the **current colour** of each light of the group (read from the state info of its colour command, or from an info with the *Light colour* generic type);
- one button per action command;
- the <i class="fas fa-sync-alt"></i> icon recalculates the state of the group.

The widget updates live. A hidden command (<i class="fas fa-cog"></i> icon > *Display*) does not appear in it.
