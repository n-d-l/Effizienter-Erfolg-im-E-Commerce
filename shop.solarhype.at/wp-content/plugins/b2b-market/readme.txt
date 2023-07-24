=== B2B Market ===
Contributors: MarketPress
Requires at least: 5.4+
Tested up to: 6+
Stable tag: 1.0.11.1

== Description ==

WooCommerce und B2B-Shops passen endlich zusammen, erstmals auch im deutschsprachigen Bereich. Verkaufe gleichzeitig an B2B und B2C. Mit individuellen Preisen für unterschiedliche Kunden, Prüfung der USt-ID, Staffelpreisen, erweiterten Rabatten und vielem mehr. Erweitere deine Umsätze auf Geschäftskunden, Endkunden und weitere Zielgruppen - mit B2B Market.

= Features =
<https://marketpress.de/shop/plugins/b2b-market/>

== Installation ==

= Requirements =
* WordPress 5.4+*
* PHP 7.4+*
* WooCommerce 5.1.0+*

= Installation =
 * Installieren Sie zuerst WooCommerce
 * Installieren Sie die Standardseiten für WooCommerce (Folgen Sie dazu der Installationsroutine von WooCommerce)
 * Benutzen Sie den installer im Backend, oder

1. Entpacken sie das zip-Archiv
2. Laden sie es in das `/wp-content/plugins/` Verzeichnis ihrer WordPress Installation auf ihrem Webserver
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

= 1.0 =
- Release

= 1.0.1 =
- Staffelpreise je Variante
- Neues Admin-Interface für Staffelpreise in Produkten
- Angepasste Live-Preis-Berechnung für Varianten
- Korrigierte Übersetzungen
- Behandlung von Umlauten und Sonderzeichen in Kundengruppen-Namen
- Steuer-Darstellung bei Netto-Preisen
- Komma-Preise in allen Feldern
- Konditionale Überprüfung für Warenkorb-Rabatte
- Produktauswahl bei langen Listen
- Fallback-Lösung bei riesigem Produktbestand in Select2
- Minifizierung Scripts und Styles
- REST API Bug in Zusammenhang mit Kalkulation

= 1.0.2 =
- Addon: Min-und Max-Mengen je Produkt und Kundengruppe
- Erweiterung und Bugfixes für Migrator (Varianten)
- Bugfixes Windows-Server und Migrator
- Umfassende Performance-Optimierung und neue Einstellung
- Kunden-und Gastgruppe nutz-und importierbar für Anwendung von Regeln für Gäste und normale Kunden
- Option zur Deaktivierung der Whitelist-Funktion für bestimmte Themes
- Shortcode zur konditionalen Ausgabe nach Kundengruppe (z.B. AGB)
- Neues Staffelpreis-Interface zur besseren Eingabe und eine effizientere Speicherung
- Umbenennung von Kundengruppen / Löschen von Kundengruppen / Anpassung von Kundengruppen verbessert
- Support für Staffel-und Gruppenpreise für Ajax- und Mini-Carts
- Bugfixes: Rundungsfehler in Live-Preis-Berechnung
- Bugfixes: Netto / Brutto-Preis-Berechnung
- Versandmethoden WC 3.5 Komatiblität
- Bugfixes: Kompatibilität mit Product Bundles
- Option: Blacklist / Whitelist für Administratoren deaktivieren
- Global und Kundengruppen-Preise auch für Varianten (ohne die Notwendigkeit von Produktwerten)
- Handling von Umlauten und Sonderzeichen in Kundengruppen-Namen verbessert
- Angepasste Sprachdateien für Core und Plugin Improver
- Validierung für Negativwerten in allen Nummerfeldern
- Helper-Funktion zur Migration von 1.0.1 Staffelpreisen zu 1.0.2

