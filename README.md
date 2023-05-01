# Postpartner Suche / Szenensuche
Dieses Plugin fügt eurem Forum eine Postpartnersuche hinzu. Diese wird über eine eigene Seite aufgerufen und dort auch verwaltet. User/innen haben die Möglichkeit, egal von welchem Account aus, für all ihre Charaktere Suchen zu starten. Dafür wählen sie den entsprechenden Charakter aus, geben an in welchem Inplayzeitraum sie die neue/n Szene/n gerne hätten und was sie gerne ausspielen würden. Auch besteht die Möglichkeit, dass User pro Suche eine Szenenkapazität angeben können. Das bedeutet: wie viele Szenen sie sich noch für diesen Charakter wünschen bzw. zutrauen. Alle eigenen Szenenangebote können mit jedem Account auf der Seite bearbeitet oder gelöscht werden. Auch kann die Szenenkapazität geupdatet werden mit einem Klick, wenn eine Szene entstanden ist.<br>
Bei den Szenenangeboten werden eigene Angebote ausgeblendet. Genau wie die Angebote, für die, von einem selbst, eine Anfrage rausgeschickt wurde und diese Anfrage noch nicht beantwortet wurde. Immer wenn jemand eine neue Suche einträgt, bekommen alle Hauptaccounts einen Alert.<br>
Die Szenenangebote können, wenn vom Team aktiviert und die Kriterien erfüllt sind, nach Postingvorlieben (Länge, Tempo und Perspektive) gefiltert werden. Kriterien für das Filtern ist, dass es sich bei dem Profilfeld/Steckbrieffeld um ein Feld handelt, beidem die Auswahlmöglichkeiten vom Team vorgegeben werden.<br>
<br>
Wenn sich ein/e User/in für eine Suche interessiert, dann hat diese/r zwei Möglichkeiten: Entweder klassisch und direkt eine PN zu schreiben (man wird dann in das PN-Fenster weitergeleitet) oder per Klick sein Interesse zu bekunden. Wenn Option 2 gewählt wurde, bekommt der/die Suchende auf allen Accounts einen Index-Banner, dass sich Charakter X für eine Szene mit Charakter Y interessiert. Der/Die User/in hat dann wieder zwei Optionen: Entweder Kontakt aufzunehmen und eine Szene erstellen (per Klick auf Szene erstellen wird der/die User/in dann in die Inplaykategorie weitergeleitet und die Szenenkapazität von beiden User/innen wird geupdatet) oder die Anfrage abzulehnen. Wenn eine Anfrage abgelehnt wird, muss ein Grund angegeben werden. Dieser Grund wird dann per PN an den oder die Anfragensteller/in geschickt. 
Außerdem kann die Funktion von einem Postpartnerwürfel vom Team aktiviert werden.<br>
<br>
<b>HINWEIS:</b><br>
Das Plugin ist kompatibel mit den klassischen Profilfeldern von MyBB und/oder dem <a href="https://github.com/katjalennartz/application_ucp">Steckbrief-Plugin von Risuena</a>. Genauso kann auch das Listen-Menü angezeigt werden, wenn man das <a href="https://github.com/ItsSparksFly/mybb-lists">Automatische Listen-Plugin von sparks fly</a> verwendet. Beides muss nur vorher eingestellt werden.

# Vorrausetzung
- Der <a href="https://www.mybb.de/erweiterungen/18x/plugins-verschiedenes/enhanced-account-switcher/" target="_blank">Accountswitcher</a> von doylecc <b>muss</b> installiert sein.

# Empfehlungen
- <a href="https://github.com/MyBBStuff/MyAlerts" target="_blank">MyAlerts</a> von EuanT (ein Alert - jemand hat sich neu in die Suche eingetragen)
- Eingebundene Icons von Fontawesome (kann man sonst auch in der Sprachdatei ändern)

# Datenbank-Änderungen
hinzugefügte Tabelle:
- PRÄFIX_postpartners
- PRÄFIX_postpartners_alerts

# Einstellungen - Postpartnersuche
- Erlaubte Gruppen
- Gästeberechtigung
- Avatar verstecken
- Standard-Avatar
- Profilfeldsystem
- Kurzbeschreibung
- Postinglänge
- Postingfrequenz
- Postperspektive
- Filtermöglichkeit
- Postpartnerwürfel
- Listen PHP
- Listen Menü
- Listen Menü Template
- Inplay-Kategorie<br><br>
<b>HINWEIS:</b><br>
Das Plugin ist kompatibel mit den klassischen Profilfeldern von MyBB und/oder dem <a href="https://github.com/katjalennartz/application_ucp">Steckbrief-Plugin von Risuena</a>. Genauso kann auch das Listen-Menü angezeigt werden, wenn man das <a href="https://github.com/ItsSparksFly/mybb-lists">Automatische Listen-Plugin von sparks fly</a> verwendet. Beides muss nur vorher eingestellt werden.

# Task - Postpartnersuche
Löscht automatisch Postpartnersuchen, bei den Szenenkapazitäte ausgeschöpft ist.

# Neue Template-Gruppe innerhalb der Design-Templates
- Postpartnersuche

# Neue Templates (nicht global!)
- postpartner
- postpartner_add
- postpartner_character
- postpartner_dice
- postpartner_dice_bit
- postpartner_edit
- postpartner_filter
- postpartner_header
- postpartner_header_rejectRequest
- postpartner_none
- postpartner_own
- postpartner_ownsearch<br><br>
<b>HINWEIS:</b><br>
Alle Templates wurden ohne Tabellen-Struktur gecodet. Das Layout wurde auf ein MyBB Default Design angepasst.

# Template Änderungen - neue Variablen
- header - {$postpartner_header}

# Neues CSS - postpartner.css
Es wird automatisch in jedes bestehende und neue Design hinzugefügt. Man sollte es einfach einmal abspeichern, bevor man dies im Board mit der Untersuchungsfunktion bearbeiten will, da es dann passieren kann, dass das CSS für dieses Plugin in einen anderen Stylesheet gerutscht ist, obwohl es im ACP richtig ist.

# Links
- euerforum.de/misc.php?action=postpartner

# Weiter Profilfelder
In dem Tpl postpartner_character, wo die einzelnen Szenenangebote definiert werden, können auch weitere Profilfelder/Steckbrieffelder ausgelesen werden. Dafür benötigt man nur die Variable: $searchs['X']<br>
<b>Profilfeld:</b> $searchs['fidX']<br>
<b>Steckbrieffeld:</b> $searchs['Identifikator']

# Demo
<img src="https://www.bilder-hochladen.net/files/big/m4bn-hd-5fc5.png">
<img src="https://www.bilder-hochladen.net/files/big/m4bn-ha-b983.png">
<img src="https://www.bilder-hochladen.net/files/m4bn-h9-5eee.png">
<img src="https://www.bilder-hochladen.net/files/big/m4bn-hc-bfcb.png">
<img src="https://www.bilder-hochladen.net/files/m4bn-hb-a7de.png">
<img src="https://www.bilder-hochladen.net/files/m4bn-h8-678b.png">
