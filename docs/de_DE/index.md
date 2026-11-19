---
layout: default
lang: de_DE
---

Beschreibung
===

Mit Groupir lassen sich **Geräte gruppieren** (und Szenarien). Jede Gruppe, zum Beispiel „Wohnzimmerlichter", ist ein Jeedom-Gerät mit eigenen Befehlen:

- **Info-Befehle** zeigen an, ob sich die Geräte der Gruppe im erwarteten Zustand befinden (z. B. `on` ist `ok`, wenn alle Lichter eingeschaltet sind);
- **Aktionsbefehle** steuern alle Geräte auf einmal (z. B. `Einschalten` schaltet die Lichter ein und stellt Helligkeit und Farbe ein).

Diese Befehle funktionieren wie jeder andere Jeedom-Befehl: Szenarien, Widgets, Verlauf, Warnungen...

![Liste der Gruppen](../images/Groupes.png)

Plugin-Konfiguration
===

Aktivieren Sie das Plugin nach der Installation aus dem Market. **Es ist keine globale Konfiguration erforderlich**: Jede Gruppe wird über ihr Gerät im Reiter *Zuordnungen* konfiguriert.

Die Funktion **cron** ist aktiv: Jede Minute berechnet das Plugin die Gruppen neu, deren Info-Befehle vom Zustand eines Szenarios abhängen. Die Log-Stufe *Debug* protokolliert jede Neuberechnung und jede ausgeführte Aktion, was bei der Fehlersuche hilft.

Die Oberfläche ist auf Französisch, Englisch, Deutsch, Spanisch und Italienisch verfügbar.

Eine Gruppe erstellen
===

Klicken Sie auf **Gruppe hinzufügen**, geben Sie einen Namen ein und konfigurieren Sie dann die beiden Reiter.

Reiter „Allgemein"
---

Name, übergeordnetes Objekt, Kategorie, Optionen *Aktivieren* / *Sichtbar* und Beschreibung, wie bei jedem Gerät.

Der Block **Verknüpfte Szenarien** listet:

- die Szenarien, die **diese Gruppe verwenden** (einen ihrer Befehle als Auslöser, Bedingung oder Aktion);
- die Szenarien, die **von dieser Gruppe gesteuert werden** (von einem ihrer Aktionsbefehle gestartet, gestoppt, aktiviert oder deaktiviert).

Ein Klick öffnet das Szenario.

![Reiter Allgemein](../images/Groupe.General.png)

Reiter „Zuordnungen"
---

Die Gruppe enthält **Befehle**, von denen jeder eine Untergruppe von Geräten ist:

- **Info-Befehl** (blaues Abzeichen): berechnet einen Zustand aus den Geräten;
- **Aktionsbefehl** (oranges Abzeichen): führt Aktionen auf den Geräten aus.

Geben Sie dem Befehl einen **Namen**: Er wird zum Namen des Gruppenbefehls. Fügen Sie dann mit der Schaltfläche **Gerät** eine Zeile pro Gerät hinzu (oder mit der Schaltfläche **Szenario** eine Zeile pro Szenario). Jede Zeile wird von links nach rechts ausgefüllt: **Objekt**, **Gerät**, **Befehl**. Jede Liste hat ein Feld *Filter*; ein Filter, der nur ein Ergebnis übrig lässt, wählt es automatisch aus, und ein bereits im Befehl verwendetes Gerät wird nicht mehr angeboten.

![Reiter Zuordnungen](../images/Correspondances.png)

### Info-Befehl

Für jedes Gerät: der Info-Befehl, eine Bedingung (*gleich*, *ungleich*, *größer als*, *enthält*...) und der **erwartete Wert** (der aktuelle Wert wird als Hinweis angezeigt).

Die **vom Befehl zurückgegebenen Werte** hängen von einer Regel ab:

- **wenn alle übereinstimmen** (Standard): alle Zeilen müssen übereinstimmen;
- **wenn mindestens eine übereinstimmt**: z. B. „mindestens ein Licht an";
- **wenn mindestens N übereinstimmen**: z. B. mindestens 2 von 4 Fenstern offen.

Der Befehl nimmt dann den Wert **alors** (dann, Standard `ok`) an, andernfalls den Wert **sinon** (sonst, Standard `ko`). Sind beide Werte numerisch (z. B. `1` / `0`), ist der Befehl numerisch und kann als Diagramm historisiert werden.