= 1.0.3 =
- Preisberechnung auf Basis des regulären / Angebots-Preis für Gruppen und Staffelpreise
- Neue Ajax-Live-Price-Lösung für weniger Theme-Inkompatibilitäten
- Filterbare Preisausgabe für B2B Market Preise (Live-Preis und Single-Preis)
- Zahlreiche Performance-Optimierungen
- Netto-Preis-Anzeige im Warenkorb / Kasse bei B2B-Gruppen
- UVP-Preis-Anzeige
- WooCommerce-API-Nutzung für Registrierung
- Registrierung im Checkout / Bearbeitung der Felder im Kundenkonto
- Handelsregister-Nummer als optionales Feld in der B2B-Registrierung
- FILTER: Produkt-Typen über Filter anpassbar
- FILTER: Regulären statt Angebotspreis für Kalkulation
- FILTER: Produkt-Gruppenpreis forcieren
- Automatisches Transient-Handling ohne Performance-Optionen (Transient-Option im Admin entfernt)
- Admin-Cache-Option ergänzt
- Deaktivierung / Aktivierung von Preisberechnung für Gast und Kunde
- Deaktivierung Gast-Suche für bessere Performance
- HOOKS: Alle Klassen sind nun über entsprechende Hooks anpassbar/filterbar
- Kompatibilität WP Support Plugins
- Kompatiblität Postman SMTP
- Kompatibilität WooCommerce Product Addons (Filter-Ergänzung der Plugin-Entwickler steht noch aus)
- REST API Fix für Billbee und weclapp
- Avada Shortcode Handling Support
- WPAllImport Support Verbesserungen
- Elementor Whitelist/Blacklist Kompatibilität
- Flatsome Live-Preis Kompatibilität
- Shopkeeper Live-Preis Kompatibilität
- Avada Live-Preis Kompatibilität
- Erendo Live-Preis Kompatibilität
- Raidboxes Admin Cache Fix
- Safari Source .map ergänzt

= 1.0.4 =

Feature:
- Kopierfunktion für Staffelpreise
- Konfigurierbare Preisanzeige
- WP All Import Addon
- Cookie Banner Addon
- Kundengruppe in Bestellübersicht anzeigen
- Reihenfolge, Label und Benennung von Kundengruppen in Registrierung anpassbar
- Preise verstecken je Kundengruppe
- Platzhalter statt Labels in Registrierung
- Konditionale Versandmethoden je Kundengruppe

Fix:
- UVP Anpassungen (Ausgabe/Darstellung)
- Slack Conntector Admin Page Fix
- Kompatibilität: WooCommerce Branding
- Registrierungsauswahl in Mein Konto
- Divi Builder Preisanzeige
- Kompatibilität Atelier Theme
- Kompatibilität Shopkeeper Theme
- Kompatibilität: WooCommerce Produkt Bundles
- Kompatibilität: PayPal Plus
- Schritte im Warenkorb (Min/Max-Addon)

Verbessert:
- JTL Kompatibilität
- VAT-Validator mit German Market
- Staffpreise ohne Bis-Wert möglich
- Automatische Grundpreisberechnung mit German Market
- Steuerstatus auch für Gäste
- Mehrere Kundengruppen in Shortcode
- Whitelist/Blacklist für Kategorien (Shop-Seite) und Widgets
- Kundengruppen in REST API
- Fehlerhandlung bei Kundengruppen Benennung
- VAT-ID von GM automatisch mit B2B VAT ID updaten
- Neue Filter für Registrierung
- Cache löschen Funktion statt automatischem Leeren
- UVP Preise löschen
- uninstall.php
- Beschreibungen und Hinweistexte für alle Funktionen

== 1.0.4.1 ==

Fix: 

- WP-Skin Notice Fix
- Admin-Script-Fix für Cookie Banner
- Min-Max-Addon Hooks in hooks.php für besseres Filtern
- Filter für Screen-Base bei Auslieferung der Admin-Styles
- Entfernung automatisches Update "Alle Produkte" in Gast und Kunde
- Bugfix: RBP-Migration ohne Staffelpreise
- Sale-Preis-Berücksichtigung in Alter Preis - Neuer Preis

== 1.0.5 ==

Feature:

- Kundengruppen-Auswahl als eingeloggter Administrator über Admin-Bar
- UVP-Label konfigurierbar
- Prozentuale Ersparnis im Warenkorb anzeigen
- Mindestbestellwert je Kundengruppe einstellbar

Fix:

- Whitelist bei Produktauswahl (Variable Produkte)
- Sale-Preis-Berücksichtigung in Alter Preis - Neuer Preis
- Erstbesteller-Rabatt Kalkulation mit Ajax
- PayPal-Express-Button bei Live-Preisen
- Blog2Social CSS-Bugfix
- Registrierung Notice-Fix wenn keine Gruppe aktiviert
- Auto-Updater Warning Bugfix
- WPAllImport Codemirror Script-Kollision Fix
- Kompatibilität Yith Offerts
- Mobile Navigation im Admin

Verbessert:

- Kompatibilität Variantenanzeige German Market
- Filter zur Anpassung des Ladens von Admin-Assets
- Migration ohne aktivierte Staffelpreise (RBP)
- Filter für Zusammenlegung Gast und Kunde
- Filter für Default-Aktivierung "Alle Produkte" in Gast/Kunde

== 1.0.6 ==

Feature:
- Admin-Bestellungen je Kundengruppe

Fix:
- Live-Preis-Kalkulation in Varianten
- Steuerdarstellung in Quick Views
- Performance-Optimierung bei get_cheapest_price() mit Cache
- Kompatibilität Giftware und WooCommerce Free Gift Coupons
- Kostenlose Produkte in Min-Amount-Prüfung
- Elementr Pro Custom Template Live-Preis
- Registrierung PHP-Notice bei Ermittlung der aktuellen Kundengruppe
- Grundpreis bei Netto-Preis-Eingabe der Preise
- Prozentuale Ersparnis Kalkulation (Rundungsabweichungen)
- JTL Preisanzeige Brutto/Netto


Verbessert:
- Filter zum Ausschließen von Produkten in Kundengruppen und globalen Rabatten
- Autoupdater
- Cookie-Banner - Impressum-Anzeige als Link
- Besseres Steuerhandling
- Blacklist auch in Warenkorb und Kasse (Überprüfung)
- Aktuelle Version in B2B Market Admin
- Cache löschen Button nun überall im B2B-Admin
- Product Bundles Intregration (mehr Optionen unterstützt)

== 1.0.6.1 ==
- Live-Preis Varianten mit Staffelpreisen

== 1.0.6.2 ==

Feature:
- UST-ID in Rechnungen, Kundenkonto und E-Mails integriert

Fix:
- Grundpreisberechnung mit German Market optimiert
- Steuerdarstellung auf Frontend begrenzt
- [b2b-group-display] - Bugfix für einzelne Kundengruppe
- Double-Optin-Registrierung optimiert + Bugfixes (Behebung PHP-Notices)
- Grundpreis im Warenkorb für Staffelpreise optimiert

Verbessert:
- get_cheapest_price() mit Kundengruppen-ID erweitert
- Kompatibilität mit Yith Giftcards Pro
- Live-Preis mit Divi-Theme
- Avada Whitelist-Kompatibilität

== 1.0.6.2.1 ==

Fix:
- JavaScript Anpassung für WordPress 5.5

== 1.0.7 ==

Feature:

- Neue Double-Optin-Lösung
- Kundengruppe (Admin-Bestellung) basierend auf Kunde nicht Kundengruppen-Switcher
- PHP 8 Support
- Neuer Shortcode [b2b-customer-group] zur Ausgabe der aktuellen Kundengruppe

Fix:

- Streichpreis-Anzeige wenn vergeben
- Erstbesteller-Coupon bei Kategorien
- Warnung bei Lizenz-Aktivierung
- jQuery für neueste Version kompatibel gemacht
- Notice-Fix: get_regular_price prüfen vor Berechnung
- Grundpreis Varianten (Gewicht und automatisch)
- Auto-Updater Bugfix für Lizenz-Check
- pre_option in Ajax für Preisdarstellung in Modal
- Kundengruppen editieren URL-Anpassung
- Slack Connector deprecated Funktion ersetzt
- Plugin-Improver Anpassungen
- Rundungsfehler Präventation
- Darstellungsfehler bei Feldern in Kundengruppe (Admin) behoben
- Flatsome Kompatibilität Bugfix für Whitelist
- Mengenwähler bei "Nur einzeln verkaufen" gefixt
- Bugfixes für Blacklist/Whitelist in Widgets, Blöcken und Shop-Standardseiten
- Absoluter Nachlass bei Rabatt-Anzeige im Warenkorb
- Hinweis für Cookie Consent Addon
- Anpassungen für gruppierte Produkte (keine Gruppen/Staffelpreise, keine Live-Preis-Anpassung)

Verbessert:

- Shop-Manager Zugriff auf Kundengruppen
- Optionaler Parameter Kundengruppen-ID in Funktionen
- UST-ID Eintragung in Kombination mit German Market
- Mobil-Optimierung des Backends
- B2B VAT ID als Metafeld in Bestellungen
- Default-Nachricht bei Mindestbestellwert
- [bulk-price-table] mit Grundpreisen

== 1.0.8 ==

Feature:

