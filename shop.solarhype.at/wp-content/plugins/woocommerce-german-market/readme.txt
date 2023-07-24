=== German Market ===
Contributors: MarketPress
Requires at least: 5.4+
Tested up to: 6.2
Stable tag: 3.23


== Description ==
German Market macht deinen WooCommerce-Shop fit für den deutschsprachigen Markt und für die gesamte EU. Inklusive aller rechtlich relevanter Inhalte, integrierter Rechnungserstellung, Anbindung an dein Buchhaltungssystem, Bearbeitung von Stornos und vielem mehr. Einfach verkaufen - mit German Market.

= Features =
<https://marketpress.de/german-market-funktionen>


= Hinweise =
*WICHTIG - HAFTUNGSAUSSCHLUSS*
Dieses Plugin trägt wesentlich dazu bei, ihren WooCommerce Shop rechtssicher betreiben zu können. Es ist durch einen Rechtsanwalt geprüft worden, der ebenfalls die rechtlichen Mustertexte wie AGB und die Widerrufsbelehrung erstellt hat. Sie entsprechen den rechtlichen Anforderungen eines typischen, an Verbraucher gerichteten Onlineshops. Jedoch kann die rechtliche Sicherheit eines Onlineshops nur im Einzelfall geprüft und bestätigt werden. Daher sind die rechtlichen Muster nur als Vorlagen zu verstehen, deren abschließende rechtliche Prüfung und ggf. Anpassung Ihnen obliegt. Falls Sie eine Prüfung Ihres Shops wünschen, können Sie sich an Rechtsanwalt Thomas Schwenke, LL.M. <http://rechtsanwalt-schwenke.de/service/kontakt/> wenden.

== Installation ==
= Requirements =
* WordPress 5.4+*
* PHP 7.2+*
* WooCommerce 5.1.0+*

= Installation =
 * Installieren Sie zuerst WooCommerce
 * Installieren Sie die Standardseiten für WooCommerce (Folgen Sie dazu der Installationsroutine von WoooCommerce)
 * Benutzen Sie den installer im Backend, oder

1. Entpacken Sie das zip-Archiv
2. Laden Sie es in das `/wp-content/plugins/` Verzeichnis ihrer WordPress Installation auf Ihrem Webserver
3. Aktivieren Sie das Plugin über das 'Plugins' Menü in WordPress und drücken Sie aktivieren
4. Folgen Sie den Anweisungen des Installationsbildschirms

