# 🔌 Zwischenstecker (Pluggable Switch)

[![Home](https://img.shields.io/badge/Home-wilkware.de-0b1830.svg?style=flat-square)](https://wilkware.de/module/zwischenstecker/)
[![Version](https://img.shields.io/badge/Symcon-PHP--Modul-red.svg?style=flat-square)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
[![Product](https://img.shields.io/badge/Symcon%20Version-8.1-blue.svg?style=flat-square)](https://www.symcon.de/produkt/)
[![Version](https://img.shields.io/badge/Modul%20Version-2.0.20260203-orange.svg?style=flat-square)](https://github.com/Wilkware/RfxPluggableSwitch2COM)
[![License](https://img.shields.io/badge/License-CC%20BY--NC--SA%204.0-green.svg?style=flat-square)](https://creativecommons.org/licenses/by-nc-sa/4.0/)
[![Actions](https://img.shields.io/github/actions/workflow/status/wilkware/PluggableSwitch/ci.yml?branch=main&label=CI&style=flat-square)](https://github.com/Wilkware/PluggableSwitch/actions)


Das Modul erlaubt das Schalten von elektrischen Geräten einfach und zeitgesteuert für verschiedene Szenarien mit einer steckbaren Steckdose (Zwischenstecker).  

## Inhaltverzeichnis

1. [Funktionsumfang](#user-content-1-funktionsumfang)
2. [Voraussetzungen](#user-content-2-voraussetzungen)
3. [Installation](#user-content-3-installation)
4. [Einrichtung](#user-content-4-einrichtung)
5. [Statusvariablen](#user-content-5-statusvariablen)
6. [Darstellungen](#user-content-6-darstellungen)
7. [Visualisierung](#user-content-7-visualisierung)
8. [Befehlsreferenz](#user-content-8-befehlsreferenz)
9. [Versionshistorie](#user-content-9-versionshistorie)

### 1. Funktionsumfang

* Manuelles und Zeitgesteuertes Schalten von Zwischensteckern (Steckdosen)
* Anlegen und verknüpfen eines Zeitplanes (Wochenprogramm)
* Schalten von einzelenen oder mehreren Steckdosen (Gruppe)
* On-the-fly Umbennenung des Moduls, z.B. bei Änderung des Verwendungszwecks
* Integration in neue Tile-Visualisierung

### 2. Voraussetzungen

* Symcon ab Version 8.1

### 3. Installation

* Über den Modul Store das Modul _Zwischenstecker_ installieren.
* Alternativ über das Modul Control folgende URL hinzufügen.  
`https://github.com/Wilkware/PluggableSwitch` oder `git://github.com/Wilkware/PluggableSwitch.git`

### 4. Einrichtung

* Unter 'Instanz hinzufügen' ist das _Zwischenstecker_-Modul unter dem Hersteller '(Geräte)' aufgeführt.

__Konfigurationsseite__:

Einstellungsbereich:

> 🔌 Steckdose(n) ...

Name                 | Beschreibung
-------------------- | ---------------------------------
Geräteanzahl         | 'Ein Gerät' oder 'Mehrere Geräte' - Umschalten zwischen Variablenauswahl und Variablenliste.
Schaltvariable*      | Zielvariable, die bei hinreichender Bedingung geschalten wird (true). *[Ein Gerät]
Schaltvariablen*     | Zielvariablen, welche alle bei hinreichender Bedingung geschalten werden (true). *[Mehrere Geräte]

> ⏱️ Zeitsteuerung ...

Name                 | Beschreibung
-------------------- | ---------------------------------
Zeitplan             | Wochenprogram, welches die Steckdose(n) zeitgesteuert an- bzw. ausschaltet.
ZEITPLAN HINZUFÜGEN  | Button zum Erzeugen und Hinzufügen eines Wochenprogrammes.

### 5. Statusvariablen

Die Statusvariablen werden automatisch angelegt. Das Löschen einzelner kann zu Fehlfunktionen führen.

Name                 | Typ     | Beschreibung
-------------------- | ------- | ------------------------------
Beschriftung         | String  | Bezeichnung des Zwischensteckers, eine Änderung benennt die Instanz um (On-the-fly Umbenennung)

### 6. Darstellungen

Die Darstellungen werden direkt an den Statusvariablen hinterlegt, es werden keine Profile angelegt.

Variable             | Darstellung   | Werte
-------------------- | ------------- | ------------------------------
Beschriftung         | Werteingabe   | Freitext (einzeilig)

### 7. Visualisierung

Das Modul kann direkt als Link in die TileVisu eingehangen werden.  
Als Kachel wird der Zustand der Steckdose(n) ähnlich einem normalen Licht-Schalter dargestellt
Ein kleiner Indikator (rechts unten) zeigt an, ob ein aktives Zeitprogramm hinterlegt ist (grün).

### 8. Befehlsreferenz

Das Modul stellt keine direkten Funktionsaufrufe zur Verfügung.

### 9. Versionshistorie

v2.0.20260203

* _NEU_: Umstellung von Profilen auf Darstellungen
* _NEU_: Modulversion wird in Quellcodesektion angezeigt
* _NEU_: Projektumstrukturierung hin zu einer globalen CI/CD-Pipeline
* _NEU_: Umstellung auf IPSModuleStrict
* _NEU_: Kompatibilität auf IPS 8.1 hoch gesetzt
* _FIX_: Bibliotheksfunktionen angeglichen

v1.1.20241118

* _FIX_: Zeitplanindikator durch Timersymbol ersetzt
* _FIX_: Positionierung Zeitplansymbol optimiert
* _NEU_: Inventarnummer eingeführt

v1.0.20241115

* _NEU_: Initialversion

## Entwickler

Seit nunmehr über 10 Jahren fasziniert mich das Thema Haussteuerung. In den letzten Jahren betätige ich mich auch intensiv in der Symcon Community und steuere dort verschiedenste Skript und Module bei. Ihr findet mich dort unter dem Namen @pitti ;-)

[![GitHub](https://img.shields.io/badge/GitHub-@wilkware-181717.svg?style=for-the-badge&logo=github)](https://wilkware.github.io/)

## Spenden

Die Software ist für die nicht kommerzielle Nutzung kostenlos, über eine Spende bei Gefallen des Moduls würde ich mich freuen.

[![PayPal](https://img.shields.io/badge/PayPal-spenden-00457C.svg?style=for-the-badge&logo=paypal)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=8816166)

## Lizenz

Namensnennung - Nicht-kommerziell - Weitergabe unter gleichen Bedingungen 4.0 International

[![Licence](https://img.shields.io/badge/License-CC_BY--NC--SA_4.0-EF9421.svg?style=for-the-badge&logo=creativecommons)](https://creativecommons.org/licenses/by-nc-sa/4.0/)