- Gruppen- und Staffelpreise auf Kategorien eingrenzen
- Erweiterung Staffelpreistabelle für variable Produkte
- Optionen für Staffelpreise und UVP
- Staffelpreisnotiz auf Shop-, Produktseiten ausgeben
- Steueranzeige in Rechnungs-PDF je nach Steuereinstellung

Verbessert:

- Überarbeitung Preisanpassung
- Varianten-Preiseingabe direkt innerhalb der Variante
- Überarbeitung Kundengruppen-Tabelle
- Änderung Bestellungen im Backend
- Warenkorb-Rabatt-Meldung je Steuereinstellung ausgeben
- Staffelpreise ab Menge 1 festlegen können
- Nur kostenloser Versand, wenn verfügbar
- UST-ID Eintragung in Kombination mit German Market
- Steueranzeige mit German Market EU-Mehrwertsteuer-Add-On
- Kompatibilität mit TM Extra Product Options
- Kompatibilität mit Product Bundles
- Codeoptimierungen


Fix:

- Fehler, wenn kostenloses Produkt und Mengenrabatt je Kategorie gewählt
- Shortcode [b2b-group-display] bei "Keine Kundengruppe"
- Regulärer Preis und Angebotspreis sind "vertauscht", wenn Angebotszeitraum entfernt wird
- Keine Auswahl von Produkten direkt an Kundengruppe
- Kleine Bugfixes
- Das Cookie-Addon wurde entfernt


== 1.0.8.1 ==

Fix:

- Verbesserung der Performance für Migrator
- Berücksichtigung von Staffelpreisen in Migration
- Anwendung von Preisen bei Themes ohne Minicart
- Fallback-Lösung für Migrator bei Produkten ohne Objekt

== 1.0.8.3 ==

Verbessert:

- Verbesserung des Im-und Exporters für Einstellungen und Preise
- Performance für variable Produkte (Preisanzeige) (Nicht-Admin-User)
- Kompatibilitätsklasse hinzugefügt

Fix:

- Admin-Bestellungen mit neuem WooCommerce Hook angepasst
- WpBakery Support für Preisanzeige in Widget hergestellt

== 1.0.8.3 ==

Verbessert:

- Kompatibilität Divi-Theme
- Kompatibilität Avada-Theme
- Kompatibilität Impreza-Theme
- Kompatibilität Elementor Pro
- Min-und Maxmengen Kompatibilität für Warenkorb-Button im Loop

Fix:

- Grundpreisberechnung nach Gewicht bei Varianten
- Variantenpreise für Backendänderungen
- Gutscheine bei Backend-Änderungen werden nach Aktualisierung entfernt
- UST ID mit German Market

== 1.0.9 ==

Feature:

- Sale-Badge Steuerung für B2B Preise je Kundengruppe
- Erweiterung und Optimierung des Ex-und Imports
- Verbesserte Preisaktualisierung mit JavaScript
- Blacklist/Whitelist für WooCommerce Blöcke
- Slack-Connector - Benachrichtigung bei Admin-Bestellungen
- Dynamische Gesamtpreisanzeige (+ Option)
- Verbesserte Staffelpreistabelle (klickbare Mengenauswahl + konfigurierbare Darstellung)

Verbessert:

- Grundpreisberechnung mit Germanized
- Optimierung Gutscheinanwendung im Backend bei B2B Bestellungen
- Kostenloser Versand über Filter steuerbar
- Kompatibilität Thrive Theme Builder
- Verbesserung WooCommerce Product Bundles Kompatibilität
- WPC Product Bundles Kompatibilität
- Kompatibilität Envo Theme
- Verbesserung Avada Kompatibilität
- Verbesserung Flatsome Kompatibilität
- Kompatibilität WooCommerce Product table
- Performance-Optimierung Staffelpreiseingabe bei Variablen Produkten
- Kompatibilität MwSt-EU-AddOn von German Market

Fix:

- Staffelpreise in Quick View (Atomion)
- Sale-Preis-Badge bei variablen Produkten
- Min/Max-Mengen Eingabe bei variablen Produkten (Metabox)
- Double-Opt-In E-Mail bei Nutzung von German Market
- UST-ID-Eingabe bei Kundengruppe "Kunde"
- Min/Max-Mengen bei Rollenwechsel

== 1.0.9.1 ==

- Zurückrollen auf PHP 7.4 Kompatibilität

== 1.0.9.2 ==

Fix:

- Streichpreis für variable Produkte (und keine Kundengruppe)
- Sale-Badge für variable Produkte
- Update-Price bei variablen Produkten in WooCommerce Standardverhalten