== Other Notes ==
= Acknowledgements =
Thanks Mike Jolley (http://mikejolley.com/) for supporting us with the WooCommerce core.

= Licence =
 GPL Version 3

= Languages =
- English (en_US) (default)
- German (de_DE)

== Changelog ==

= 3.23 =
- Erweitert: lexoffice Add-On: Neue Einstellung "Übertrage die Bestellung vor der Rückerstattung"
- Verbessert: lexoffice Add-On: Bestellungen, welche Rückerstattungen besitzen, können stets zu lexoffice übertragen werden
- Verbessert: "Vermeide kostenlose Artikel im Warenkorb": Hook geändert von "woocommerce_checkout_process" zu "woocommerce_after_checkout_validation", für bessere Kompatibilität mit Zahlungs-Plugins
- Verbessert: Kompatibilität mit WooCommerce Subscriptions im Bezug auf Rechnungsnummern der Subscription (nicht Order) und der Einstellung "Automatische Generierung -> Neue Bestellung"
- Fix: "Zeige Produkteigenschaften, die nicht für Varianten verwendet werden" funktionierte nicht mehr für neu hinzugefügte Attribute, da $attribute->get_variation() bei einfachen Produkten immer true liefert
- Fix: Verbesserung der Übersetzungen
- Fix: Kleine Bugfixes

= 3.22.1 =
- Verbessert: Add-On "EU Mehrwertsteuer": Behandlung von PLZ aus EU-Ländern, welche nicht zum EU-Steuergebiet gehören
- Verbessert: Kompatibilität Theme Support Lacomete
- Fix: Dateianhänge hatten mit WordPress 6.2 (neue PHPMailer-Version) falschen Dateinamen (der Key im Array der Anhänge wird ausgegeben)

= 3.22 =
- Verbessert: Performance-Verbesserung in WGM_Refunds::count_all_refunds() (für das Untermenü "WooCommerce -> Alle Stornos")
- Verbessert: Nicht mehr genutzten Code aus WGM_Template::disclaimer_line() entfernt
- Verbessert: Der "alterntive Versandhinweis" im Produkt durfte bisher kein Prozentzeichen beinhalten
- Verbessert: Double Opt-In: Logout bei Kontoerstellung auf Kassen-Seite bereits im Hook 'wp' bei Erreichen des "order-received"-Endpunktes
- Verbessert: Ausgabe der Steuerrate (%) in Bestellung anhand der Steuerdaten, die in der Bestellung gespeichert sind
- Fix: Steuerausgabe bei Gebühren bei Bestellungen ist jetzt konsistent
- Fix: Korrekte Ausgabe auf der "Bestellung Prüfen"-Seite des Feldes "Bundesland / Landkreis"
- Fix: Verbesserung der Übersetzungen
- Fix: Kleine Bugfixes

= 3.21 =
- Verbessert: EU USTID-Nr. Check Add-On: JavaScript angepasst, sodass die Klasse "error" am Input-Feld nach einer ungültigen Validierung entfernt wird, wenn die nächste Validierung erfolgreich war
- Verbessert: WPML-Support (Übersetzbarkeit von Einstellungen bzgl. der Rückerstattungs-PDF, Verwendung der Übersetzungen des Rechnungsnummer Add-Ons beim manuellen E-Mail-Versand im Backend)
- Fix: Kleine Bugfixes

= 3.20 =
- Erweitert: Statische Dateianhänge für E-Mails (WooCommerce -> German Market -> Allgemein -> E-Mails -> Dateianhänge) können jetzt statt an alle WooCommerce E-Mails auch nur an ausgewählte E-Mails angehangen werden
- Erweitert: Manuelle Bestellannahme: Neue Einstellung "Der Kunde kann die Zahlungsmethode erst nach der manuellen Bestellannahme wählen"
- Fix: Rechnungs PDF Add-On: Download Rückerstattungs PDF auf "Mein Konto"-Seite
- Fix: Rechnungs PDF Add-On: CSS in Template "invoice-default-templates.php" ergänzt, um Fehler von DOMPDF zur verhindern, ausgelöst durch ungültige HTML/CSS-Elemente von Dritt-Plugins
- Fix: Kleine Bugfixes

= 3.19.1 =
- Verbessert: Kompatibilität Plugin "PayPal Payments 2.0"
- Fix: Kleine Bugfixes

= 3.19 =
- Verbessert: Kompatibilität Plugin "Subaccounts & Multi-User Accounts" (Verzicht der Validierung der Datenschutz-Checkbox bei Ausführung der Funktion smuac_create_multiaccount)
- Fix: SEPA E-Mail
- Fix: Verbesserung der Backend-Übersetzungen

= 3.18 =
- Verbessert: SEPA XML-Download: Bei Bestellungen, die bereits einmal exportiert würden, wird das Icon in der Bestellübersicht grün hinterlegt dargestellt
- Fix: WPML Support: Vermeidung von PHP-Notice wenn "Maßeinheiten" als Taxonomy nicht vorhanden ist
- Fix: Add-On Retourenschein/Lieferschein: Dateiname beim Backend-Download wird utf8-dekodiert
- Fix: Wenn die Einstellung "Zeige Produkteigenschaften, die nicht für Varianten verwendet werden" aktiv ist, kann es sein, dass Attribute unterschiedlich sortiert werden und so das selbe Produkt beim erneuten Hinzufügen in den Warenkorb nicht als identisches Produkt erkannt wird. Sortierung der Attributnamen behebt das Problem
- Fix: Fataler PHP-Fehler bei Erstellung der Rechnungs PDF, durch das Plugin "PayPal Payments". Kompatibilität durch German Market hergestellt.

= 3.17 =
- Verbessert: Rundungsgenauigkeit von Steuerbeträgen unterschiedlicher Steuerklassen in Versandkosten bei anteiliger Steuerberechnung von Nebenleistung
- Verbessert: WPML-Support: Registrierung der Steuernamen aller Steuerraten zur Übersetzung wird nur noch auf eingeschränkten Admin-Screens ausgeführt
- Verbessert: EU USTID-Nr. Check Add-On: Während der Validierung der MwSt.-Nummer wird der "Pay Order"-Button deaktiviert
- Verbessert: EU USTID-Nr. Check Add-On: Wenn die Einstellung "MwSt-Nummer auf Mein Konto-Seite und im Benutzerprofil im Backend editieren können" aktiviert ist, kann die MwSt-Nummer im Backend einer Bestellung aus dem Kundenprofil geladen werden
- Fix: E-Mail "New Account" wird bei Double Opt-In erst nach Aktivierung versendet (WooCommerce Version > 6.9)
- Fix: WPML Support: Das Plugin "WooCommerce Multilingual & Multi-Currency" ist Voraussetzung für die Verwendung des German Market Codes zur Herstellung der Kompatibilität

= 3.16 =
- Verbessert: WPML-Support
- Verbessert: Double Opt-In: Bei WooCommerce Versionen >= 6.0 und aktivierter WooCommerce-Einstellung "Sende dem neuen Benutzer bei der Erstellung eines Kontos einen Link, über den er sein Passwort festlegen kann.", wird in der "Double Opt-In"-Aktivierungs-E-Mail nicht das Passwort angezeigt, aber in der "Neues Konto"-E-Mail nach der Aktivierung der Link zur Passwort-Generierung angezeigt
- Verbessert: Wenn der installierende Admin eine andere Sprache als die "Sprache der Website" verwendet, Rechtstexte sowie Terms von Taxonomien trotzdem in der "Sprache der Website" installieren
- Verbessert: Terms von Taxonomien werden bei Uninstall gelöscht
- Fix: Rechnungs PDF Add-On: Ist die Einstellung "Download-Button für Storno PDFs aktivieren" eingestellt, darf dadurch keine Rechnungsnummer für die Bestellung generiert werden, wenn es weder Refunds gibt noch der Platzhalter für die Rechnungsnummer der Bestellung im Button-Text verwendet wird
- Fix: lexoffice Add-On: Vor dem Senden wird aus der MwSt-Nummer sämtliche Leerzeichen entfernt, um Fehler zu vermeiden 
- Fix: Kleine Bugfixes
- Fix: Verbesserung der Backend-Übersetzungen

= 3.15 =
- Rechtstexte: Die Vorlage für den Rechtstext "Impressum" wurde aktualisiert
- Rechtstexte: Die Vorlage für den Rechtstext "Datenschutz" wurde aktualisiert
- Rechtstexte: Die Vorlage für den Rechtstext "Widerrufsbelehrung" wurde aktualisiert (verwendbar ab 28.05.2022)
- Rechtstexte: Die Vorlage für den Rechtstext "Widerruf für digitale Inhalte" wurde aktualisiert (verwendbar ab 28.05.2022)
- Rechtstexte: Aktualisierung der Englischen Übersetzungen der Vorlagen der Rechtstexte
- Erweitert: News im German Market User Interface
- Erweitert: Neue Einstellungen bzgl. "Rechtlicher Informationen für Produktbewertungen" im Menü "WooCommerce -> German Market -> Allgemein -> Produkte"
- Erweitert: Neue Einstellung in der SEPA-Zahlungsmethode: "SEPA-Daten eines Benutzers zur Wiederverwendung speichern"
- Verbessert: Kompatibilität Theme RT-Theme-20
- Verbessert: Kompatibilität Theme Waveride
- Verbessert: Bei der Wiederholung der Checkbox "Für digitale Inhalte" in E-Mails / Rechnungs PDF wird der Satz "Erlöschung des Widerrufsrecht wird bestätigt" vorangestellt
- Verbessert: Double Opt-In: Nach der Registrierung eines Kunden wird nun direkt in WooCommerce durch Verwendung des Filters 'woocommerce_registration_auth_new_customer' verhindert, dass der Kunde sofort eingeloggt wird. Solltest du eigene Anpassungen per PHP-Code vorgenommen haben und dieses Verhalten nicht wünschen, so nutze: add_filter( 'woocommerce_registration_auth_new_customer', '__return_true', 999 );
- Fix: Rechnungs PDF: Schweizer QR-Rechnung: Fehlendes Leerzeichen zwischen Vor- und Nachname behoben
- Fix: lexoffice Add-On: Korrekte Kategorie bei Deutscher Besteuerung und Zielland außerhalb der EU
- Fix: Kleine Bugfixes

= 3.14.1 =
- Verbessert: Rechnungs PDF Add-On: Update DOMPDF Version 1.2.1
- Verbessert: Rechnungs PDF Add-On: "GiroCode" und "Schweizer QR-Rechnung" stehen erst ab PHP 7.4 zur Verfügung, entsprechende Meldung wird in den Untermenüs ausgegeben, wenn die Anforderung nicht erfüllt ist
- Fix: Rechnungs PDF Add-On: DOMPDF 1.2 Issue bzgl. fix positionierter Elemente mit "bottom: "-Angabe
- Fix: Rechnungs PDF Add-On: DOMPDF 1.2 Issue bzgl. Grafiken mit Höhe 100% und weight: auto;
- Fix: Rechnungs PDF Add-On: Wenn benutzerdefiniertes Kleingedrucktes ausgegeben werden soll, aber kein Text zugewiesen wurde, findet nun keine Ausgabe mehr statt (weder Seitenumbruch noch leeres DIV)
- Fix: Kleine Bugfixes

= 3.14 =
- Verbessert: Alle Daten zum Grundpreis können nun allgemein mit vier Nachkommastellen eingetragen werden
- Verbessert: sevDesk Add-On: Identifizierung von WordPress-Users / Gästen als sevDesk-User anhand der E-Mail-Adresse, um mehrfache Kontakterstellung in sevDesk für die selbe Person zu vermeiden 
- Verbessert: sevDesk Add-On: Übertragung der MwSt-Nummer für Kontakte
- Verbessert: Rechnungs PDF Add-On: Update DOMPDF Version 1.2.0
- Verbessert: Rechnungs PDF Add-On: Neue Funktionen zur Ausgabe von digital einscannbaren Codes für Zahlungen
- Verbessert: Kompatibilität Theme Bacola
- Fix: Kleine Bugfixes

= 3.13.2 =
- Erweitert: Neue Einstellung "German Market -> Allgemein -> Bestellvorgang -> Checkout Template / Theme Optionen -> Layout-Verschiebungen unterbinden", um auf der Kassen-Seite h3-Überschriften innerhalb des div-Containers "order_review" zu positionieren
- Verbessert: sevDesk Add-On: Fehler bei Übertragung von anderen Währungen als EUR verhindern
- Verbessert: Rechnungs PDF Add-On: "Text nach dem Rechnungsinhalt" ist jetzt für Rechnungs PDF und Rückerstattungs PDF jeweils eine gesonderte Einstellung
- Verbessert: Kompatibilität Theme Chaai
- Verbessert: Kompatibilität Theme Ambient
- Fix: Rechnungs PDF: Fehlende Ausgabe von Tabellenrändern bei Inkompatibilitäten mit anderen Plugins 
- Fix: Kleine Bugfixes

= 3.13.1 =
- Erweitert: Optionale Einstellung "Bestelleingangsbestätigungsmail erst bei Erreichen der Danke-Seite senden"
- Verbessert: Die Einstellung "Zeige Kurzbeschreibung - Varianten" ist nun auch im Rechnungs PDF sowie Retouren- und Lieferschein wirksam, wenn dort jeweils die Ausgabe der Kurzbeschreibung aktiviert ist
- Verbessert: Kompatibilität Plugin DHL for WooCommerce 2.7.6
- Verbessert: Kompatibilität Plugin Elementor Pro: Checkout Widget
- Verbessert: Kompatibilität Theme Zamona
- Verbessert: LMIV Add-On: Beim Laden des Frontend-JavaScripts dieses Add-Ons wird nun die German Market Einstellung "Frontend-JavaScript im Footer laden" berücksichtigt
- Fix: Typumwandlung von String zu Float bei erhaltener Steuerrate durch Werte von WC_Order_Item_Shipping::get_taxes[ 'totals' ], bei anteiliger Steuerberechnung

= 3.13 =
- Rechtstexte: Die Vorlage für den Rechtstext "Datenschutz" wurde aktualisiert
- Rechtstexte: Die Vorlage für den Rechtstext "Allgemeine Geschäftsbedingungen" wurde aktualisiert
- Rechtstexte: Aktualisierung der Englischen Übersetzungen von "Datenschutz" ("Privacy") und "Allgemeine Geschäftsbedingungen" ("Terms and Conditions")
- Fix: Ausgabe der Versandkosten einer Bestellung bei mehreren Versandmethoden
- Fix: Checkbox - Persönliche Daten an Versanddienstleister senden: Verbesserungen des JavaScripts zum Ein- und Ausblenden der Checkbox abhängig von der gewählten Versandmethode

= 3.12.6 =
- Erweitert: "Produktabhängige Checkbox", im Menü "WooCommerce -> German Market -> Allgemein -> Checkboxen"
- Erweitert: Wenn die Einstellung "WooCommerce -> German Market -> Allgemein -> Bestellvorgang -> Kurzbeschreibung anzeigen" aktiviert ist, kann für Varianten von variablen Produkten mit der neuen Einstellung "Zeige Kurzbeschreibung - Varianten" entschieden werden, ob die Produkt Kurzbeschreibung, die Beschreibung der Variante oder beides angezeigt werden soll.
- Verbessert: Rechnungs PDF Add-On: Die Einstellung "Rechnungs PDF -> Benutzerdefinierte CSS Styles -> HTML-Ausgabe" wird im Frontend, in E-Mail-Anhängen und bei der Übertragung zu lexoffice/sevDesk ab jetzt ignoriert, falls vergessen wurde, die Einstellung nach dem Stylen der PDF zu deaktivieren
- Verbessert: Rücksende- / Lieferschein Add-On: Update FPDF 1.84
- Verbessert: WPML Support: Übersetzung der Lieferzeiten bei Artikeln in Bestellungen (E-Mails) nicht korrekt, wenn E-Mail in anderer Sprache versendet wird als Bestellung aufgegeben wurde
- Verbessert: WPML Support: Verhindern des Cachings bei der Ausgabe der Steuern von Artikeln in Bestellungen
- Verbessert: Kompatibilität Plugin Oxygen bzgl. der Rechtstexte in E-Mails: Korrekte Darstellung der Rechtstexte, wenn die zugehörigen WordPress-Seiten mit dem Oxygen Builder erstellt wurden
- Verbessert: Add-On sevDesk: Wird gemäß den German Market Einstellungen kein Kontakt für den Beleg angelegt, wird nun trotzdem ein 'supplierName' an sevDesk übermittelt
- Fix: LMIV Add-On: Ausgabe des Alkoholgehaltes bei variablen Produkten korrigiert, wenn in den Varianten kein Alkoholgehalt gespeichert war
- Fix: Kleine Bugfixes
- Fix: Verbesserung der Übersetzungen

= 3.12.5 =
- Erweitert: Shortcode für Streichpreise hinzugefügt
- Erweitert: Shortcode für "Anforderungen (digital)" hinzugefügt
- Verbessert: Produkt-Meta "digital" wird nun durch die Hooks "woocommerce_ajax_save_product_variations" / "woocommerce_process_product_meta" gespeichert, nicht mehr durch "save_post"
- Verbessert: Kompatibilität magnalister
- Verbessert: Kompatibilität Theme Zocker
- Verbessert: Kompatibilität Theme Brunn
- Verbessert: Rechnungs PDF Add-On: Update DOMPDF Version 1.0.2
- Verbessert: Add-On lexoffice: "Ausfuhrlieferung Drittländer" wird in der API jetzt auch für Privatpersonen unterstützt
- Verbessert: Rechnungsnummer Add-On: Im Backend einer Bestellung kann eine bereits erstellte Rechnungsnummer per Klick auf "Rechnungsnummer löschen" gelöscht werden.
- Fix: Add-On lexoffice: Ermittlung der Steuerrate bei Order Items war ggf. nicht korrekt, wenn die Steuerrate keine Ganzzahl war
- Fix: Verbesserung der Übersetzungen
- Fix: Kleine Bugfixes

= 3.12.4 =
- Erweitert: GTIN wird in den strukturierten Daten ausgegeben (WooCommerce-Hook: woocommerce_structured_data_product)
- Verbessert: sevDesk Add-On: Unterstützung anderer Währungen als EUR bei der Übermittlung der Belege
- Fix: Im Menü "WooCommerce -> Alle Stornos" wird die korrekte Währung beim Betrag ausgegeben
- Fix: SEPA - Die Einstellung "Checkbox-Text" wirkte bei Änderungen nicht im Frontend, der Standard-Text wurde immer genutzt
- Fix: Fataler Fehler beim Deaktivieren von German Market behoben, wenn das Add-On "EU USTID-Nr. Check" aktiviert war
- Fix: Verbesserung der Übersetzungen

= 3.12.3 =
- Erweitert: Im Menü "WooCommerce -> German Market -> Allgemein -> Bestellvorgang" gibt es nun die Einstellungsmöglichkeit, um Artikel im Warenkorb und/oder Bestellungen nach Name oder Aritkelnummer zu sortieren
- Erweitert: sevDesk Add-On: Unterstützung von steuerfreien Ausfuhrlieferungen / steuerfreie innergemeinschaftlichen Lieferungen als Ust.-Regel in sevDesk
- Erweitert: Rechnungs PDF Add-On: Auf der Mein-Konto-Seite können optional zusätzlich die Storno PDFs als Download angeboten werden
- Verbessert: Kompatibilität "WooCommerce PayPal Payments", Ausgabe der PayPal Buttons genau an der Stelle, an der bei anderer Zahlungsweise der "Zahlungspflichtig Bestellen"-Button erscheint
- Fix: Rechnungsnummer Add-On: Bei der Einstellung "Betreffzeile 2 (klein)" aus dem Menü "Storno PDF" wurde nicht der korrekte Standardwert verwendet, wenn die Einstellung noch nie gespeichert wurde
- Fix: Der Datenschutztext von WooCommerce sollte auf der Kassen-Seite nicht angezeigt werden, wenn die Checkbox "AGB, Datenschutz, Widerruf" von German Market angezeigt wird
- Fix: Streichpreisanzeige für Varianten von variablen Produkten korrigiert (vorher wurde der Hook "woocommerce_format_sale_price" genutzt, jetzt "woocommerce_get_price_html")
- Fix: Kompatibiltät Polylang mit manueller Bestellannahme: Übersetzung der Zahlungsanweisungen
- Fix: Add-On lexoffice: Wenn die Einstellung "Automatisches User-Update" auf die Option "Update lexoffice User" gesetzt wurde, wurden lexoffice Kontakte bisher nicht geupdated, wenn es sich um Gast-Bestellungen in WooCommerce handelte
- Fix: Add-On lexoffice: Unterstützung von Nordirland für Fernverkäufe
- Fix: Add-On lexoffice: Ermittlung der Steuerraten bei Kleinstbeträgen verbessert
- Fix: Add-On billbee: Links im Menü "WooCommerce -> Bestellungen" korrigiert
- Fix: Add-On Retourenschein/Lieferschein: Bei Adressen Apostroph korrekt darstellen
- Fix: Manuelle Bestellannahme: Kunden-E-Mail nach der manuellen Bestellannahme wurde bisher nur gesendet, wenn die E-Mail "Bestellung wartend" aktiviert war
- Fix: Kleine Bugfixes

= 3.12.2 =
- Vorraussetzungen: Minimale WordPress-Version 5.4+
- Vorraussetzungen: Minimale WooCommerce-Version: 5.1.0+
- Erweitert: Rechnungs PDF Add-On: Rechnungs PDFs könnnen in E-Mails als Link versendet werden anstatt E-Mail-Anhang
- Erweitert: EU USTID-Nr. Check Add-On: Optional kann die MwSt-Nummer auf der "Mein Konto"-Seite durch den Nutzer selbst und im Backend im Benutzerprofil durch den Admin editiert werden. Die im Profil gespeicherte Nummer wird auf der Kassen-Seite verwendet, um das Feld für die MwSt-Nummer vorauszufüllen
- Fix: sevDesk Add-On: Bei aktivier anteiliger Steuerberechnung für Nebenleistungen kam es bei steuerfreien Bestellungen ggf. im sevDesk-Beleg zu falschen Versandkosten / Rundungsdifferenzen
- Fix: Add-On IT-Recht Kanzlei: Anpassung an die aktualisierte Schnittstelle
- Fix: Verbesserung der Übersetzungen
- Fix: Kleine Bugfixes

= 3.12.1 =
- Erweitert: EU USTID-Nr. Check Add-On: Die Einstellung "Anzeige des Feldes für die MwSt-Nummer" hat nun zusätzlich die Option "Deaktiviert"
- Erweitert: EU Mehrwertsteuer Add-On: Neues Untermenü "EU-Steuersätze anhand KN-Nummer importieren", durch Abfrage einer KN-Nummer können per API die zugehörigen Steuersätze der EU-Mitgliedsstaaten ermittelt werden und nach Auswahl in eine Steuerklasse importiert werden
- Erweitert: lexoffice Add-On: Unterstützung von Fernverkäufen
- Verbessert: Ausgabe der MwSt-Informationen in Gesamtsummen bei verschiedenen Steuersätzen berücksichtigte immer nur verschiedene Steuernamen durch WooCommerce, jetzt findet die Aufschlüsselung nach Steuernamen und Prozentsatz statt. Dadurch können bei verschiedenen Steuerklassen identische Steuernamen vergeben werden

= 3.12 =
- Verbessert: Kompatibilität Plugin Elementor mit dem LMIV Add-On (Tab "Zutaten" wurde ggf. nicht korrekt dargestellt)
- Verbessert: Kompatibilität Theme Zigcy Lite
- Verbessert: Kompatibilität Theme Movedo
- Verbessert: Kompatibilität Theme Riode
- Verbessert: Kompatibilität Theme Lucia Vineyards
- Verbessert: Kompatibilität Plugin Polylang / Polylang for WooCommerce: Neues Menü "WooCommerce -> German Market -> Polylang Support", Download der PDFs im Backend richtet sich jetzt entweder nach der Sprache der Bestellung oder einer selbst definierten Sprache
- Verbessert: Verzicht auf jQuery.noConflict() in Frontend- und Backend-JavaScripts
- Verbessert: Routinen für Plugin-Aktivierung /-Deaktiviertung und -Uninstall für MultiSite-Installationen verbessert
- Verbessert: Datenschutz-Checkbox bei Produktbewertungen wird jetzt unter ggf. erscheinenden Eingabefeldern für "Name" und "E-Mail" angezeigt statt darüber
- Verbessert: Bei Mehrfachaktionen zum Download von Rechnungs-, Lieferschein- und Retourenschein-PDFs wird, falls die PHP-Klasse "ZipArchive" nicht zur Verfügung steht, die Klasse "PclZip" aus WordPress geladen und genutzt
- Verbessert: Das WooCommerce-Template "cart/cart.php" wird nicht mehr durch German Market überschrieben
- Erweitert: EU USTID-Nr. Check Add-On: Optionale Protokollierung der Antwort der MIAS-Abfrage, wird als Bestellnotiz gespeichert, etwaige Fehler im WooCommerce-Log protokolliert
- Erweitert: EU USTID-Nr. Check Add-On: Optionale Übergabe der MwSt-Nummer des Antragstellers an MIAS, um eine Abfrage-Nummer in der Antwort zu erhalten
- Erweitert: EU USTID-Nr. Check Add-On: Die MwSt-Nummer kann im Backend einer Bestellung vom Shop-Admin bearbeitet werden
- Erweitert: EU USTID-Nr. Check Add-On: Die Validierung der MwSt-Nummer wird nicht mehr strikt am Land der Rechnungsadresse ausgeführt, sondern ggf. anhand der Lieferadresse, abhängig von der WooCommerce-Einstellung "Steuer berechnen basierend auf"
- Erweitert: EU USTID-Nr. Check Add-On: Die standardmäßige Anzeige des Feldes für die MwSt-Nummer kann mittels Einstellung abgeändert werden. Standardmäßig wird das Feld angezeigt, wenn das Rechnungs- bzw. Lieferland des Kunden ein EU-Land ist, welches nicht das Shop-Basisland ist
- Erweitert: EU Mehrwertsteuer Add-On: Wenn es aktiviert ist, erhält das Add-On ein eigenes Submenü bei "WooCommerce -> German Market -> EU Mehrwertsteuer"
- Erweitert: EU Mehrwertsteuer Add-On: Die Einstellung "Brutto-Preise Fixieren" besitzt nun zusätzlich die Option "Aktivieren für alle Produkte"
- Erweitert: EU Mehrwertsteuer Add-On: Im Menü "WooCommerce -> German Market -> EU Mehrwertsteuer -> EU-Steuersätze Importieren" können Steuersätze der EU-Mitgliedsstaaten per Knopfdruck in die WooCommerce-Steuerklassen importiert werden
- Erweitert: EU Mehrwertsteuer Add-On: Untermenü "Verallgemeinerte Steuerausgabe", um Steuerangaben allgemein ohne Steuerrate und -Betrag auszuweisen
- Erweitert: Das E-Mail Template für die "Bestelleingangsbestätigungsmail" kann nun im Menü "German Market -> Allgemein -> E-Mails" per Knopfdruck ins Child-Theme kopiert werden
- Erweitert: Neue Einstellung "Kunden IP nicht in Bestellung speichern" im Menü "German Market -> Allgemein -> Globale Optionen"
- Fix: Add-On "Zeitweise Mehrwertsteuersenkung": Steuerausgabe-Einstellungen wurden entfernt, es können die neuen Funktionen des Add-Ons EU Mehrwertsteuer genutzt werden
- Fix: Zur Ausgabe des Prozentwertes bei Steuersätzen wird WC_Tax::find_rates genutzt, aber bisher ohne die Übergabe der Postleitzahl als Parameter
- Fix: Kompatibilität Plugin WP-Staging Pro: Übernahme der Einstellungen der Add-Ons Rechnungs PDF und Rechnungsnummer in die Staging-Site
- Fix: Verbesserung der Übersetzungen

= 3.11.1 =
- Verbessert: Kompatibilität Theme Hello-Elementor
- Verbessert: Kompatibilität Theme Lotusgreen
- Verbessert: Kompatibilität Theme Dokan
- Fix: WooCommerce 5.1 verwendet in WC_Checkout::process_order_payment jetzt "wp_safe_redirect" anstatt "wp_redirect". Die Standard-PayPal-Schnittstelle von WooCommerce hat keinen Filter für "allowed_redirect_hosts", um die PayPal-URLs für eine Weiterleitung nach Bestellabschluss zu erlauben. Bei Verwendung der "Bestellen Prüfen"-Seite von German Market findet kein AJAX-Request statt, es wird "wp_safe_redirect" ausgeführt mit einer nicht erlaubten URL, daher leitet die Funktion nicht korrekt weiter. Das Problem wird in German Market gelöst, in dem die von der Zahlungsart übergebene Weiterleitungs-URL zur Laufzeit den erlaubten URLs hinzugefügt wird.
- Fix: Typos in der Englischen Vorlage für den Rechtstext "Datenschutz" korrigiert
- Fix: Rechnungs PDF: DOMPDF: Fehlende Font Files ergänzt

= 3.11 =
- Erweitert: Zahlungsart "Kauf auf Rechnung": Ist in der Zahlungsart die Einstellung "Deaktiviere 'Lieferung an eine andere Adresse senden'" aktiviert, so wird eine Meldung auf der Kassen-Seite ausgeben, wenn der Kunde "Lieferung an eine andere Adresse senden" erst aktiviert hatte und dann zu "Kauf auf Rechnung" wechselt
- Verbessert: Kompatibilität Plugin WP-Staging: Übernahme der Einstellungen der Add-Ons Rechnungs PDF und Rechnungsnummer in die Staging-Site
- Verbessert: Kompatibilität Theme XStoreVersion (bzgl. Kompatibilität mit Page-Buildern)
- Verbessert: Kompatibilität Theme Furlen
- Verbessert: Kompatibilität Theme Goya
- Verbessert: Kompatibilität Theme Blocksy
- Verbessert: Kompatibilität Theme Kitring
- Verbessert: Kompatibilität Theme Bikeway
- Verbessert: Kompatibilität Plugin Oxygen bzgl. des Rechnungs PDF Add-Ons: Korrekte Darstellung der "Rechtstexte PDFs", wenn die zugehörigen WordPress-Seiten mit dem Oxygen Builder erstellt wurden
- Verbessert: Rechnungsnummer Add-On: Verwendung Sempahore zur Vermeidung gleichzeitiger Code-Ausführung
- Verbessert: Rechnung PDF Add-On: Kompatibilität mit Plugin PDF Product Vouchers 3.9.0
- Fix: Rechnungs PDF Add-On: DOMPDF Einstellungen bzgl. Remote-Optionen zum Laden externer Quellen (CSS, Bilder) angepasst
- Fix: Add-On sevDesk: MwSt.-Raten-Zuweisung bei Artikel mit Rabatt verbessert
- Fix: Bei der Nutzung der Funktion "Duplizieren" eines Produktes wurde die Eigenschaft "digital" nicht übernommen
- Fix: Kleine Bugfixes

= 3.10.6 =
- Erweitert: Shortcodes für German Market Produktdaten, siehe Dokumentation https://marketpress.de/dokumentation/german-market/shortcodes-fuer-produktdaten/
- Erweitert: BCC/CC kann auch für die E-Mail "Double-Opt-in Kundenregistrierung" aktiviert werden
- Erweitert: Steuerlicher Hinweis für Gebühren der Zahlungsweise Nachnahme angepasst, Einstellung für Steuerberechnung hinzugefügt in der Zahlungsweise
- Erweitert: SEPA - Verfügbarkeit auf der Kassen-Seite optional nur für registrierte Kunden, Kunden mit 1, 2 oder 3 abgeschlossenen Bestellungen
- Verbessert: Rechnungs PDF Add-On: Update DOMPDF Version 1.0.1
- Verbessert: sevDesk Add-On: Fehlerausgabe bei falscher / fehlender Authentifizierung angepasst
- Verbessert: Kompatibilität Theme Handlavet
- Verbessert: Kompatibilität Theme Zonex
- Verbessert: Kompatibilität Theme Sport
- Verbessert: Kompatibilität Theme Rigid
- Verbessert: Kompatibilität Theme Learts
- Fix: Grundpreisausgabe in Rechnungs PDF
- Fix: Verbesserung der Übersetzungen
- Fix: Kleine Bugfixes

= 3.10.5 =
- Verbessert: Kompatibilität mit Plugins, welche die Währung von Bestellungen wechseln: Anzeige der MwSt. im Versand in korrekter Währung bei speziellen Einstellungen
- Verbessert: Kompatibilität Theme Emarket
- Verbessert: Kompatibilität Theme Drile
- Verbessert: Kompatibilität Theme Blaze
- Verbessert: Kompatibilität Theme Neve (mit Neve Pro Addon)
- Verbessert: Kompatibilität Subscriptions: BBC / CC Einstellungen auch für E-Mails dieses Plugins
- Erweitert: SEPA: Einstellungen in der Zahlungsmethode zur Ausgabe einer Vorankündigung
- Fix: Manuelle Bestellannahme: Anpassung an WooCommerce 4.7.0 (diese Version nutzt nun $order->needs_payment() statt WC()->cart->needs_payment() )
- Fix: Retourenschein/Lieferschein Add-On: In der Fußzeilen der PDFs wurde das Bullet-Symbol "•" bisher nicht korrekt dargestellt
- Fix: Protected Shops Add-On: JavaScript- / Ajax-Fehler behoben
- Fix: Verbesserung der Übersetzungen
- Fix: Kleine Bugfixes

= 3.10.4 =
- Rechtstexte: Die Vorlage für den Rechtstext "Datenschutz" wurde aktualisiert
- Verbessert: German Market UI Anpassung für Kompatibilität mit WooCommerce 4.4.0
- Verbessert: Anpassungen für WooCommerce 4.4.0 bzgl. "deprecated functions"
- Verbessert: WooCommerce cart.php Template aktualisiert
- Verbessert: Kompatibilität Theme Urus
- Verbessert: Kompatibilität Theme Panaderia
- Verbessert: Admin JavaScript: deprecated jQuery function ".size()" durch .length ersetzt
- Verbessert: Kompatibilität Plugin B2B Market (bzgl. der Zahlungsart SEPA von German Market, Sichtbarkeit der Checkbox auf der Kassen-Seite bei speziellen Kundengruppen)
- Fix: Rechnungsnummer Add-On / Rechnungs PDF Add-On: Für spezielle Browser wurde der JavaScript angepasst, der in einer Bestellung im Backend dafür sorgt, dass nach Download der Rechnungs PDF das Feld für die Rechnungsnummer ausgefüllt wird
- Fix: SEPA: Verbesserung der Darstellung der SEPA-Checkbox auf der Kassen-Seite, wenn ein Kunde SEPA gewählt hatte und dann auf eine andere Zahlungsweise wechselt
- Fix: Rechnungsnummer Add-On: Beim Reset wird jetzt current_time zur Abfrage der aktuellen Zeit genutzt
- Fix: Kompatibilität Plugin "WP HTML Mail - WooCommerce" und Add-On "Zeitweise Mehrwertsteuersenkung": Ausgabe der Steuerrate in Bestelleingangsbestätigung
- Fix: lexoffice Add-On: Beachten, dass die Funktion array_key_first erst ab PHP 7.3 zur Verfügung steht
- Fix: Typos und Verbesserung der Übersetzungen
- Fix: Kleinere weitere Bugfixes

= 3.10.3.2=
- Verbessert: Kompatibilität WooCommerce Memberships
- Fix: Add-On "Zeitweise Mehrwertsteuersenkung": Wenn die Anteilige Besteuerung von Nebenleistungen deaktiviert ist (im Menü "WooCommerce -> German Market -> Allgemein -> Globale Optionen), kam es bei den Versandkosten zu falschen Beträgen der Steuern. Die Einstellung sollte in Deutschland aktiviert sein, der Fix ist für den Shop nur notwendig, wenn die genannte Einstellung deaktiviert ist.

= 3.10.3.1.1=
- Erweitert: Add-On "Zeitweise Mehrwertsteuersenkung": Einstellungen für die Steuerausgabe
- Verbessert: Add-On "Zeitweise Mehrwertsteuersenkung": Erhöhung der Prioritäten der Hooks von 10 auf 30, zur Kompatibitlät mit Dritt-Plugins

= 3.10.3=
- Erweitert: Neues Add-On "Zeitweise Mehrwertsteuersenkung"
- Erweitert: Die Einstellung "Automatisches Löschen: Zusätzlicher Text" bei den Double-Opt-In Einstellungen kann nun mehrzeilig erfolgen
- Verbessert: Aktualisierung des cart.php Templates
- Kleinere weitere Bugfixes

= 3.10.2.1=
- Fix: lexoffice Add-On: Falsche Validierungsausgabe bei Übermittlungsfehlern
- Verbessert: Kompatibilität Theme DIVI
- Verbessert: Kompatibilität Theme Open Shop

= 3.10.2=
- Erweitert: Double-Opt-In Management: Manuelle Kontoaktivierung als Mehrfachaktion im Menü "Benutzer"
- Erweitert: BCC/CC kann auch für die E-Mails "Neues Konto" und "Kundenanmerkung" aktiviert werden
- Erweitert: Im Menü "WooCommerce -> German Market -> Allgemein -> Lieferzeiten" kann jetzt separat eingestellt werden, ob die Lieferzeiten im Lieferschein/Retourenschein/Rechnungs PDF angezeigt werden, wenn die Einstellung "Zeige die Lieferzeit auf der Bestellübersicht an" aktiv ist und die entsprechenden Add-Ons verwendet werden
- Erweitert: Retourenschein/Lieferschein Add-On: Beim Lieferschein können bei der Bemerkung Platzhalter für die Telefonnummer und E-Mail-Adresse des Kunden verwendet werden, sowie für das Gesamtgewicht der Bestellung
- Verbessert: Bei der Einstellung "Text: Hinweis bzgl. geschätzter Steuern und Versandkosten" werden Zeilenumbrüche im Frontend berücksichtigt
- Verbessert: Kompatibilität Plugin WooCommerce Product Bundles
- Verbessert: Kompatibilität Plugin Ultimate Addons for Elementor
- Verbessert: Kompatibilität Plugin WPC Smart Quick View for WooCommerce
- Verbessert: Ob der Frontend-JavaScript von German Market im Footer oder Header eingebunden wird, kann nun im Menü "WooCommerce -> German Market -> Allgemein -> Globale Optionen" selbst festgelegt werden
- Verbessert: Kompatibilität Plugin Plugin WPC Smart Quick View for WooCommerce
- Verbessert: Kompatibilität Theme MyBag
- Verbessert: Kompatibilität Theme Teepro
- Verbessert: Kompatibilität Theme Crexis
- Verbessert: Kompatibilität Theme Tonda
- Verbessert: Kompatibilität Theme Kartpul
- Verbessert: Kompatibilität Theme Beaver Builder Theme
- Verbessert: Kompatibilität Theme Pearl
- Fix: lexoffice Add-On: Falsche Validierungsausgabe bei Übermittlungsfehlern

= 3.10.1 =
- Erweitert: Die Maßeinheiten für die Grundpreise lassen sich im Nachgang erneut installieren (im Menü WooCommerce -> German Market -> Allgemein -> Produkte -> Grundpreis).
- Erweitert: Die Maßeinheiten für die Grundpreise cm², m² und Stück sind als Standard-Maßeinheiten aufgenommen.
- Erweitert: EU USTID-Nr. Check Add-On: Als Admin Einstellung kann gewählt werden, ob das Vereinte Königreich als EU-Land behandelt werden soll (WooCommerce macht dies nicht mehr).
- Verbessert: Manuelle Bestellannahme: Keine Übertragungsmöglichkeit zu sevDesk / lexoffice anzeigen und durchführen, wenn die Bestellung noch nicht manuell bestätigt wurde
- Verbessert: Der Knopf für die "Manuelle Bestellannahme" im Menü "WooCommerce -> Bestellungen" sieht nun anders aus als der Knopf für "Abgeschlossen"
- Verbessert: Kompatibilität Theme Techmarket
- Verbessert: Kompatibilität Theme Rehub
- Verbessert: Kompatibilität Theme Beaver
- Verbessert: Kompatibilität Theme Paw Friends
- Verbessert: Kompatibilität Theme Biagiotti
- Verbessert: Kompatibilität Theme Faith
- Verbessert: Kompatibilität Theme Astra "Two Step Checkout"
- Verbessert: Kompatibilität Theme Eveland
- Verbessert: Kompatibilität Theme Bolge
- Verbessert: Kompatibilität Theme SweethTooth
- Verbessert: Kompatibilität Theme Massive Dynamic
- Verbessert: Kompatibilität Theme Savory
- Verbessert: Kompatibilität Plugin Amelia
- Verbessert: Frontend JavaScript wird nun im Footer eingebunden
- Verbessert: WPML Support: Zahlungsmethode in Rechnungs PDF nicht übersetzt, wenn die "Sprache des Downloads" verschieden von Bestellsprache
- Fix: Add-On sevDesk: Bei manuellen angelegten Bestellungen ohne Lieferadresse wurde an sevDesk als Lieferadresse "Deutschland" im Kontakt angelegt, jetzt: es wird keine Adresse in sevDesk angelegt
- Fix: Add-On sevDesk: Wurde in der Bestellung "_shipping_address_2"/"_billing_address_2" verwendet, wurde diese nicht korrekt zu sevDesk übertragen
- Fix: Add-On lexoffice: Bei Kleinstbeträgen gab es Schwierigkeiten, die korrekte Steuerrate zu zuweisen
- Fix: Typos und Verbesserung der Übersetzungen
- Fix: Manuelle Bestellannahme: Text auf der Danke-Seite fix gesetzt, da sonst verwirrender Text bei einigen Zahlungsarten (z.B. PayPal) ausgegeben wird
- Kleinere weitere Bugfixes

= 3.10 =
- Vorraussetzungen: Minimale PHP-Version 7.2
- Vorraussetzungen: Minimale WordPress-Version 5.0
- Vorraussetzungen: Minimale WooCommerce-Version: 3.9.2+
- Erweitert: Erforderliche German Market Seiten können erneut installiert werden
- Erweitert: Vorlagen der Rechtstexte können sich im Menü "Vorlagen der Rechtstexte" angezeigt und kopiert werden
- Erweitert: Grundpreis wird bei variablen Produkten angezeigt, wenn alle Varianten des variablen Produktes den identischen Grundpreis besitzen
- Erweitert: WPML Support von German Market Einstellungen
- Erweitert: sevDesk Add-On: Mehrfachaktion "Senden zu sevDesk" (Bulk) für Bestellungen und Rückerstattungen
- Erweitert: sevDesk Add-On: Die Belegbeschreibung ist als Text-Einstellung frei wählbar (mit Platzhaltern für Bestellnummer, Storno-ID, Rechungsnummer, Stornonummer)
- Erweitert: lexoffice Add-On: Mehrfachaktion "Senden zu sevDesk" (Bulk) für Bestellungen und Rückerstattungen
- Erweitert: Double-Opt-In: Management und automatisches Löschen nicht aktivierter Konten
- Erweitert: Rechnungs PDF Add-On: Im Backend lässt sich optional einstellen, dass eine Spalte Position in der Rechnungs PDF ausgegeben wird
- Erweitert: Automatische Grundpreisberechnung mit WooCommerce-Gewichten: Optionale Möglichkeit im Produkt, die Ausgabe des Grundpreises zu unterbinden
- Erweitert: SEPA XML-Datei: Optionale Einstellungsmöglichkeit, ob rückerstattete Beträge in den exportieren Bestellungen berücksichtigt werden sollen
- Erweitert: Rücksende- / Lieferschein PDF Add-On: Optionale Einstellungsmöglichkeit, ob die Artikelmenge rückerstattete Mengen berücksichtigen soll
- Erweitert: Es ist eine Standard-Vorlage für "Anforderungen (digital)" als Option setzbar, wenn dies geschehen ist, ist diese bei neuen Produkten voreingetragen
- Erweitert: Rechnungs PDF Add-On: In Storno-PDFs wird die Anzahl jetzt so ausgegeben wie in WooCommerce E-Mails
- Erweitert: Rechnungs PDF Add-On: Rechnungs PDF und "Weitere PDFs" lassen sich an die E-Mail "Anmerkung des Kunden" anhängen
- Erweitert: LMIV Add-On: Zutaten
- Verbessert: Kompatibilität WPML: Ausgabe der Produktbilder auf "Bestellung prüfen Seite"
- Verbessert: Kompatibilität Theme SweetTooth
- Verbessert: Kompatibilität Theme Shoptimizer
- Verbessert: Kompatibilität Theme Hermes
- Verbessert: Kompatibilität Theme WPLMS
- Verbessert: Kompatibilität Theme Chromium
- Verbessert: Kompatibilität Theme DIVI
- Verbessert: Kompatibilität Theme Hyperon
- Verbessert: Kompatibilität Theme Werkstatt
- Verbessert: Kompatibilität Theme Naturalife
- Verbessert: Kompatibilität Theme Klippe
- Verbessert: Kompatibilität Theme Makali
- Verbessert: Kompatibilität Theme Kameleon
- Verbessert: Kompatibilität Theme Bazien
- Verbessert: Kompatibilität Theme Eat Eco
- Verbessert: Kompatibilität Theme Kalium
- Verbessert: Kompatibilität Theme Supro
- Verbessert: Kompatibilität Theme Coi
- Verbessert: Kompatibilität Theme Peggi
- Verbessert: Kompatibilität Theme Vermeil
- Verbessert: Kompatibilität Theme ToyShop
- Verbessert: Kompatibilität Theme Woovina
- Verbessert: Kompatibilität Theme Ken
- Verbessert: Kompatibilität Theme Sovereign
- Verbessert: Kompatibilität Subscriptions: Fälligkeitsdatum wird in Renewals neu gesetzt
- Verbessert: Kompatibilität Plugin Kadence WooCommerce Email Designer
- Verbessert: Kompatibilität Plugin WooCommerce PDF Product Vouchers
- Verbessert: Kompatibilität Plugin Elementor mit dem LMIV Add-On
- Verbessert: Kompatibilität WooCommerce WooCommerce Global Cart
- Verbessert: SEPA: Update PHP-Bibliothek php-iban 2.7.3
- Verbessert: Rücksende- / Lieferschein Add-On: Update FPDF 1.82
- Verbessert: Rechnungs PDF Add-On: Update DOMPDF Version 0.8.5
- Verbessert: lexoffice Add-On: Kompatibilität mit "Product Bundles for WooCommerce" (Berücksichtigung von Artikel mit negativem line_total)
- Verbessert: Ausgabe der UST ID und Steuerinformationen (steuerfreie Ausfurhlieferung / innergemischaftliche Lieferung) in E-Mails / Rechnungs PDF
- Verbessert: LMIV Add-On: "Lebensmittel-Daten" sind im Backend im Produkt erstmal ausgeblendet, können per Klick eingeblendet werden
- Verbessert: Bestelleingangsbestätigungsmail: Bestellnotiz des Kunden ausgeben
- Verbessert: Kompatibilität PHP 7.4.3
- Fix: SEPA XML-Datei: Korrekte Klassifizierung der Lastschrift als OOFF statt RCUR
- Fix: Beim Betreff der Bestelleingangsbestätigung wird {order-date} nun so ausgegeben, wie in WooCommerce E-Mails
- Fix: Ausgabe des Fälligkeitsdatum war mit WooCommerce 3.7 fehlerhaft (es wird esc_html ausgeführt, daher ist HTML als Text sichtbar gewesen)
- Fix: Zusätzliche Einstellungen von Zahlungsarten speichern nicht mehr (Fälligkeitsdatum, individuelle sevDesk Zahlungskonten je Zahlungsart)
- Fix: Produkt-MwSt im Mini-Warenkorb muss sich auf die WooCommerce-Einstellung "Preise während Bestell- (Warenkorb) und Bezahlvorgang (Kasse) anzeigen" beziehen
- Fix: Bessere Ladezeiten bei variable Produkten
- Fix: Backend Styles für das User Interface angepasst bzgl. WordPress 5.3
- Fix: Korrektur für "Einzelpreis" bei Gutschein-Anwendung
- Fix: Zeilenumbruch in Plain E-Mails korrigiert
- Fix: Typos
- Kleinere weitere Bugfixes

= 3.9.1 =
- Verbessert: Kompatibilität Plugin WooCommerce Advanced Notifications
- Verbessert: Kompatibilität Theme The Retailer
- Verbessert: Kompatibilität Theme  uncode
- Verbessert: Kompatibilität Theme  Mesmerize
- Verbessert: Kompatibilität Theme  Shop-Keeper
- Verbessert: Kompatibilität Plugin Elementor
- Verbessert: SEPA: Buchstaben von IBAN und BIC werden nur in Großbuchstaben gespeichert, Leerzeichen werden nun ignoriert
- Fix: sevDesk Add-On: Individuelle Buchungskonten für Varianten von variablen Produkten wurden nicht angewandt, behoben
- Fix: WPML Kompatibilität: WooCommerce-Body-Klassen werden jetzt auch an übersetzte Versionen der "Bestellung Prüfen"-Seite eingefügt
- FIX: WPML Kompatibilität: PHP-Warnung "unset property name" bzgl. Lieferzeiten-Ausgabe behoben
- Fix: "Warning: A non-numeric value encountered" bei bestimmten PHP-/mySQL-Versionen bzgl. Grundpreis-Ausgabe behoben
- Fix: Kompatibilität für Page Builder: Falsche Ausgabe der German Market Daten in Widgets behoben
- Fix: Runtime Cache für German Market Daten hat die aufrufende Funktion als Key erhalten, um falsche Ausgaben zu verhindern
- Fix: Wenn in German Market die Produktbilder auf der Kasse-Seite aktiviert sind, werden diese nun auch auf der "Bestellung prüfen Seite" ausgegeben
- Fix: Zahlungsart "SEPA" wurde angezeigt, obwohl es deaktiviert ist, wenn Einstellungen bei "Verfügbar für Versandländer" gesetzt sind
- Fix: Manuelle Bestellannahme: Zahlungslink war in E-Mails nicht enthalten
- Fix: Neuer Runtime Cache berücksichtigt nun aufrufende PageBuilder

= 3.9 =
- Erweitert: Rechnungs PDF Add-On: DOMDPF aktualisiert (PHP 7.3-Support)
- Erweitert: Rechnungs PDF Add-On: RTL-Sprachen können in der PDF nun korrekt dargestellt werden
- Erweitert: Caching-Plugins wird nun besser mitgeteilt, dass die Bestellen Prüfen Seite vom Caching auszuschließen ist.
- Erweitert: Statische Dateianhänge sind jetzt mit Sprach-Plugins als Strings übersetzbar
- Erweitert: Rechnungsnummer Add-On: Optionen werden jetzt vom Caching ausgeschlossen, Cache vorher gelöscht, zeitgleicher Zugriff auf die Funktion verhindert. Damit soll mehrfache Vergabe einer Rechnungsnummer ausgeschlossen werden
- Erweitert: Verwendung eines Runtime-Caches für Ausgabe von Produktdaten
- Erweitert: sevDesk Add-On: Übertragung des Fälligkeitsdatums an sevDesk
- Erweitert: EU USTID-Nr. Check Add-On: Steuerinfo bzgl. steuerfreier innergemeinschaftl. Lieferung / Ausfuhrlieferung wird nun auch im Checkout angezeigt
- Erweitert: EU USTID-Nr. Check Add-On: USTID und Steuerinfo (innergemeinschaftl. Lieferung / Ausfuhrlieferung) wird in Bestellübersicht dargestellt (WooCommerce -> Bestellungen) (optional per Einstellung)
- Erweitert: Zahlungsart "Kauf Auf Rechnung": Verfügbarkeit nach Rechnungsland des Kunden
- Erweitert: Auf Shop-Seiten kann der "In den Warenkorb"-Button gegen einen Link zum Produkt mit benutzerdefiniertem Text ausgetauscht werden, kann optional für einfache, variable und / oder gruppierte Produkte aktiviert werden
- Erweitert: sevDesk Add-On: Individuelle Zahlungskonten für Zahlungsmethoden
- Erweitert: sevDesk Add-On: Individuelle Buchungskonten für Produkte
- Erweitert: lexoffice Add-On: Berücksichtigung der Buchungskonten bei steuerfreien Ausfuhrlieferungen, steuerfreien innergemeinschaftlichen Lieferungen
- Erweitert: Optional anklickbare Checkout Checkboxen, Protokollierung der vom Kunden angeklickten Checkboxen als private Bemerkung in der Bestellung
- Erweitert: GTIN für Produkte
- Verbessert: Kompatibilität Theme Support Kanna
- Verbessert: Kompatibilität Theme Depot
- Verbessert: Kompatibilität Theme Appetito
- Verbessert: Kompatibilität Theme Yolo Robino
- Verbessert: Kompatibilität Theme Minera
- Verbessert: Kompatibilität Theme Elaine
- Verbessert: Kompatibilität Theme Theme Sober
- Verbessert: Kompatibilität Theme TM Robin
- Verbessert: Kompatibilität Theme Astra in Zusammenhang mit Astra Pro Plugin und Page Builder Plugins
- Verbessert: Kompatibilität Theme Calafate
- Verbessert: Kompatibilität Theme Gioia
- Verbessert: Kompatibilität Theme Uncode
- Verbessert: Kompatibilität Theme Superfood
- Verbessert: Kompatibilität Theme Grosso
- Verbessert: Kompatibilität Theme DieFinhutte
- Verbessert: Kompatibilität Theme Verdure
- Verbessert: Kompatibilität Theme Mimosa
- Verbessert: Kompatibilität Theme Enfold mit Avia Advanced Layout Builder
- Verbessert: Kompatibilität Theme CiyaShop
- Verbessert: Kompatibilität Theme Oxygen
- Verbessert: Kompatibilität Theme Highlight Pro
- Verbessert: Kompatibilität Plugin Stripe
- Verbessert: Kompatibilität Plugin WooCommerce Product Bundles
- Verbessert: Kompatibilität Plugin Product Filter for WooCommerce
- Verbessert: Kompatibilität Plugin WooCommerce Tab Manager & LMIV Add-On
- Verbessert: Kompatibilität Plugin Divi BodyCommerce
- Verbessert: Kompatibilität Plugin JetWooBuilder For Elementor
- Verbessert: Kompatibilität Plugin UPS WooCommerce Shipping
- Verbessert: Kompatibilität Plugin Polylang: Übersetzung der Lieferzeiten mittels "Übersetzung von Zeichenketten"
- Verbessert: Kompatibilität WPBakeryVisualComposer
- Verbessert: Kompatibilität Plugin Elementor: SEPA Mandat E-Mail hatte ggf. falschen Inhalt, Filter von Elementor in dieser E-Mail deaktivieren
- Verbessert: Verwendung einer anderen PHP-Bibliothek für die Erstellung der SEPA-XML-Dateien
- Verbessert: "Invalid"-CSS-Klassen der Checkboxen im Checkout angepasst
- Verbessert: Ausgabe der German Market Daten der Produkte in Widgets nach dem Preis
- Verbessert: Checkbox Klassen im Checkout hinzugefügt
- Fix: Der "jeweils xy Euro"-Preis wurde verbessert, sodass Dritt-Plugins auf den Preis Einfluss nehmen
- Fix: Onlinebuchführung Add-On: API Domain angepasst
- Fix: Rücksende- / Lieferschein Add-On: Platzhalter bei Bemerkung für Rechnungsnummern funktionierten nicht
- Fix: Frontend-Java-Script für Variantenauswahl angepasst, funktioniert jetzt auch auf "Nicht-Produkt-Seiten"
- Fix: EU VAT ID CHECK: Feld wurde beim ersten Aufruf angezeigt, wenn Basisland = Rechnungland
- Fix: EU VAT ID CHECK: Feld wird nicht mehr für Rechnungsländer außerhalb der EU angezeigt
- Fix: Zahlungsanweisungen für SEPA wurden nicht in E-Mails ausgegeben
- Fix: sevDesk API Anpassung (voucher already paid)
- Fix: Lieferzeit für virtuelle Produkte anzeigen
- Fix: Automatische Übertragung bei sevDesk und lexoffice nutzen jetzt den Hook 'woocommerce_order_status_changed' statt 'woocommerce_order_status_completed', um API Aufrufe zu berücksichtigen
- Fix: SEPA Felder wurden im Backend nicht angezeigt, wenn die manuelle Bestellannahme aktiviert ist
- Fix: lexoffice Add-On: Steuerberechnung bei Stornos korrigiert
- Fix: Validierung der Datenschutz-Checkbox bei Kundenregistrierung nicht bei API-Aufrufen durchführen
- Fix: Die deaktivierte SEPA Zahlungsart wurde angezeigt, wenn in der Einstellung "Für Versandarten verfügbar machen" Optionen gesetzt sind
- Fix: Bei der Einstellung "Wenn eine Variante des variablen Produktes gewählt wurde" wurden Kurzbeschreibung und Lagerbestand einer Variante nicht angezeigt, wenn die Option "Ersetze den Preis des variablen Produktes durch den Preis der Variante" gewählt ist
- Fix: Korrigierte Lieferzeitenausgabe bei Lagerbestand von 0
- Fix: Übersetzungen im Backend korrigiert
- Fix: Wenn "Anteilige Steuerberechnung für Nebenleistungen" ausgestellt ist, wird Versand besteuert, obwohl steuerfreie innergemeinschaftl. Lieferung vorliegt
- Fix: Bestellen Prüfen Seite: Kehrt man zur Kassen-Seite zurück, muss die Auswahl "ship to another address" und Eingaben bei "customer notes" berücksichtigt werden
- Fix: Rechnungsnummer Add-On: Rechnungsnummer wird ab jetzt bei der Erstellung eines Liefer- oder Retourenscheines nur noch generiert, wenn die entsprechenden Platzhalter im Dokument verwendet werden
- Fix: Kauf auf Rechnung, Manuelle Bestellannahme und 100% Gutschein: Nach der manuelle Bestellannahme darf in der E-Mail keine Zahlungsaufforderung enthalten sein, da die Bestellung keine Zahlung benötigt

= 3.8.1 =
- Erweitert: Neue Optionen zur Preisdarstellung variabler Produkte bei Auswahl der Variante
- Erweitert: Zahlungsweise SEPA: IBAN-Maskierung für den Admin in E-Mails lässt sich an- und ausstellen
- Erweitert: sevDesk Add-On: Optional als neue Optionen: Automatisches Senden einer Bestellung, wenn diese als fertiggestellt markiert wurde, sowie automatisches Senden eines Refunds, wenn dieser erstellt wurde
- Erweitert: sevDesk Add-On: Kundendaten senden bei Gastbestellungen in WooCommerce ermöglicht
- Erweitert: Text für "Hinweis für Versand in Nicht-EU-Länder" kann direkt im Backend eingestellt werden
- Verbessert: WooCommerce-Template emails/emai-order-items.php wird nicht mehr durch German Market überschrieben
- Verbessert: Template der Bestellung Prüfen Seite (second-checkout2.php) angepasst: Zahlungsweisen in ein DIV geschachtelt
- Verbessert: Grundpreis: Neuer Filter wgm_ppu_wc_price_args, um die Ausgabe zu manipulieren, z.B. für Ausgabe mit 3 Nachkommastellen
- Verbessert: Kompatibilität Theme Avada
- Verbessert: Kompatibilität Theme DFD Native
- Verbessert: Kompatibilität Theme Astra
- Verbessert: Kompatibilität Theme Ciloe
- Verbessert: Kompatibilität Theme Ordo
- Verbessert: Kompatibilität Theme Hypermarket
- Verbessert: Kompatibilität Theme Planetshine Polaris
- Verbessert: Kompatibilität Theme Flatsome
- Verbessert: Kompatibilität Theme iustore
- Verbessert: Kompatibilität Theme Electro
- Verbessert: Kompatibilität Theme Justshop
- Verbessert: Kompatibilität Plugin WooCommerce PDF Vouchers und Rechnungs PDF Add-On
- Verbessert: Kompatibilität mit Währungs-Plugins und der Anzeige des Einzelpreises (jeweils xy Euro)
- Verbessert: Kompatibilität WPML: Download der PDFs richtet sich jetzt entweder nach dem Sprachumschalter im Backend oder der Sprache der Bestellung, dazu gibt es eine neue globale Option
- Verbessert: Rechnungs PDF Add-On: Neuer Filter 'wp_wc_invoice_pdf_clear_cache_time', um die Caching-Zeit zu erhöhen, damit alle PDFs auch an die E-Mails angehangen werden, wenn auch "Weitere PDFs" aktiviert sind
- Fix: Rechnungs PDF Add-On: Speichern der Einstellung "Globale Fortlaufende Nummer" korrigiert
- Fix: Rechnungs PDF Add-On: Fehler in der Überprüfung, ob es sich um eine Test-PDF handelt korrigiert
- Fix: Rechnungs PDF Add-On: Speichern des PDF-Inhalts als Meta-Daten korrigiert
- Fix: Rechnungs PDF Add-On: In Storno PDFs kann im Willkommens-Text jetzt der Platzhalter {{order-total}} korrekt verwendet werden
- Fix: Rechnungs PDF Add-On: Die Gesamtsumme berücksichtigt jetzt keine Rückerstattungen mehr
- Fix: Rechnungsnummer Add-On: Falsche Stornonummer in Rechnungs-PDFs wurde korrigiert
- Fix: Rechnungsnummer Add-On: Die lokalisierte Ajax-Variable wurde spezieller benannt, um Inkompatibilitäten auszuschließen
- Fix: Rücksende- / Lieferschein Add-On: Korrigierter Abstand von Adressen zum Inhalt, wenn bei manuellen Bestellungen keine Rechnungsadresse hinterlegt war
- Fix: Rücksende- / Lieferschein Add-On: HTML Tags in Kurzbeschreibung werden nicht mehr angezeigt
- Fix: lexoffice Add-On: Fehler bei Übertragung des Fälligkeitsdatum korrigiert
- Fix: Die Optionen "Versandhinweis deaktivieren" und "Alternativer Versandhinweis" sind jetzt auch verfügbar bei einem Externen/Affiliate-Produkt
- Fix: Double Opt In Kundenregistrierung: Weiterleitung verbessert in Hinsicht auf andere Plugins, die die Weiterleitung beeinflussen
- Fix: Double Opt In Kundenregistrierung: Passwörter ggf. korrekt in E-Mails anzeigen
- Fix: Im Backend hatten die Styles für die An/Aus-Schalter zu allgemeine Klassen, die ggf. durch andere Plugins überschrieben wurden
- Fix: Backend JavaScript wird nicht mehr überall geladen, behebt Inkompatibilitäten mit anderen Plugins
- Fix: Zahlungsweise SEPA: Stets die Bestellnummer (get_order_number()) statt ID verwenden (get_id()), um Dritt-Plugins zu berücksichtigen
- Fix: Zahlungsweise SEPA: Verfügbarkeit der Zahlungsweise, wenn kein Versand erforderlich ist, korrigiert
- Fix: Bessere Handhabung der Standardlieferzeit
- Update: Sprachdateien

= 3.8 =
- Erweitert: Altersfreigabe für Produkte
- Erweitert: Automatische Grundpreisberechnung: WooCommerce Gewichte der Produkte nutzen
- Erweitert: Verwendung von Platzhaltern für Vor- und Nachname des Kunden im Text der Bestelleingangsbestätigungsmail
- Erweitert: Neue Option "Text: Hinweis bzgl. geschätzter Steuern und Versandkosten", der im Warenkorb angezeigte Text kann nun im Backend eingestellt werden
- Erweitert: Rechnungs PDF Add-On: Neue Option "Ausgabe der Zahlungsanweisungen verhindern"
- Erweitert: Kleinunternehmerregelung: Gesonderte Steuerhinweis für Externe/Affilate-Produkte im Backend einstellbar
- Erweitert: sevDesk Add-On: Auswahl von Buchungskonten als neue Einstellungen
- Verbessert: CSS-Style zur Ausgabe der Notiz für digitale Produkte im Checkout verbessert
- Verbessert: Kompatibilität Divi Page Builder
- Verbessert: Kompatibilität Theme Variegated
- Verbessert: Kompatibilität Theme Ordo
- Verbessert: Kompatibilität Theme Supro
- Verbessert: Kompatibilität Theme Erado
- Verbessert: Kompatibilität Theme Bazien
- Verbessert: Kompatibilität Theme Technics
- Verbessert: Kompatibilität Theme Adorn
- Verbessert: Kompatibilität Plugin Autoptimize
- Verbessert: Kompatibilität Plugins YITH WooCommerce Best Sellers und YITH WooCommerce Wishlist
- Verbessert: Add-On EU USTID-Nr. Check: Kompatibilität mit WireCard
- Fix: HTML-Markup der Produktdaten verbessert, keine <div>-Tags mehr in <p>-Tags
- Fix: HTML-Markup der Checkboxen im Checkout angepasst
- Fix: Rechnungs PDF Add-On: Gibt es im Shop keine Bestellungen, gab es PHP FATAL ERRORs bei Download der Test-PDFs
- Fix: Rechnungs PDF Add-On: Darstellung von Anführungszeichen oben und unten
- Fix: Rechnungs PDF Add-On: Darstellung von nicht quadratischen Produktbildern
- Fix: Rechnungs PDF Add-On: Steuerhinweise "Innergemeinschaftliche Lieferung" / "Ausfurhlieferung" hatten nicht die eingestellte Schriftart
- Fix: Rechnungs PDF Add-On: Ausgabe der korrekten Währung, wenn Plugins zum Wechseln der Währung aktiv sind
- Fix: EU USTID-Nr. Check Add-On: Position des Input-Feldes im Checkout korrigiert
- Fix: Rechnungsnummer Add-On: Vorschau der Rechnungsnummern im Backend korrigiert
- Fix: Verwendung von current_time() statt time() zur Verwendung der eingestellten WordPress-Zeit im gesamten Plugin
- Fix: Meldung auf "Bestellung prüfen Seite" ist jetzt eine Notice, kein Error mehr
- Fix: Manuelle Bestellannahme: E-Mail Versand verbessert
- Fix: "Notiz für deine Kunden auf der letzten Seite des Bestellvorgangs" wurde nur angezeigt, wenn die "Bestellung prüfen"-Seite aktiviert ist
- Update: Sprachdateien

= 3.7.1 =
- Erweitert: Rücksende- / Lieferschein Add-On: Optionale Anzeige von Artikelnummer, Gewicht (nur im Lieferschein), Auslieferungsdatum (nur im Lieferschein)
- Erweitert: Rücksende- / Lieferschein Add-On: Rücksendeschein auch an die E-Mails "Bestellbestätigung" & "Bestellung wartend" anhängen können
- Erweitert: Rücksende- / Lieferschein Add-On: Optionale Anzeige der Kurzbeschreibung eines Produktes
- Erweitert: lexoffice Add-On: Optional als neue Optionen: Automatisches Senden einer Bestellung, wenn diese als fertiggestellt markiert wurde, sowie automatisches Senden eines Refunds, wenn dieser erstellt wurde
- Erweitert: Neue Option: Zeige Artikel-Einzelpreise in Bestellungen
- Erweitert: Neue Option: Steuerklasse für Verandkosten und Gebühren, wenn "Anteilige Steuerberechnung für Nebenleistungen" ausgestellt ist
- Erweotert: Rechnungs PDF Add-On: Filter für die Item Meta Args, um deren Ausgabe mit Filter anzupassen
- Verbessert: Kompatibilität Theme Ecode
- Verbessert: Kompatibilität Theme Alishop
- Verbessert: Kompatibilität Theme Astra
- Verbessert: Kompatibilität Theme OceanWP
- Verbessert: Kompatibilität Plugin Elementor
- Verbessert: Automatische Grundpreisberechnung: Komplette Produktmenge lässt sich als Dezimalzahl angeben anstatt nur als Ganzzahl
- Fix: Double-Opt-In: Kundenregistrierung klappt nicht in angepassten Themes, wenn die Registrierung und deren Weiterleitung nicht auf einer Mein-Konto-Seite stattfindet
- Fix: sevDesk Add-On: Kein Steuersatz beim Versand, wenn „Anteilige Steuerberechnung auf Nebenleistung“ aktiviert ist, MwSt. sich basierend auf das Shop-Basisland berechnet, der Käufer aus einem anderen Land bestellt und für dieses Land kein Steuersatz hinterlegt ist
- Fix: Produktbilder sind doppelt in Rechnungs-PDFs, wenn auch die Anzeige in Bestellzusammenfassungen aktiviert ist
- Fix: Fehlendes Rechnungsdatum in Storno-PDFs, wenn die PDF-Erstellung erstmalig beim Senden an sevDesk als Beleg erfolgt
- Fix: Mit neusten PHP-Versionen gibt es Fehler bei der Überprüfung, ob die PDF-Rechnung auf einer Test-PDF oder echten Bestellung beruht
- Fix: lexoffice Add-On: Falsches Datum in Rechnungs-PDF (es wird das Fälligkeitsdatum ausgegeben), wenn die PDF-Erstellung erstmalig beim Senden an lexoffice erfolgt
- Fix: Rücksende- / Lieferschein Add-On: Sehr lange Artikelnummern brechen nicht in der Spalte um
- Fix: Kleinere Bugfixes
- Update: Sprachdateien

= 3.7 =
- Erweitert: Automatische Grundpreisberechnung
- Erweitert: Kauf auf Rechnung - Verfügbarkeit nach Warenkorbwert
- Erweitert: Produktbilder im Warenkorb, auf Kasse-Seite, in Bestellzusammenfassung und E-Mails lassen sich optional aktivieren bzw. deaktivieren
- Erweitert: Optionale Anzeige von Produkteigenschaften, die nicht für Varianten verwendet werden
- Erweitert: Es kann optional ein alternativer Versandkostenhinweis im Produkt eingegeben werden, der anstatt des allgemeinen Hinweises ausgegeben wird
- Erweitert: Bestätigungsmail: Betreff, E-Mail Kopfzeile und E-Mail Text können im Backend direkt eingestellt werden
- Verbessert: Neuer Hinweistext für Servicegebühren der Zahlungsarten: Die Funktion der Servicegebühr wird aufgrund gesetzlicher Neuregelungen bis zum 31.12. ausgebaut
- Verbessert: Der optische Schalter im Backend für Optionen besitzt jetzt auch den Text „Aus | Ein“, damit er durch einen Screen-Reader bedienbar wird
- Verbessert: Kompatibilität Theme Amely
- Verbessert: Kompatibilität Theme Iustore
- Verbessert: Kompatibilität Plugin Wirecard Checkout Seamless: Korrekte Ausgabe der Zahlungsweise auf „Bestellung Prüfen“-Seite
- Verbessert: Kompatibilität mit anderen Plugins, die auch DOMPDF nutzen
- Verbessert: Versionsnummern von JS und CSS-Dateien entsprechen jetzt der German Market Version, nicht mehr der WordPress-Version
- Verbessert: EU USTID-Nr. Check Add-On: Bei Eingabe einer ungültigen VAT ID, ist der Checkout nicht mehr ausführbar, es wird eine Meldung ausgegeben
- Fix: Wird die WordPress-Sprache umgestellt, waren im Backend keine Maßeinheiten beim Grundpreis mehr auswählbar
- Fix: sevDesk Add-On: Bei deaktivierter anteiliger Steuerberechnung für Nebenleistungen kam es zu Rundungsfehlern bei der Übertragung des Steuersatzes
- Fix: 1&1 Buchführung Add-On: Verbesserung der Übertragung von Steuern und Steuersätzen
- Fix: Rechnungs PDF Add-On: Korrekte Bezeichnung des Steuersatzes ausgeben, wenn Ausgabe der Nettopreise aktiviert ist
- Fix: Veralteten WooCommerce Filter „woocommerce_create_product_variation“ ersetzt
- Fix: Fehlerhafte Ausgabe des Steuer-Labels behoben, wenn Brutto-Versankosten aktiv ist und anteiliger Steuerberechnung für Nebenleistungen deaktiviert ist
- Fix: Shortcodes wurden in E-Mails und Rechnungs PDF nicht korrekt ersetzt, wenn der E-Mail-Versand im Backend ausgelöst wurde
- Fix: Fehlende Klassen 'woocommerce-invalid woocommerce-invalid-required-field' für Checkboxen im Checkout bei ungültiger Validierung
- Fix: Kleinere Bugfixes
- Update: Sprachdateien

= 3.6.3 =
- Erweitert: Rechnungsnummer Add-On: Filter für update_post_meta, um eigene Rechnungsnummer abzuspeichern
- Erweitert: Rechnungs PDF Add-On: Template Totals-<tr>s mit Klassen (order_total_key), damit diese gestylt werden können
- Verbessert: Rechnungs PDF Add-On: Durch die Methode WP_WC_Invoice_Pdf_Email_Attachment::repair_filename wird nun auch im Backend, ein Slash im Dateiname mit einem “-”" ersetzt
- Verbessert: SEPA Zahlungsart: Wenn BIC kein Pflichtfeld ist, erfolgt keine Ausgabe der SEPA Daten in den Customer Details der Mails
- Verbessert: Wenn “Kauf auf Rechnung” aktiviert ist => Keine abweichende Lieferadresse ermöglichen
- Verbessert: Kompatibilität Plugin Heidelpay
- Verbessert: Kompatibilität Theme Kalium
- Verbessert: Kompatibilität Theme handmade-shop
- Verbessert: Kompatibilität Theme Zass
- Verbessert: Kompatibilität Theme Ronneby
- Verbessert: WPML: Wenn die WPML Standardsprache nicht EN ist, wird keine Lieferzeit ausgegeben.
- Fix: Lexoffice Add-On: Ungültige Belegsummen, wenn es Produkte mit negativen Gesamtwert gibt
- Fix: Wenn die “Checkbox - Für digitale Inhalte” deaktiviert ist, darf “Für digitale Inhalte - Wiederholung” nie wirken
- Fix: Rechnungs PDF Add-On: SVG-Bilder lassen sich nun korrekt ausgeben.
- Fix: Retourenschein/Lieferschein Add-On: Keine Preise (jeweils xy Euro) in Retourenschein/Lieferschein.
- Fix: Rechnungs PDF Add-On: Benutzerdefinierter Platzhalter wird nicht ersetzt, wenn Rechnungsnummer aktiviert ist
- Fix: Kleinere Bugfixes
- Update: Sprachdateien


= 3.6.2 =
- Feature: Checkbox vor Kundenbewertung
- Erweitert: Versandart Abholung vor Ort -> Keine Checkbox “Daten an Versanddienstleister senden”
- Erweitert: Per Filter SEPA Raw Daten aus Datenbank ausgeben im Backend
- Erweitert: SEPA Daten in REST API ausgeben
- Erweitert: Neuer Filter in Rechnungs PDF bei Product Meta, um benutzerdef. Daten hinzufügen zu können
- Erweitert: Checkout Checkboxes - Alle Platzhalter auch bei “Benutzerdefinierter Checkbox” und “Mein Konto Registrierung” können verwendet werden
- Erweitert: Rechnungs PDF Add-On: Per Filter statt Rechnungsadresse Versandadresse im Briefkopf
- Verbessert: Telefon Pflichtfeld Option ausgebaut, da es ab WooCommerce 3.4 Bestandteil ist.
- Verbessert: Kompatibilität Theme Savoy
- Verbessert: Kompatibilität Theme Elessi
- Verbessert: Kompatibilität Plugin WP-Members
- Verbessert: Google Fonts aus German Market CSS entfernt
- Fix: Im Checkout * für alle Checkboxen
- Fix: SEPA Daten wurden in der Bestellung Bearbeitenansicht nicht immer ausgegeben
- Fix: Kleinere Bugfixes
- Update: Sprachdateien


= 3.6.1 =
- Fix: Fehler in den Sprachdateien behoben


= 3.6.0 Blog: https://marketpress.de/2018/dsgvo-datenschutz-woocommerce/ =
- Feature: Optionale Datenschutzcheckbox und individuelle Checkbox
- Feature: Optionale Datenschutzcheckbox für das Registrieren über „Mein Konto”
- Enhancement: Mustertext Datenschutz und AGB sind überarbeitet
- Enhancement: EU USTID-Nr. Check Add-On: Schlägt die Verifizierung mit dem EU Server fehl, wird die Zusammensetzung der USTID-Nr. auf Korrektheit geprüft
- Enhancement: lexoffice Schnittstelle: Es ist nun möglich, Rechnungsdatum als Belegdatum anzugeben
- Enhancement: SEPA Mandat - Es ist nun per Filter möglich, dass keien SEPA-Mail versendet wird
- Enhancement: Rechnungs PDF: Es ist nun möglich, externe Medieninhalte in die PDF einzufügen
- Enhancement: Rechnungs PDF: DejaVu wird nun mit als Schriftart angeboten
- Verbessert: Kompatibilität Theme Peony
- Verbessert: Kompatibilität Theme Aurum
- Verbessert: Kompatibilität Plugin YITH E-Mail Templates
- Verbessert: Kompatibilität Plugin WooCommerce Product Bundles
- Fix: Kleinere Bugfixes
- Update: Sprachdateien

= 3.5.8 =
- Erweitert: Rücksende-/Lieferschein PDF: Ein Filter, um beliebiges unter den Lieferschein hinzuzufügen
- Verbessert: Rechnungs-PDF Add-On: Kompatibilität mit anderen Plugins, die DOMPDF nutzen
- Fix: Kleinere Bugfixes

= 3.5.7 =
- Erweitert: Filter für die Ausgabe der Lieferzeit
- Erweitert: Rechnungs PDF Add-On: Action hinzugefügt, um DOMPDF Optionen zu setzen
- Erweitert: Manuelle Bestellannahme: Der String "Zahle jetzt" in der E-Mail ist jetzt filterbar
- Erweitert: Rücksende-/Lieferschein PDF: Neue Filter für Lieferadresse und Bestelldatum
- Verbessert: Rechnungs PDF Add-On: Es gibt für Storno-PDFs einen eigenen Willkommenstext
- Verbessert: Rechnungs PDF Add-On: Rückerstattungen werden nur noch auf der entsprechenden Storno-PDF angezeigt, nicht mehr auf der eigentlichen Rechnungs-PDF
- Verbessert: Kompatibilität Theme Hestia
- Verbessert: Kompatibilität Theme Sober
- Verbessert: Kompatibilität Theme XStore
- Verbessert: Kompatibilität Theme Woldmart
- Verbessert: Kompatibilität Plugin WooCommerce Delivery Slots
- Verbessert: EU USTID-Nr. Check Add-On: Wird durch die API des europa Servers ein Error zurückgegeben, wird die VAT ID zumindest auf korrekte Zusammensetzung überprüft
- Fix: Bei einer Variation wird jetzt die Kurzbeschreibung des Eltern-Produktes ausgegeben
- Fix: Die Lieferzeiten wurden mit WPML nicht mehr korrekt übersetzt
- Fix: Rechnungs PDF Add-On: Die Einstellung "Stornodatum" wird nun korrekt ausgegeben
- Fix: sevDesk Add-On: Kleinere Steuerberechnungsprobleme behoben
- Fix: Double-Opt-In: Funktioniert nun auch mit "einfachen" Permalinks
- Fix: Bei der Option "Für Versandarten verfügbar machen" gibt es nun die Auswahlmöglichkeit "Kein Versand erforderlich (für virtuelle Bestellungen)"
- Fix: Kleinere Bugfixes
- Update: Sprachdateien


= 3.5.6 =
- WooCommerce 3.3.1 Kompatibel
- Erweitert: Rechnungs PDF Add-On: Filter hinzugefügt, um eigene Fonts einzufügen
- Update: Textvorlage Impressum: der OS Plattform-Link wurde angepasst
- Fix: Das Template cart.php wurde mit WooCommerce Update angepasst


= 3.5.5 =
- WooCommerce 3.3 Kompatibel
- Erweitert: Neuer Filter: Ob Versandklasse auch wirklich ausgeblendet werden soll, wenn die Option: "Blende Versandkostenpauschalen aus, wenn eine kostenlose Lieferung verfügbar ist" aktiviert ist.
- Erweitert: Rechnungsnummer Add-On: Neuer Filter: Ändern von E-Mail Betreff & Überschrift unterbinden können.
- Erweitert: Checkboxen lassen sich nun optional über der Bestellzusammenfassung auf der Kassenseite anzeigen.
- Verbessert: Kompatibilität Plugin WPML
- Verbessert: Kompatibilität Plugin Learnpress
- Verbessert: Kompatibilität Plugin YITH WooCommerce Added to Cart Popup Premium
- Verbsssert: Kompatibilität Theme The7
- Verbessert: Kompatibilität Theme Support Hestia Pro
- Fix: Kleinere Bugfixes
- Update: Sprachdateien


= 3.5.4 =
- Erweitert: SEPA-Lastschrift: Action nach Konstruktor des Gateways
- Erweitert: SEPA-Lastschrift: BIC als Checkout-Feld ist ein erforderliches Feld lässt sich nun optional per Filter ausstellen
- Verbessert: Kompatibilität Theme Support Hestia Pro
- Verbessert: Manuelle Bestellannahme: Handling mit dem "Bezahle jetzt" Link
- Fix: Kleinere Bugfixes
- Update: Sprachdateien


= 3.5.3 =
- Erweitert: Rechnungs PDF Add-On: Produkt-Maße und -Gewicht können nun optional in der PDF angezeigt werden
- Erweitert: Checkout Filter für das validieren der “Widerrufscheckbox” wenn diese nicht aktiv sein soll und die Bestellung prüfen Seite aktiv ist
- Erweitert: Fälligkeitsdatum bei SEPA-XML
- Erweitert: Rechnungs PDF Add-On: Produktbilder in der Rechnungs PDF
- Erweitert: SEPA-Lastschrift: Es ist nun möglich im Mandatstext mit Platzhalter [amount] für den Betrag auszugeben
- Verbessert: Kompatibilität mit Subscriptions - Für “Switch”-Bestellungen keine Rechnungsnummer generieren bzw. optional ausstellen werden können
- Verbessert: Kompatibilität mit dem Plugin WPGlobus und WooCommerce WPGlobus
- Verbessert: Kompatibilität mit dem Plugin Woo Floating Cart
- Verbessert: Kompatibilität mit dem Plugin WooCommerce TabManager und unserem LMIV Add-On
- Verbessert: Kompatibilität mit dem Plugin Page Builder by SiteOrigin
- Verbessert: Kompatibilität mit dem Theme VG Vegawine
- Verbessert: Kompatibilität mit dem Theme Peddlar
- Verbessert: Kompatibilität mit dem Theme savoy
- Verbessert: Kompatibilität mit dem Theme Kryia
- Fix: lexoffice Add-On: Bei einer bestimmten Bestellkonzellation, war es nicht möglich den Beleg an lexoffice zu schicken
- Fix: Rechnungs PDF Add-On: “https, http” Problem auf speziellen Kundenserver beim Einbinden eines Bildes in Rechnungs PDF
- Fix: Retourenschein/Lieferschein Add-On: Bindestrich “–” wurde in PDFs nicht korrekt dargestellt (? wurde ausgegeben)
- Fix: EU USTID-Nr. Check: Im Validator sind falsche Ländercodes
- Fix: Kleinere Bugfixes
- Update: WooCommerce cart.php Template
- Update: Sprachdateien


= 3.5.2 =
- Erweitert: Rücksende- / Lieferschein Add-On: "Kundennotizen" lass sich nun im Lieferschein ausgegeben
- Erweitert: Add-Ons Belegschnittstellen: Es ist nun möglich anstatt der Bestelnummer die Rechnungsnummer zu senden
- Erweitert: Rücksende- / Lieferschein Add-On: Backend-Download Retoureschein lässt sich nun deaktvieren
- Verbessert: Rechnungs-PDF Add-On: Preis pro Einheit lässt sich nun in der Rechnungs-PDF ausstellen
- Verbessert: Rechnungs-PDF Add-On: Im Wilkommenstext ist nun der Platzhalter {{order-total}} für Gesamtbetrag möglich
- Verbessert: Kompatibilität Theme Woodstroid
- Verbessert: EU USTID-Nr. Check Add-On: Das Vat ID Feld im Checkout kann nun mit einem Filter zu einem Pflichtfeld werden
- Fix: lexoffice Add-On: Wenn das Due Date schon gespeichert wurde, wird kein voucher date generiert, das führt zu Error beim senden: "missing_entity: scource: voucherDate.."
- Fix: Kleinere Bugfixes
- Update: Sprachdateien


= 3.5.1 =
- Erweitert: Es ist nun möglich den Link zu Datenschutz bei "Hier erfährst du mehr zu den Versandkosten, Zahlungsweisen und unserer Widerrufsbelehrung." einzufügen
- Erweitert: Die Details vor der Bestellübersicht, wie z.B. die Zahlungsanweisungen, lassen sich nun mit Filter ausstellen
- Erweitert: Für die Versandadresse gibt es jetzt bei WooCommerce -> German Market -> Rechnungs PDF -> Rechnungsinhalt nun die Option Lieferadresse
- Erweitert: Mehr Filter um die Checkout-Checkboxen zu manipulieren
- Erweitert: Rechnungs-PDF Add-On: Neuer Filter vor der Generierung der PDF hinzugefügt, um Generierung der PDF zu unterbinden
- Erweitert: Filter für Lieferzeitangabe, wenn das Produkt "Out of stock" (Nicht auf Lager) ist
- Erweitert: Lieferschein im Briefumschlag mit Fenster nun nutzbar
- Erweitert: Filter, um "zzgl. Versand"-Link ohne target=_blank zu gestalten
- Erweitert: EU USTID-Nr. Check Add-On: Filter, um das Verstecken und Löschen des VAT ID Feldes zu verhindern, wenn Kundenrechnungsland und Shopbasisland übereinstimmen
- Erweitert: Filter, Lieferzeiten bei variablen Produkten, um die Ausgabe Lieferzeit des Elternproduktes zu ermöglichen
- Verbessert: Kompatibilität Woocomerce Composite Products Plugin
- Verbessert: Kompatibilität Woocomerce Klarna
- Verbessert: Kompatibilität Polylang
- Verbessert: Kompatibilität WPML
- Verbessert: Kompatibilität Theme Avada Checkout
- Verbessert: Kompatibilität Theme Envision
- Verbessert: Kompatibilität Theme Fluent
- Verbessert: Lexoffice Add-On: Bessere Fehlermeldung, wenn Bestellungen mit einem Gesamtbetrag von 0 Euro gesendet werden
- Verbessert: Double Opt-In für Kundenregistrierung
- Fix: Steuerberechnung bei Kleinst-Cent-Beträgen
- Fix: lexoffice Add-On: Bei kleineren Beträgen kam es zu Fehlern in der Rundung
- Fix: EU Mehrwertsteuer Add-On: Berechnung im Checkout wegen geänderten Klasseneingenschaften
- Fix: Kleinere Bugfixes
- Update: Sprachdateien


= 3.5 (Releasepost: https://marketpress.de/2017/german-market-sepa-archive-double-opt-in) =
- Feature: Double Opt-In für Kundenregistrierung
- Feature: Archivierung von SEPA-Mandaten
- Erweitert: Kauf auf Rechnung - Verfügbarkeit im Checkout optional erst ab 3. Bestellung
- Erweitert: Bulkdownload für Liefer- und Rücksendescheine
- Erweitert: Der Hinweistext für Kleinunternehmerregelung lässt sich nun im Backend anpassen
- Erweitert: Das Lexoffice Add-On wurde um einen Filter erweitert, damit auch Belege an lexoffice gesendet werden können, obwohl die Bestellung in WooCommerce noch nicht als “fertiggestellt” markiert wurde
- Verbessert: Option “German Market Checkout Template erzwingen” greift nun auch auf das Template “checkout/terms”
- Verbessert: Kompatibilität mit WooCommerce Subscriptions und Rechnungsnummer Add-On
- Verbessert: Kompatibilität WPML
- Verbessert: Kompatibilität Polylang
- Verbessert: Kompatibilität Theme Avada
- Verbessert: Kompatibilität Theme Superba
- Verbessert: Kompatibilität Theme WooDance
- Update: Sprachdateien
- Fix: Im Liefer- & Rücksendeschein wurden HTML Tags im Produktnamen ausgegeben
- Fix: Einheit (user unit = cm) bei body{ width: ... cm } fehlte
- Fix: EU VAT ID CHECK: Wenn die Klasse SoapClient nicht vorhanden ist, gibt es einen FATAL ERROR
- Fix: Kleinere weitere Bugfixes.


= 3.4.2 =
- Enhancement: Invoice PDF: Switch off the shipping address by filter
- Improved: Show shipping information to non-EU countries” for variable products in the wrong place
- Improved: Itemprop Markup removed
- Fix: WGM Template::attribute _in_product_name_order - make third parameter optional with default to false, because some themes only apply the filter with 2 instead of 3 parameters
- Fix: Invoice PDF: syntax error, unexpected ‘wp_wc_invoice_pdf_page_numbers’ (T_STRING) in PhpEvaluator.php (55): eval () ‘d code on line 5
- Fix: Small other bugfixes


= 3.4.1 =
- Improved: EU VAT Number Check: Handling of the VAT ID input field during checkout and being tax exempt when logging in
- Fix: Error when login fails on 1st checkout, avoid possibility to login on 2nd checkout page
- Fix: Small other bugfixes


= 3.4 =
- Feature: IT-Recht Kanzlei API
- Feature: Protected Shops API
- Feature: 1&1 Online Buchhaltung API
- Enhancement: EU VAT Number Check - Optional text for non-EU countries (Tax-exempt export delivery)
- Enhancement: Refund Number is editable
- Enhancement: Invoice PDF & Subcriptions: PDFs can be attached in emails of the Plugin "Subscriptions"
- Improved: Price per unit - better output and markup.
- Improved: Compatibility with WPML
- Improved: Purchase on account + cash on delivery + SEPA - notice at fee
- Improved: Small improvements to the lexoffice API
- Improved: Compatibility with Product CSV Importer
- Update: DOMPDF Version
- Fix: Incompatibility: Add-On “EU USTID Check” and “WooCommerce Amazon Pay Gateway”
- Fix: Invoice PDF - PDF Design is displayed incorrectly when the invoice is downloaded a second time
- Fix: Small other bugfixes


= 3.3.2 =
- Improved: Price per unit – better output and markup
- Improved: Compatibility with WPML
- Fix: Incompatibility: Add-On “EU USTID Check” and “WooCommerce Amazon Pay Gateway”
- Fix: VAT for variations of a variable product, PHP Warning
- Fix: Fatal error Uncaught TypeError: argument 2 passed to WGM_Fee :: add_fee_to_order_tax_totals () must be an instance of WC_Order
- Fix: “Digital Item Notice” was not displayed in the checkout without 2nd Checkout
- Fix: Invoice PDF – PDF Design is displayed incorrectly when the invoice is downloaded a second time
- Fix: Small other bugfixes


= 3.3.1 =
- Enhancement: Filter for email heading in Confirm Order email
- Enhancement: Filter for CC in emails
- Enhancement: SEPA Filter to prevent email sending to customer
- Enhancement: Added check whether $order is a refund in our hooks
- Improved: More notices for SEPA gateway
- Improved: Compatibility with WooCommerce Subscriptions
- Updated: Translations
- Fix: No schema.org data at related products
- Fix: “Learn more about shipping costs, payment methods and our revocation policy.” Incorrect/broken HTML markup
- Fix: GM UI Tooltips
- Fix: Wrong Itemprop in variable products
- Fix: EU Vat Checkout: Do not force parameter type
- Fix: Email Attachements: If these are activated but disabled by admins, the legal texts are also not attached to customer emails (thanks for your order) if an admin e-mail is sent at the same time
- Fix: SEPA XML Export: Wrong totals
- Fix: Small other bugfixes


= 3.3 =
- Feature: SEPA Direct Debit Mandate
- Feature: Billbee Add-On - Link to the order in Billbee
- Enhancement Improved German Market Checkout themes compatibility
- Enhancement: Cart Note: “You can use [link-shipping][/link-shipping], [link-payment][/link-payment]. [link-revocation][/link-revocation] to add the specific links.”
Is now customizable on the backend.
- Enhancement: lexoffice - In the case of the WooCommerce guests that shall be created in lexoffice, the e-mail address of the WooCommerce guest is used to check whether this is already present in lexoffice. If so, this lexoffice contact is used.
- Enhancement: lexoffice - Problems with guest users, if not as a collection in lexoffice
- Enhancement: Lexoffice - Better error output for debugging
Lexoffice: Better error output for debugging
- Enhancement: more hooks for loop -> product output
- Enhancement: Running Invoice Number - Filter for prefix and suffix before generation
- Improved: Hide other shipping methods at free shipping
- Improved: Compatibility with WooCommerce Composite Products
- Improved: Compatibility with WPML
- Improved: Compatibility with WordPress Theme “The Retailer”
- Updated: WooCommerce Templates
- Updated: Translations
- Fix: Incorrect output of the delivery time string, if this contains a comma as soon as the order is completed and the quantity of the line item in the purchase order is 1
- Fix: Purchase On Acccount - Order Status “On Hold” does not work
- Fix: FIC - alc. Is always stored in the order item when the order is completed, even if no value has been set
- Fix: Disable shipping notifications, does not store for variations of variable products, so it does not work in the frontend
- Fix: small Bugfixes lexoffice tax calculation
- Fix: small Bugfixes in Invoice and Refund PDF/Template
- Fix: order confirmation mail - thick dashes in the order table
- Fix: small other Bugfixes


= 3.2.3 =
- Fix: SevDesk FATAL ERROR at SKU (determine product first) from order item
- Fix: Shipping costs, taxes .. if the option is not an array, comes a PHP warning
- Fix: Due date, PHP ERROR by WC Changeover of time functions
- Fix: WooCommerce 3.0.1 compatibility
- Fix: small other Bugfixes


= 3.2.2 =
- Enhancement: Invoice Number - Double-digit year
- Enhancement: Landscape for Invoice PDF
- Enhancement: Daily reset of the invoice number
- Enhancement: Filter for FIC Frontend
- Enhancement: sevDesk - more filters to always mark the order as "open"
- Enhancement: Filter for additional text in the shopping basket "Here you will find .."
- Enhancement: More filters for Order Totals
- Enhancement: Action after invoice number creation
- Enhancement: Filter for delivery time string
- Enhancement: Filter for the backend no delivery time or (in each case xy Euro)
- Enhancement: Filter before and after PDF Template
- Enhancement: More filters at create-pdf.php
- Enhancement: New filter for PDF e-mail sending
- Enhancement: (In each case xy Euro) show only if quantity> 1
- Update: Translations
- Improved: Manual order
- Improved: Compatibility with WP All Import
- Improved: Compatibility with WPML
- Improved: Compatibility with Polylang
- Fix: After Quick Edit + Bulk Edit, product type returns to Digital
- Fix: Manual order acceptance - no shop admin mail
- Fix: EU VAT - Adjust tax rates
- Fix: Images in returns and delivery notes: http - https problem
- Fix: sevDesk - Incorrect tax and tax rates for small amounts due to incorrect rounding
- Fix: sevDesk: If the shipping is not taxed, false information to sevDesk
- Fix: WooCommerce 3.0 compatibility
- Fix: small UI Bugfixes
- Fix: small other Bugfixes


= 3.2.1 =
- Enhancement: Additional text: Tax free intracommunity delivery
- Enhancement: New filter in WGM_Shipping to manage rounding problems
- Update: Translations
- Fix: Legal text templates
- Fix: No invoice-pdf-download-button on thank-page for not logged in users
- Fix: Order button for payment method PayPal
- Fix: Checkout: At the first page call, "Send Order" is displayed
- Fix: Delivery note - The recipient and the sender are incorrect
- Fix: Static attachments can not be added
- Fix: In the refunds are payment instructions
- Fix: UST ID examination for the Baseland
- Fix: Error with manual order if no payment method is selected
- Fix: Filter to optionally not check if all variants of a variable product has the same delivery time
- Fix: Change of payment method with deactivated 2nd CO page has errors
- Fix: Grouped product - No display of the price range in the shop overview page
- Fix: Notice in the backend when saving orders with charges without taxes
- Fix: small other Bugfixes


= 3.2 =
- Feature: FIC Add-On
- Feature: VATIN Check Add-On
- Feature: Purchase On Acccount
- Feature: Manual order confirmation
- Feature: Due date for payments
- Enhancement: static mail attachments to the backend
- Enhancement: Invoice number - Resetting number at the end of the year and month
- Enhancement: Display of the price per unit display in the checkout
- Enhancement: The lexoffice API to create contacts
- Enhancement: Hide the legal texts in the mails
- Enhancement: Order confirmation for digital products
- Enhancement: Hide the delivery times
- Enhancement: Customizable order buttons and checkboxes
- Enhancement: Disable the 2nd checkout page
- Enhancement: BCC/CC for the shopadmin of the customer mails
- Enhancement: Deactivation of the telephone number as mandatory field
- Enhancement: Invoice PDF - Variables for the greeting text
- Improved: Compatibility with Visual Composer
- Improved: Compatibility with amazon Payments
- Improved: Compatibility with WPML
- Improved: Compatibility with FooEvents for WooCommerce
- Improved: Compatibility with FooEvents PDF Tickets
- Improved: Compatibility with Polylang
- Improved: Tax calculation for fees
- Improved: MarketPress Auto-Updater
- Improved: Debug as option for the invoice PDF add-on
- Update: Legal text templates
- Update: Translations
- Fix: small Bugfixes

= 3.1.1 =
- Update: Translations
- Fix: PDF E-mail attachments were output blank
- Fix: Error in the order overview Backend when the products are clicked

= 3.1 =
- New German Market UI
- Feature: lexoffice API – For sending voucher data
- Enhancement: Invoice PDF - Bulk download of invoices and refunds for shopadmin
- Enhancement: Invoice PDF - Extra field for Refund number, Refund ID, Refund date, Order number, Invoice number & Payment method
- Enhancement: Invoice PDF - Option Net Prices
- Enhancement: Invoice PDF - Delete button for delete saved invoice PDF content to allow regeneration of the invoice PDF content
- Enhancement: Invoice PDF -If the Small Trading Exemption is activated, an additional text is automatically displayed in the invoice
- Improved: Compatibility with Enfold Theme
- Improved: Compatibility with WooCommerce Subscriptions
- Improved: Invoice PDF - Display Order notes from the customer and for the customer
- Update: Translations
- Fix: small Bugfixes

= 3.0.2 =
- Feature: Avoid Free Items In Cart Option
- Improved: sevDesk: Filter, so the voucher is not marked as "open", even if it is considered paid in woocommerce
- Improved: Compatibility with WooCommerce Subscriptions
- Improved & Fix: Additional PDFs
- Fix: Backend Order overview page - Display errors in the order overview after product deletion
- Fix: Second-Checkout Template - fixed small bug with checkbox an back button
- Fix: DOMPDF did not load his autoloader, which caused a 500 error
- Fix: bug with Adobe Acrobat DC Pro

= 3.0.1 =
- Fix: Cash on Delivery display shipping method
- Fix: Invoice PDF Custom Google Fonts
- Fix: Display Tax if the tax settings are set to exclusive
- Fix: Removed coupon field from 2nd checkout page
- Fix: Invoice PDF Section text after the billing content now saves correctly html
- Fix: small Bugfixes

= 3.0 =
- Feature: Invoice PDF - Invoice PDF as an attachment to customer emails
- Feature: Invoice Number - Running Invoice Number to orders
- Feature: Return Delivery Note PDF - Retoure PDF as an attachment to customer emails
- Feature: Refunds – Refund system for customer credits and refunds
- Feature: sevDesk API – For sending voucher data
- Feature:  EU VAT Checkout - Display fixed gross prices and dynamically recalculate taxes (VAT) included in those prices. EU tax rates can be configured in and will be retrieved from the default WooCommerce tax table.
- Feature: Email attachments for Shop Admin Emails
- Feature: Display of the order time in the order overview and refund overview
- Feature: Translation Legal texts in English
- Feature: Delivery times linked to stock

- Improved: Default Delivery time for variants
- Improved: Sale Label can be now deactivated in the product loop pages and product single pages.
- Improved: Customizable in second checkout + email attachments

- Fix: Cash on Delivery endless loop fixed
- Fix: The class shop_table_responsive added in cart.php.

= 2.6.10 =
- Improved: Compatibility with EU VAT Number
- Update: MarketPress Auto-Updater
- Change: small revised translations

= 2.6.9 =
- Fix: hidden fields in second checkout
- Fix: checkboxes of Order-Pay Page
- Fix: display delivery time for variation
- Fix: display option delivery address by digital + downloadable proucts
- Removed action `add_product_short_desc_to_order_title_mail`
- Renamed: „Place binding order“ Button
- Change: small revised translations

= 2.6.8 =
- Fix: Problem with themes that use buffer ("ob_get_clean")
- Fix: VAT in variation
- Fix: Show Delivery Times Option
- Improved: Parameters for Digital Products

= 2.6.7 =
- Add: Add option to hide sale labels in product loops
- Add: Improved support for grouped products
- Add: Support product embeds

- Fix: Add some checks to delivery time output
- Fix: Improve split-tax calculation when digital products are involved
- Fix: Improve fee tax calculation when no split-tax is used
- Fix: Enforce order-comments to be UTF-8 encoded in PHP < 5.4

- Change: Fix German Translation of Email Footer Setting string
- Change: Display full country name during checkout
- Change: Tidy up WGM_Template code and deprecate some functions
- Change: Remove unnecessary calls to get_plugin_data()
- Change: Tidy up main class and separate admin/frontend-related code
- Change: Always show delivery times on product-single view
- Change: Get rid of cart-totals.php template
- Change: add span tag to sale labels
- Change: Do not show (possibly false) tax rates on variable product summary
- Change: Product summary/price markup is nowin line with WooCommerce default

= 2.6.6 =
- Fix: Prevent using front-end-specific WooCommerce functions from the back-end. This has led to a number of problems in combination with some 3rd party plugins
- Fix: Variation price was not displayed if all variations cost the same
- Fix: Global delivery time setting was not being used
- Fix: Changed JS behaviour when selecting variations so that general information is not hidden earlier than necessary
- Fix: Correctly show tax information during checkout for all tax_display_total/tax_display_cart combinations

= 2.6.5 =
- Fix: Formatting in plaintext and HTML emails
- Fix: Prevent a couple of potential notices
- Fix: Minor markup improvements for 'Small Trading Exemption'(KUR) output
- Fix: 'Small Trading Exemption'(KUR) notice now takes shop location into account
- Fix: Code cleanup and various minor enhancements
- Fix: Correct an issue with second checkout when using SSL
- Fix: Correctly display fee taxes with 'split tax' disabled
- Fix: Prevent potential errors when no 'measuring_unit' attributes are present on PPU settings
- Fix: Fix tax strings not respecting 'Tax based on' setting, fixing various oddities with tax strings
- Fix: Add missing default settings for 'Small Trading Exemption'(KUR) Feature
- Fix: Show thankyou-notice on Cash-on-delivery

- Change: Move 'Small Trading Exemption'(KUR) notice below order total
- Change: Move several product settings to general product options (and variations)
- Change: Fees now get updated when changing payment gateways during checkout
- Change: Add formal language pack support
- Change: Refunded items now show correct tax strings in refund emails
- Change: Update terms checkboxes to match WC2.5 layout

- Add: Introduce configurable 'Sale Labels' to product sale prices
- Add: Display individual tax/shipping info on product variations


= 2.6.4 =
- fixed escaped content attributes (WC 2.4.9 security fix)
- fixed if the option text-mail activated, the legal texts are now properly added
- fixed if the option text-mail activated, the order confirmation will be sent correctly
- fixed broken link revocation in checkout.
- fixed fatal error on reorder button

= 2.6.3 =
- fixed missing variations
- fixed missing KUR in totals for reviewed and mailed orders
- fixes unnecessary spaces removed
- fixes doubled description in second-checkout2 page

= 2.6.2 =
- fixed warning in confirmation mail
- fixed grammatical errors and translation errors corrected
- fixed disappearing attributes after installation
- fixed missing tax for the shipping costs
- fixed deprecated function args
- fixed incomplete declaration of $tax_total_string
- fixed updated text template terms (en+at)
- fixed added missing WooCommerce hooks in templates second-checkout2.php, email-order-items.php and plain/email-order-items.php

- added options for order confirmation mail
- added only check visable variations if they are digital

= 2.6.1 =
- updated UI message on second checkout page to be styled as an informational notice instead of success in order to prevent customers from misreading it and not finalizing their order
- fixed a bug where split tax calculation would not be applied to additional fees
- fixed a bug where a “1” would be displayed instead of the tax label
- fixed a bug where taxes would not appear when tax total display was set to “single”
- fixed a bug where taxes would be displayed as “0,00” at the order-received endpoint
- localized a previously hard-coded text string in a JavaScript file

= 2.6 =
- **Changed default language for text strings from German to English. [Learn more …](http://make.marketpress.com/woocommerce-german-market/269)**
- Ridded the world of improper use of the term “Imprint” outside print context. Props [@_andywashere](https://twitter.com/_andywashere/status/591146826335526913).
- Fixed rate binary output on first checkout page
- Shipping options product tab is now no longer available for virtual products
- Added new product type "digital"
- Added an order confirmation mail
- Reworked WGM sessions
- Split tax calculation is now done before coupons
- Added a filter for split tax deactivation
- Fixed structured data for product single view
- Fixed qoute usage in order comments
- WGM product tabs are using now woocommerce class to be only visable when applicable
- Rearranged element order for variable products
- Added an option for a default tax label
- Shipping cost links now open in new tabs
- fixed inconsistent options
- Added price per unit to product variation view
- Fixed shipping costs for digital products
- Fixed missing revocation policy text in mails when using paypal
- Changed default plugin language form german to english
- Added prerequisites for digital and downloadable variations
- Fixed shipping times for virtual products
- Normalized all tax strings
- Normalized WGM options
- Completely reworked translation

= 2.5.3 =
- Change Autoupdater to support inline plugin update

= 2.5.2 =
- Fixed doubled totals in cart for WooCommerce 2.3.8

= 2.5.1 =
- Fixed missing subtotals on second checkout page

= 2.5 =
- Added exclude tax notice to cart and checkout totals
- Added spacing for shipping method tax string
- Added compatibility with WooCommerce Print Invoice and other PDF Invoice plugins
- Added filter for product overview order
- Added tax calculation for digital products when using the cash on delivery gateway
- Added filters for link attributes in checkbox labels during second checkout
- Added filters for disclaimer labels during checkout
- Added coupons to second checkout
- Updated WooCommerce templates
- Updated translation
- Removed doubled requirements tab in product settings
- Removed internal item meta fields admin order panel
- Removed filters `wgm_checkout_revocation_checkbox_text` and `wgm_checkout_revocation_digital_checkbox_text` in favor of new filters `wgm_checkout_revocation_label_text` and `wgm_checkout_revocation_digital_label_text`

= 2.4.13 =
- Moved “Next” button on checkout page below terms rows
- Updated cart templates
- Fixed a bug where old orders coundn’t be displayed or accessed in the back-end
- Removed duplicated button and shipping calculator on the cart page

= 2.4.12 =

- Improved the MarketPress update feature to be able broadcast an update notice to the WordPress backend.
- Fixed button text during checkout when PayPal was selected as payment gateway

= 2.4.11 =

- Fixed a visual bug with duplicated items in cart
- Removed CSS class `second-checkout-button-container` in favor of `wc-proceed-to-checkout wgm-proceed-to-checkout`
- Removed no longer needed CSS declarations in frontend.css
- Removed no longer needed JS function call (left function in place for legacy)
- Updated button CSS classes to match default WooCommerce CSS classes
- Updated cart-totals.php template to make use of `wc_cart_totals_coupon_label()` and `wc_cart_totals_taxes_total_html()`
- Added body class `woocommerce` to be applied to second checkout page in order to match default WC button styles
- Added CSS classes `wgm-place-order`, `wgm-go-back-button` and `checkout-button` to second-checkout2.php template

= 2.4.10 =
- WGM now uses the Woocommerce Geolocate feature to determine the displayed tax rates if the option is enabled
- Added "Cash on delivery" for virtual products to the cash on delivery gateway
- Updated Autoupdater
- Changed from WC_Customer to WC_Order to retrive customer location in orders
- Removed selectable shipping methods in second checkout and only display chonsen one
- Added second checkout to woocommerce exclude from caching transient
- Removed shipping methods from checkout when the cart only contains digital products.
- Updated translation
- Fixed typos

= 2.4.9 =
- Fixed deprecated function calls from cart->tax to WC_Tax
- Fixed a typo in WGM_Settings
- Removed old and no longer needed review-order.php template

= 2.4.8 =
 - Fixed an issue with the Amazon Payments gateway
 - Fixed an issue where tax was worngly calculated when using a coupon
 - Fixed an issue where shipping costs for variable products in small business regulation shops would not be displayed
 - Fixed an issue where with excluding tax option active the price would not be displayed
 - Fixed an issue where taxes would be worngly calculated when a reduced digital products was in cart
 - Fixed an issue where no further text could be inserted before or after the woocommerce_de_check shortcode
 - Fixed a division by zero
 - Fixed typos
 - Changed button text on first checkout page when using paypal to "next" from "pay with paypal"
 - Added one decimal place to tax rate display
 - Updated translation
 - Added a wraping div to mails

= 2.4.7 =
- added new filters `wgm_get_split_tax_html` and `wgm_get_excl_incl_tax_string`
- improved support for coupons during split tax calculations for shipping costs and fees
- fixed a bug during in cart view where shipping costs would display net values before switching to gross values when checked
- fixed a bug during in cart view where taxes for shipping costs would be duplicated when added to total amount
- fixed a bug during order editing in the back-end where WGM’s calculations for split taxes on shipping costs and fees would not be applied

= 2.4.6 =
- added a missing argument to WGM_Template::checkout_readonly_field()
- added displaying of fee taxes for COD on thank-you page
- added legal notice for store owners to text templates
- improved formating of text templates, fixed corrupted HTML tag
- improved tax calculation for shipping fees and COD
- fixed a PHP notice at order-pay endpoint
- fixed a bug that resulted in zero values for taxes in invoice e-mails
- fixed obsolete displaying of fee taxes when small business option was enabled
- fixed a bug that resulted in falsified tax calculations for checkout totals
- fixed a bug that resulted in missing tax columns for orders in the back-end

= 2.4.5 =
- Fixed an fatal error when sending invoices from the backend
- Fixed wrong tax display for cash on delivery
- Fixed undefined offset notice for additional services tax calculation

= 2.4.4 =
- fixed wrong use of woo translation string
- fixed cod gateway and fee display
- show variation price in oderlisting
- added paypal return URL fix
- added new calculation for shipping and COD costs
- removed unnecessary expression
- replaced all , with . for wc_price in price per unit output
- updated translation

= 2.4.3 =
- Fixed notice in cart
- Fixed wording and punctuation
- Added Compability for Woo 2.2
- Removed strict warnings
- Updated updater
- Removed notice in mail
- Added target _blank to disclaimer line links

= 2.4.2 =
- Added filter for checkout checbox lables and texts
- Remmoved double shipping cost notice for variations with the same price
- Fixed digital notice in outgoing mails and paypal

= 2.4.1 =
- Added a Filter for HTML digital notice and the digital keyword itself respectively
- Added prerequists for virtual variable products
- Added missing CSS class prefix
- Replaced old "woocommerce_order_item_title" with new hook
- Changed COD Gateway to use the Woocommerce fees api
- Fixed worngly used hook "woocommerce_order_actions" to "woocommerce_order_actions_start"
- Fixed digital notice HTML output for all completed order tasks (e.g. Paypal payments etc)
- Fixed doubled displayed shipping costs notice and missing tax notice on variable products
- Fixed various typos
- Fixed some internal filters
- Fixed an issue where the updater would show an update notice for an old version of the plugin
- Fixed an Issue where the wrong cancelation policy notice would be shown at checkout
- Fixed an display issue with virtual product prerequists
- Fixed multiple display of shipping costs on product page
- Fixed estimate cart option
- Fixed an undefined variable error
- Fixed strict standard notices
- Fixed missing digital notice in cart widget
- Fixed various php notices
- Fixed diliverytime display with virutal products
- Fixed false prositive digital products in cart
- Fixed dislay for digital variation prerequists in checkout
- Fixed supress shipping product option
- Cleand up old code
- Replaced old deprecated functions with new ones
- Updated translation

= 2.4 =
- made software compliant with new german online sales rights (13.6.2014)
- added new text templates
- updated old text templates
- upadted translation
- removed some options
- updated mail
- added new product field for requirements for digital products
- tax and delivery notice is now mandatory
- new customer acknowledgement for digital products
- some cleanup

= 2.3.6 =
- info release, no fixes or features

= 2.3.5 =
- added a body class for second checkout
- fixed doubled ids in second checkout
- added css class for place order button in second checkout
- minor cleanups
- fixed wrongly displayed deliveryadress in second checkout
- translated licence key messgae
- minor html fixes

= 2.3.4 =
- fixed an serious javascript issue

= 2.3.3 =
- fixed various issues with WPML and WGM ajax calls

= 2.3.2 =
- Added support for WPML
- Added compability for WooCommerce 2.1.6
- added css class for variation price infos
- added various filter and actions for manipulating wgm's markup output
- added css class for loding animation
- fixed variation price display with identical prices
- fixed various typos
- fixed various translations
- fixed broken markup
- hide default price on variation single product page when variation price is loaded
- fixed static asset loading
- fixed free shipping label display to use user entered instead of default label
- fixed tax display with empty values in conjunction with small business regulation
- fixed doulbe tax loading with variation products
- fixed cash on delivery display on certain pages
- fixed missing row in cart
- fixed various issues with coupons
- fixed a typo in a setting id
- fixed various issues with checkout validation
- fixed wgm's default tax rates
- fixed missing shortcode in english installations
- updated documentation
- fixed wgm cash on delivery gateway
- added html markup to checkout readonly fields
- removed default woo tax notices
- added english translation for tax rate import

= 2.3.1 =
- fixed variation frontend javascript
- added plugin version function
- fixed body classes
- updated woocommerce templates
- fixed typo in cart template
- updated deprecrated parameter in checkout template
- fixed filter for WGM_Template::add_mwst_rate_to_product_item
- fixed typos ins translation
- fixed price per unit currency smybol usage
- fixed wrong option for price per unit
- changed additional vat string for better context
- fixed tax display for shipping with no costs
- various minor compatibility fixes

= 2.3 =
- updated hooks and functions for WooCommerce 2.1
- added filter for unexpected extra EU costs
- added filter for product short desc
- added filter for payment method in mails
- added filter for disclaimer
- added filter for small business regulation text
- added filter for small business regulation review text
- added filter for for after cart totals
- fixed typos
- implemented new template loadding system (templates can now be overwritten like woo templates)
- fixed check if woocommerce is active
- cleaned up css
- updated woocommerce templates
- fixed shipping calculation in second checkout
- fixed some installation errors
- added option for dual shipping methods
- fixed a warning in checkout with some particular vat options
- adjusted html output
- fixed an issue with taxes in product variations
- fixed some minor stability issues
- fixed some translation errors

= 2.2.6 =
- Added warning message

= 2.2.5 =
- Added Filter for small buissiness regulation texts
- Remove hardcoded 'approx' from delivery times
- Removed no deliverytime text field
- Added filter for default deliverytimes
- Extends Checkout SSL to all WGM Checkout sites
- Removed Deliverytimes from quickedit
- Changed deliverytimes from indecies to term id
- fixed price per unit display
- updated translation
- fixed some woocommrece incompabilities
- updated settings desctiptions
- some minor display fixes
- fixed some PHP5.4 warnings
- fixed some issues with wrong delivery times
- fixed an error which would not display price per unit attributs in dropdown menus
- fixed a missing currency symbol in mails
- updated translation
- fixed an superfluous whitespace in small bussiness regulations text filter
- removed wrong post count form deliverytime editor
- moved price per unit display under actual price
- added price per unit filter and before and after actions

= 2.2.4 =
- tax does not anymore depend on country when sending a mail manually through the backend

= 2.2.3 =
- fixed a bug where delivery times would not be set correctly after update
- product short desc on second checkout now hides properly
- fixed translation
- replaced a depricated jquery function .live with .on
- fixed deliverytimes quick edit
- fixed strict error message
- fixed wrongly stacked html
- tax rates are matching the country you selected on checkout
- removed debug output
- added css class for product option panales
- "approx" deos not display before "not available at the moment"
- fixed wrong tax calculation when using a coupon

= 2.2.1 =
- fixed wrong delivery times after upgrade
- fixed translation
- removed deliverytimes from quickedit

= 2.2 =
- Added small business regulation according to §19 UStG
- Fixed a bug where VAT didn't show up in cart
- Added compability for new woocommerce releases
- added dliverytime editor
- fixed some style issues
- fixed plugin activation notice
- removed style recommendation box on settings page
- CSS and JS only now only loading when needed
- fixed colspan in some tables
- fixed free shipping when free shipping amount is reached
- updated deliverytime wording
- updated leagal templates
- updated translation
- fixed wrongly rounded VAT displays
- fixed some issues with WPML
- fixed mails to not allways include legal stuff in the footer
- VAT can now be entered with . or ,
- fixed VAT percent displayed
- fixed typos
- fixed some minor display issues
- added support for a wider range of themes
- fixed a warning occurring when doing a search

= 2.1.6 =
- sperated price per unit html from data
- added some html elements for styling to second checkout
- updated updater

= 2.1.5 =
- templates clean up
- added function Woocommerce_German_Market::is_wgm_checkout() to check if the called site is the second checkout page
- fixed some minor issues
- moved inline css to css file
- fixed some css issues

= 2.1.4 =
- Fixed VAT Display in cart

= 2.1.3 =
- Added filter into cart template
- Fixed sorting order of delivery time terms

= 2.1.2 =
- changed array_map function for translated terms into static function

= 2.1.1 =
- Fixed fatal error in PHP 5.2

= 2.1 =
- Fixed a conflict with WPML
- Added product short descriptions to the product in checkout and order listing
- Fixed an issue where the VAT dind't display properly
- Fixed a conflict with Role Based Prices
- Added an option to display a hint text for extra costs when shipping in non eu contries
- Shippingcosts no longer displayed when the limit for free shipping is reached
- Removed activation.css from Frontend
- Added option for 'Free Shipping'
- Show terms in default selection
- Fixed various translation issues
- Added a Deliverytime Editor
- Fixed some typos
- Fixed various display issues
- Fixed various behaviour issues
- Fixed various javascript issues

= 2.0.2 =
- Fixed array to string conversion
- Added filter for colspan in cart
- Removed superfluous td tag
- added filter for additional tax strings
- changed prices tag method in additional tax display
- Fixed COD Payment gateway

= 2.0.1 =
- Fixed array to string conversion
- Fixed js payment gateway selection
- Fixed bodyclasses for new checkoutpages

= 2.0 =
- Added Support for Woocommerce 2.0+
- Refactoring, splitted codebase into seperate classes
- Removed no longer needed functions
- Added documentation to all methods
- Added various hooks for thrid party developers
- Added Class Autoloaded
- Changed Textdomain loading
- Updated Templates
- Improved Template loading
- Improved Cash on delivery Gateway
- Improved second Checkout page
- Fixed verious loading issues
- Replaced Emailtemplates with a hook
- Updated Translation
- Updated all woocommerce API calls
- Fixed various issues regarding the english version
- Fixed an issue where the terms page was not assined correctly
- Fixed an issue where the error notice when woocommerce is not installed didn't disapear after a reload
- Fixed an issue with the shipping costs
- Fixed an issue where the revoction page had no content
- Fixed an issue where the tax didn't got correctly assigned by the installtion routine
- Fixed an issue where taxes dindt got displayed properly
- Fixes various display issues
- Fixed an issue where paged coulnd't be saved in the en_US version
- Fixed an issue where the plugin didnt deactivated it self when the wrong version of woocommerce was instlled
- Fixed various corrupted or broken options
- Fixed price per unit handling and display
- Fixed various typos
- Fixed various minor things

= 1.1.4 =
- JS only listens on radio buttons on first checkout page
- All numbers are now formatted properly
- Exclusive tax in cart are now displayed properly
- Removed tax from items order table for cleaner view
- Custom delivery strings now obey the "delivery time in overview" option
- Fixed variation prices in mails

= 1.1.3 =
- Fixed PHP 5.4 Warnings
- Checkout Page dropdown now gets dispayed and saved into the database
- Fixed Shipping text position
- Added a new distinctive css class for the buy button
- Added a hint fild in the options which gets displayed on the last checkout page
- Some CSS fixes
- Some rearangements on the last checkout page
- Updated translation

= 1.1.2 =
- Added English localization
- Added missing english translations
- Conditions can now be turned off
- Updated depricated functions
- Add fresh styled Setup-Message
- Add english versions of page templates like imprint, terms, Method of payment ...
- Support english for sample page localizazion and Messages
- Updated Mailtemplates
- Fixed wrong Shippingcost calculations
- Notices displayed on the cart page can now be turned off
- Fixed Spelling mistakes
- Fixed some display errors on the checkout page

= 1.1.1 =
- Fixed several issues in autoupdater
- Fixed issue with facebook plugins
- separate wording for "Widerruf" checkbox
- delivery time: rename "day" to "business day"
- new template loading mechanism
- new default mail templates
- enhanced cart
- changed some translations
- fixed numberformarts in various areas
- some internal changes
- taxes now displayed properly on every listing
- various typo fixes

= 1.1 =
- placed next button in the right order
- fixed baseprice display
- fixed tax display
- fixed autoupdater

= 1.0.10 =
- include shipping in second checkout
- extended price per unit meassure
- new custom review order template with taxes
- license code dosn't show up in frontend anymore when defined in the config
- added filter for second checkout buy button text
- fixed mail bug
- fixed notice

= 1.0.9 =
- Terms page has now the correct content
- Cash on delivery is now properly displayed in mails
- delivery cost link is now longer splitted into 2 parts

= 1.0.8 =
- New option for displaying delivery costs seperatly in product overview and detailview
- Removed install notice if WGM was previously installed

= 1.0.7 =
- CSS Fixes
- TOS and Widerrufsbelehrung on first checkout are now links only
- Deliverytime and Shipping costs are now displayed properly
- Removed no longer needed files
- Refactored roduct display
- Base prices are now properly displayed in the product overview
- Autoupdater: use site_option instead of get_option
- Move shippingcosts and tax rate closer to pricetag

= 1.0.6 =
- Updated Auto Updater (pro-only)

= 1.0.5 =
- Moved Paymentmethod above the product list on the checkout page

= 1.0.4 =
- Fixed update process

= 1.0.3 =
- Fixed delivery adress on checkout page
- price incl tax on single product page is now unter the title
- Fixed bug which caused problems for the plugin update mechanism
- small changes on the checkout page for the "Button-Lösung"
- added changelog

= 1.0.2 =
- Changes for the "Button-Lösung"
- Short description under the product on the checkout page
- various CSS fixes
- spelling fixes

= 1.0.1 =
- compatibility changes for Woocommerce 1.6.1
- localization
- various CSS fixes

= 1.0  =
- Inital Release
