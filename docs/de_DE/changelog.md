---
layout: default
lang: de_DE
---

# Groupir-Changelog

# 19/11/2026 - 1.0.0

- Erste Veröffentlichung im Jeedom Market
- Info-Befehle: Typ Binär, Numerisch oder Andere (standardmäßig on / off), anhand der gewählten Infobefehle vorgeschlagen
- Numerisch: Durchschnitt, höchster, niedrigster Wert, Summe, Median, Anzahl der Übereinstimmungen oder gemäß der Regel; Dezimalstellen und Einheit automatisch übernommen
- Option „Aktualität der Daten prüfen“: Geräte ohne aktuelle Aktualisierung oder im Timeout werden ignoriert, mit Warnung im Widget und im Log
- Info-Befehl: dasselbe Gerät kann in mehreren Zeilen vorkommen (nicht derselbe Infobefehl)
- Befehle aktivierbar / deaktivierbar
- Editor: aktueller Wert in der Befehlskopfzeile, Gruppenname angezeigt, Szenarien in einem eigenen Block (Info), Szenario-Zeilen klonbar
- Reiter Anzeige in der Befehlskonfiguration ausgeblendet (ohne Wirkung auf das Gruppen-Widget)
- Bereich „Entwicklung" aus der Plugin-Konfiguration entfernt
- Code bereinigt
- Oberfläche ins Englische, Deutsche, Spanische und Italienische übersetzt
- Dokumentation: Abschnitt „Plugin-Konfiguration"

# 19/11/2026 - 0.5

**Gruppenbefehle**
- Info-Befehl: wählbare Regel, „alle", „mindestens eines" oder „mindestens N" übereinstimmende Geräte
- Info-Befehl: der Zustand von Szenarien kann zählen (aktiv, inaktiv, läuft, gestoppt), jede Minute neu berechnet
- Aktionsbefehl: mehrere verkettete Aktionen auf demselben Gerät (z. B. On, Helligkeit, Farbe)
- Aktionsbefehl: Szenarien starten, stoppen, aktivieren oder deaktivieren
- Aktionsbefehl: optionale Pause zwischen zwei Aktionen (0 bis 30 Sekunden)
- Schiebereglerwert: Bereich des Befehls als Hinweis (z. B. 153 bis 500) und auf diesen Bereich begrenzte Eingabe

**Reiter Correspondances**
- Befehle, Zeilen und Aktionen per Ziehen und Ablegen neu anordnen
- Einklappbare Befehle, standardmäßig eingeklappt
- Schaltflächen zum Klonen einer Zeile (Befehle per Name auf dem neuen Gerät gefunden, Werte übernommen) oder eines ganzen Befehls
- Konfigurationssymbol an jedem Befehl: Verlauf, generischer Typ, Warnungen, Anzeige
- Zeilen, deren Gerät, Befehl oder Szenario gelöscht wurde, werden rot angezeigt
- Speichern mit genauer Meldung (Befehl und Zeile) abgelehnt, wenn eine Zeile unvollständig ist

**Widget und Seite**
- Eigenes Widget: Zustand jedes Info-Befehls (Punkt, Anzahl übereinstimmender Geräte), aktuelle Farbe der Lichter, eine Schaltfläche pro Aktionsbefehl
- Reiter Allgemein: Block „Verknüpfte Szenarien" (Szenarien, die die Gruppe verwenden, und von der Gruppe gesteuerte Szenarien)
- Oberfläche im Stil von Scan.Ip überarbeitet und neues Logo

**Leistung**
- Nur die von einer Änderung betroffenen Befehle werden neu berechnet
- Gruppenzustand im Cache, Widget live aktualisiert, ohne neu erzeugt zu werden

**Korrekturen**
- Gruppenbefehle konnten beim Speichern gelöscht werden (Entwicklungsversion)

# 18/11/2026 - 0.4

- Das Plugin heißt nun **Groupir** (ID `groupir`) und beschränkt sich auf das Gruppieren von Geräten (früher Simple.Scenario)
- Gruppen aus Info-Befehlen (Wert „ok / ko", anpassbar) und Aktionsbefehlen, jeweils eine Untergruppe von Geräten
- Auswahl in der Zeile Objekt > Gerät > Befehl mit Filtern; ein bereits in einem Befehl gewähltes Gerät wird nicht mehr angeboten
- Entwicklung: Schaltfläche „Branch holen" in der Plugin-Konfiguration