== 1.0.10 ==

Verbessert

- Feature Gesamtsumme bei gruppierten Produkten nicht anzeigen
- Preisanzeige bei gruppierten Produkten
- Performance optimiert
- Filter für 404-Weiterleitung Black-/Whitelist eingebaut
- Staffelpreistabelle verbessert
- Slack-Connector - Firmenfeld übertragen
- Preis-Updater modifiziert für Preise auf Kategorie-Seite
- Rundung bei Mindestbestellwert im Warenkorb und Kasse
- Kompatibilität mit Black-, Whitelist und WPBakery
- Kompatibilität mit Divi und Bereich "Ähnliche Produkte"
- Kompatibilität Preis-Updater für Elementor und Elementor Pro
- Kompatibilität mit UX-Builder verbessert
- Kompatibilität mit Open Shop
- Kompatibilität mit Variation Swatches
- Kompatibilität Javascript Preis-Updater mit Flatsome bei variablen Produkten
- Kompatibilität Preis-Updater mit "Themify Shoppe-Child" Theme
- Kompatibilität mit Product Configurator for WooCommerce
- Kompatibilität Preis-Updater in Verbindung mit XStore

Fix

- Minimale Bestellsumme bei Bruttokundengruppe
- Grundpreisberechnung nach Gewichten bei Staffelpreisen
- Grundpreisberechnung nach Gewicht bei Varianten
- Preise-Ausblenden-Funktion und Quickview (Atomion)
- Grundpreis-Darstellung / Berechnung (Thankyou-Page + Emails) angepasst
- Sale-Badge/Streichpreisoption für B2B Rabatte auch bei Gästen
- Cross-Sell-Preis-Anzeige korrigiert
- Kein Preisupdate Staffelpreise, wenn Varianten gleiche Preise haben  
- Preisanzeige in Schnellansicht bei Atomion
- Funktion des Kundengruppenswitchers wenn Kundengruppe eine Zahl ist
- WooCommerce Structured Data und "Hide Price" Funktion
- Mengenrabatt für alle ausgewählten Kategorien
- Fehler in Varianten behoben: Anlage mehrerer Felder für B2B Preise bei Nutzung des ATUM-Plugins
- Bei Anpassung der Preise über QuickEdit wird Angebotspreis nicht mehr auf 0 gesetzt
- Slack-Connector - Keine Artikel in Benachrichtigung bei Frontend-Bestellungen behoben
- Kleine Bugfixes


== 1.0.11 ==

Feature

- Warenkorb-Rabatt (nach Wert)


Verbessert

- Kompatibilität mit TM Extra Product Options
- Kompatibilität mit Advanced Coupons for WooCommerce
- Kompatabilität mit Woocommerce Custom Product Addons
- Kompatibilität mit WooCommerce Product-Add-Ons
- Kompatibilität mit Divi Library
- Kompatibilität mit Shortcodes und Elementor
- Kompatibilität mit Versand von B2B Market und Plugin Side Cart Premium
- Kompatibilität mit Uncode und Preisupdater
- Kompatibilität mit JupiterX mit Minicart Widget
- Kompatibilität mit Avia Theme Builder
- Kompatibilität mit Free Gifts for WooCommerce
- Kompatibilität mit WooCommerce Tax Toggle
- Grundpreisberechnung in Verbindung mit German Market
- Double Opt-In: Bei WooCommerce Versionen >= 6.0 und aktivierter WooCommerce-Einstellung "Sende dem neuen Benutzer bei der Erstellung eines Kontos einen Link, ueber den er sein Passwort festlegen kann.", wird in der "Double Opt-In"-Aktivierungs-E-Mail nicht das Passwort angezeigt, aber in der "Neues Konto"-E-Mail nach der Aktivierung der Link zur Passwort-Generierung ausgewiesen.


Fix

- Streichpreise in Verbindung mit 'Sale-Badge' Option sowie UVP bei Varianten
- Preisanzeige-Suffix bei einfachen Produkten
- Ausgabe der Preisspanne nach Zurücksetzen der Variante
- Berücksichtigung des Angebotszeitraumes bei Angebotspreis und Sale-Badge
- UVP bei variablen Produkten in Verbindung mit Germanized
- Kleine Bugfixes

== 1.0.11.1 ==

Verbessert

- Kompatibilität mit Salesman Modul "Fortschrittsanzeige für kostenfreie Lieferung"

Fix

- Kleine Bugfixes

