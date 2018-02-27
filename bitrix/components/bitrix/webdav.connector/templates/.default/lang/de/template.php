<?
$MESS["WD_WEBFOLDER_TITLE"] = "Als einen Web-Ordner verbinden";
$MESS["WD_USEADDRESS"] = "Folgende Adresse für Verbindung benutzen:";
$MESS["WD_CONNECT"] = "Verbinden";
$MESS["WD_SHAREDDRIVE_TITLE"] = "Als ein Netzwerklaufwerk verbinden";
$MESS["WD_REGISTERPATCH"] = "Die aktuellen Sicherheitseinstellungen erfordern, dass Sie <a href=\"#LINK#\">Änderungen im Registrierungs-Editor vornehmen</a>, um die Verbindung mit einem Netzwerklaufwerk herzustellen.";
$MESS["WD_NOTINSTALLED"] = "Diese Komponente ist in Ihrem Operationssystem standardmäßig nicht installiert. Sie können  sie<a href=\"#LINK#\">hier herunterladen</a>.";
$MESS["WD_WIN7HTTPSCMD"] = "Um die Verbindung mit der Bibliothek als einem Netzwerklaufwerk via HTTPS/SSL herzustellen, führen Sie den Befehl aus: <b>Start > Ausführen > cmd</b>.";
$MESS["WD_CONNECTION_MANUAL"] = "<a href=\"#LINK#\"> Verbindungsanweisung</a>.";
$MESS["WD_TIP_FOR_2008"] = "Lesen Sie bitte diesen <a href=\"#LINK#\">Hinweis</a> wenn Sie den Microsoft Windows Server 2008 benutzen.";
$MESS["WD_USECOMMANDLINE"] = "Um die Bibliothek als Netzwerklaufwerk über das Protokoll HTTPS/SSL anzubinden, benutzen Sie <b>Start > Ausführen > cmd</b>. Geben Sie folgende Befehle in der Befehlszeile ein:";
$MESS["WD_EMPTY_PATH"] = "Der Pfad zum Netzwerk wurde nicht angegeben.";
$MESS["WD_CONNECTION_TITLE"] = "Dokumentenbibliothek als Netzwerklaufwerk anbinden";
$MESS["WD_MACOS_TITLE"] = "Dokumentenbibliothek in Mac OS X anbinden";
$MESS["WD_CONNECTOR_HELP_MAPDRIVE"] = "<h3>Netzlaufwerk-Verbindung</h3>
<ul>
<p>Um die Dokumentenbibliothek als Netzlaufwerk über den Dateimanager (Windows Explorer) einzubinden:</p> 
<li>Starten Sie den Datei-Manager (Explorer);
<br><a href=\"javascript:ShowImg('#TEMPLATEFOLDER#/images/de/network_add_4.png',612,498,'Eine Netzweradresse hinzufügen');\">
<img width=\"250\" height=\"185\" border=\"0\" src=\"#TEMPLATEFOLDER#/images/de/network_add_1_sm.png\" style=\"cursor: pointer;\" alt=\"Bild vergrößern\" /></a></li>
<li>Wählen Sie im Menü den Punkt <b>Service >Netzlaufwerk verbinden</b> aus. Es öffnet sich das Dialogfenster zur Verbindung des Netzlaufwerks:</li>
<li>Im Feld <b>Laufwerk</b> geben Sie einen Buchstaben für den  Ordner an, mit dem Verbindung hergestellt werden soll;</li>
<li>Im Feld <b>Ordner</b> geben Sie Pfad zur Bibliothek ein: http://&lt;Ihr_Server&gt;/docs/shared/. Wenn der Ordner bei jedem Systemstart zur Vorschau angeschaltet werden soll, markieren Sie <b>Beim Systemstart wiederherstellen</b>;</li>
<li>Drücken Sie auf Fertigstellen. Wenn sich das Dialogfenster des Operationssystems zur Autorisierung öffnet, geben Sie die Autorisierungsdaten für den Server ein. </li>
</ul>
</p>
<p>Später kann der Ordner entweder mit dem Windows Explorer, wo der Ordner als einzelnes Laufwerk dargestellt wird, oder mit einem beliebigen Dateimanager geöffnet werden.</p>";
$MESS["WD_CONNECTOR_HELP_OSX"] = "<h3>Bibliothek-Einbindung in Mac OS, Mac OS X</h3>
<p>Um die Bibliothek einzubinden:</p>
<ul>
<li>Öffnen Sie <i>Finder Go->Connect to Server command</i>;</li>
<li>Im Feld <b>Server Address</b> geben Sie die Adresse der Bibliothek ein:</p>
<p><a href=\"javascript:ShowImg('#TEMPLATEFOLDER#/images/de/macos.png',465,550,'Mac OS X');\">
<img width=\"235\" height=\"278\" border=\"0\" src=\"#TEMPLATEFOLDER#/images/de/macos_sm.png\" style=\"cursor: pointer;\" alt=\"Bild vergrößern\" /></a></li>
</ul>";
$MESS["WD_CONNECTOR_HELP_WEBFOLDERS"] = "<h3>Einbindung über die Netzwerklaufwerk-Komponente (web-folders)</h3>
<p>Bevor Sie die Dokumentenbibliothek einbinden, vergewissern Sie sich, dass <a href=\"#URL_HELP##oswindowsreg\">Änderungen im Registrierungs-Editor vorgenommen wurden</a> und <a href=\"#URL_HELP##oswindowswebclient\">der Service WebClient gestartet</a> ist.</p>
<p>Um die Dokumentenbibliothek auf diese Weise einzubinden, ist die Netzwerklaufwerk-Komponente erforderlich. Wünschenswert ist die Installation der neuesten Software für die Netzwerklaufwerk auf dem Kunden-PC <a href=\"http://www.microsoft.com/downloads/details.aspx?displaylang=ru&FamilyID=17c36612-632e-4c04-9382-987622ed1d64\" target=\"_blank\">auf die Website von Mikrosoft wechseln</a> ). </p>
<ul>
<li>Starten Sie den Datei-Manager (Explorer);</li>
<li>Wählen Sie im Menü den Punkt <b>Service &gt; Netzlaufwerk verbinden </b>aus;</li>
<li>Mit Hilfe des Links <b>Verbindung mit einer Website herstellen, auf der Sie Dokumente und Bilder speichern können</b> starten Sie den Assistenten zum <b>Hinzufügen eines Netzwerkes</b>:</p> 
<p><a href=javascript:ShowImg('#TEMPLATEFOLDER#/images/de/network_add_1.png',630,458,'Netzlaufwerk verbinden');\">
<img width=\"250\" height=\"182\" border=\"0\" src=\"#TEMPLATEFOLDER#/images/de/network_add_1_sm.png\" style=\"cursor: pointer;\" alt=\"Bild vergrößern\" /></a></b>.</li>
<li>Drücken Sie auf die Schaltfläche <b>Weiter</b>, es öffnet sich das zweite Fenster des <b>Assistenten</b>;</li>
<li>Aktivieren Sie in diesem Fenster die Position <b>Eine benutzerdefinierte Netzwerkadresse auswählen</b>, drücken Sie auf die Schaltfläche <b>Weiter</b>. Es öffnet sich der nächste Schritt des <b>Assistenten</b>:
<p><a href=javascript:ShowImg('#TEMPLATEFOLDER#/images/de/network_add_4.png',612,498,'Eine Netzwekadresse hinzufügen');\">
<img width=\"250\" height=\"204\" border=\"0\" src=\"#TEMPLATEFOLDER#/images/de/network_add_4_sm.png\" style=\"cursor: pointer;\" alt=\"Bild vergrößern\" /></a></li>
<li>Im Feld <b>Internet- oder Netzwerkadresse</b> geben Sie URL des Ordners, mit dem Verbindung hergestellt werden soll, wie folgt ein: http://&lt;Ihr_Server&gt;/docs/shared/</i>;</li>
<li>Drücken Sie auf die Schaltfläche <b>Next</b>. Wenn sich das Fenster zur Autorisierung öffnet, geben Sie hier die Autorisierungsdaten für den Server ein.</li>
</ul>

<p>Um dann den Ordner öffnen zu können, führen Sie den Befehl aus: <b>Start > Netzwerk > Ordnername</b>.</p>";
?>