Eine **Szenario**-Zeile prüft den Zustand eines Szenarios: **ist aktiv**, **ist inaktiv**, **läuft** oder **ist gestoppt**. Jeedom meldet keine Zustandsänderungen von Szenarien: Diese Zeilen werden jede Minute neu berechnet (oder sofort mit dem Befehl *Aktualisieren*).

![Info-Befehl](../images/Correspondances.Info.png)

### Aktionsbefehl

Für jedes Gerät: ein oder mehrere Aktionsbefehle, die der Reihe nach ausgeführt werden. Die Schaltfläche **+ Commande** verkettet mehrere Aktionen auf demselben Gerät (z. B. `On`, dann `Helligkeit` auf 254, dann `Farbe`).

Das Feld **Wert** passt sich dem Befehlstyp an: Zahl (mit dem erlaubten Bereich als Hinweis, z. B. `153 bis 500`, und auf diesen Bereich begrenzter Eingabe), Farbe, Liste oder Nachricht. Für einen Befehl ohne Wert (z. B. `On`) wird kein Feld angezeigt.

Eine **Szenario**-Zeile startet, stoppt, aktiviert oder deaktiviert ein Szenario, in der Reihenfolge mit den Geräten (z. B. die Lichter einschalten und dann das Szenario „Abendstimmung" starten).

Die **Pause zwischen zwei Aktionen** (0 bis 30 Sekunden) verteilt die Aktionen zeitlich: nützlich für Lampen, die eine direkt nach dem Einschalten gesendete Farbe ignorieren.

![Aktionsbefehl](../images/Correspondances.Action.png)

### Befehle organisieren

- <i class="fas fa-grip-vertical"></i> **Ziehen und Ablegen**: ordnet die Befehle (und damit die Widget-Schaltflächen), die Zeilen und die Aktionen eines Geräts neu.
- <i class="fas fa-chevron-right"></i> **Ein- / Ausklappen**: Die Befehle sind beim Öffnen der Gruppe eingeklappt; die Schaltflächen oben rechts klappen alle ein oder aus.
- <i class="fas fa-clone"></i> **Zeile klonen**: fügt eine Zeile mit demselben Objekt und denselben Aktionen hinzu; sobald das Gerät gewählt ist, werden seine gleichnamigen Befehle mit denselben Werten ausgewählt.
- <i class="fas fa-clone"></i> **Befehl klonen**: dupliziert den gesamten Befehl unter dem Namen „… (copie)".
- <i class="fas fa-cog"></i> **Konfiguration**: öffnet die Jeedom-Konfiguration des Befehls (Verlauf, generischer Typ, Warnungen, Anzeige), sobald die Gruppe gespeichert ist.

Eine Zeile, deren Gerät, Befehl oder Szenario gelöscht wurde, wird **rot** angezeigt. Das Speichern wird abgelehnt, solange eine Zeile unvollständig ist; eine Meldung nennt den Befehl und die zu korrigierende Zeile.

Befehle einer Gruppe
===

| Befehl | Typ | Funktion |
|---|---|---|
| *einer pro Info-Befehl* | info | Wert „alors" oder „sinon" gemäß der Regel |
| *einer pro Aktionsbefehl* | action | Führt die Zeilen des Befehls der Reihe nach aus |
| Aktualisieren | action | Berechnet alle Info-Befehle der Gruppe neu |

Beispiel in einem Szenario: Auslöser *[Wohnzimmer][Wohnzimmerlichter][on] gleich ok*, Aktion *[Wohnzimmer][Wohnzimmerlichter][Einschalten]*.

Widget
===

Jede Gruppe hat ihr eigenes Widget:

![Widgets](../images/Widget.png)

- eine Zeile pro Info-Befehl mit einem grünen oder roten Punkt, der Anzahl übereinstimmender Geräte (z. B. `2/2`) und dem Wert des Befehls;
- die **aktuelle Farbe** jedes Lichts der Gruppe (aus der Status-Info seines Farbbefehls gelesen oder aus einer Info mit dem generischen Typ *Lichtfarbe*);
- eine Schaltfläche pro Aktionsbefehl;
- das Symbol <i class="fas fa-sync-alt"></i> berechnet den Zustand der Gruppe neu.

Das Widget wird live aktualisiert. Ein ausgeblendeter Befehl (Symbol <i class="fas fa-cog"></i> > *Anzeigen*) erscheint dort nicht.
