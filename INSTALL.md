# Choose your Language

- [English](#english)
- [Deutsch](#deutsch)


# <a name="english"></a>ENGLISH #

## 1. Installation

Unzip the zip-file into any directory. Please unzip the file with substructures.
	
Change to the folder "config" and open the file "config.inc.php". In this you have to define
the access parameter for your OXID database. Please change the values for

- DatabaseHost
- DatabasePort
- DatabaseName
- DatabaseUser
- DatabasePass

according to your OXID eShop database.
	
## 2. Settings
Open Piwik, switch to Settings and activate the plugin "OxidAnalysis". If necessary change 
additionally the currency symbol in your piwik settings. 

## 3. Use
Switch to the dashboard and add the widgets you like to see on the dashboard.
	

# <a name="deutsch"></a>DEUTSCH #

## 1. Installation ##
 
Entpacken Sie die Zip-Datei in ein beliebiges Verzeichnis so, dass alle Verzeichnisstrukturen erhalten bleiben.
	
Wechseln Sie in das Verzeichnis "config" und öffnen Sie dort die Datei "config.inc.php". In dieser Datei werden die Zugangsdaten 
für die Datenbank hinterlegt. Passen Sie die Werte für

- DatabaseHost
- DatabasePort
- DatabaseName
- DatabaseUser
- DatabasePass

entsprechend Ihrer OXID eShop Datenbank an.
	
Laden Sie anschließend das gesamte Verzeichnis "OxidAnalysis" in den Ordner "plugins". Anschließend muss auf dem Server das 
Verzeichnis unterhalt von plugins zu sehen sein.

## 2. Einstellungen ##

Öffnen Sie Piwik, wechseln Sie in den Bereich Einstellungen und aktivieren Sie das Plugin "OxidAnalysis". Um die Umsatzzahlen 
in der richtigen Währung zu bekommen, sollte auch noch die Einstellung des Währungssymbols kontrolliert werden.

## 3. Verwendung ##

Wechseln Sie auf das Dashboard und fügen Sie nun die verschiedenen Widgets hinzu.


Erläuterung der Ansichten
--------------------------
	Vorbemerkung
		Das OXID eSales Plugin für Piwik nimmt keine Veränderungen an der Datenbank vor.
		Alle Zugriffe auf die OXID Datenbank finden nur lesend statt. Daher genügt es
		dem Plugin einen lesenden (R/O) Login zu geben.
	
	Umsatz

	
	Versand offen
		Hier werden alle Bestellungen angezeigt, die versendet werden k�nnen.
		Die Selektion erfolgt aufgund folgende Kriterien:
		- Bezahldatum vorhanden oder auf Rechnung (conf->PaymentInvoice)
		- Versanddatum = 0000-00-00 00:00:00
		- Nicht storniert
	
	Vorkasse offen
		Hier werden alle Bestellungen angezeigt, die mit Vorkasse bestellt wurden, bei
		denen aber kein Zahlungseingang vorhanden ist
		Die Selektion erfolgt aufgund folgende Kriterien:
		- Zahlungsart = Vorauskasse (oxidpayadvance)
		- Bezahldatum = 0000-00-00 00:00:00
		- Nicht storniert
	
	Nachnahme offen
		Hier werden alle Bestellungen angezeigt, die mit Nachnahme bestellt wurden, die
		bereits versandt wurden, bei denen aber noch kein Zahlungseingang vorhanden ist
		Die Selektion erfolgt aufgund folgende Kriterien:
		- Zahlungsart = Nachnahme (oxidcashondel)
		- Versanddatum != 0000-00-00 00:00:00
		- Bezahldatum = 0000-00-00 00:00:00
		- Nicht storniert

	Rechnung offen
		Diese Ansicht zeigt alle Bestellungen mit der Zahlungsart "Rechnung", die versandt, 
		aber noch bezahlt wurden.
		Die Selektion erfolgt aufgund folgende Kriterien:
		- Zahlungsart = Nachnahme (oxidinvoice)
		- Versanddatum != 0000-00-00 00:00:00
		- Bezahldatum = 0000-00-00 00:00:00
		- Nicht storniert
	
	Vorkasse erhalten

	
	Top Seller


	Top Stornos

