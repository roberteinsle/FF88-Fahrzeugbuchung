# FF Braak Fahrzeugbuchung

Web-App zur Reservierung der Fahrzeuge der **Freiwilligen Feuerwehr Braak** – z. B. MTW oder Anhänger der Jugendfeuerwehr. Mitglieder sehen in einem gemeinsamen Kalender, wann welches Fahrzeug belegt ist, und buchen freie Zeiträume selbst. Die Anmeldung läuft ohne Passwort über einen Magic Link per E-Mail.

## Funktionen

### Rollen

| Rolle          | Darf                                                                                           |
|----------------|------------------------------------------------------------------------------------------------|
| Mitglied       | Kalender ansehen, eigene Buchungen anlegen, ändern und stornieren, Konflikte zur Entscheidung vorlegen, Feedback senden |
| Entscheider    | zusätzlich Buchungskonflikte entscheiden, Entscheidungen ändern und zurücknehmen                |
| Administrator  | alles, dazu Admin-Bereich, Buchungen für andere anlegen, Besitzer tauschen, vergangene Buchungen bearbeiten |

Entscheider und Administratoren werden im Admin-Bereich pro Benutzer per Häkchen festgelegt. Gibt es keinen aktiven Entscheider, übernehmen die Administratoren diese Rolle.

### Anmeldung

- **Passwortloser Login per Magic Link:** E-Mail-Adresse eingeben, Link aus der Mail öffnen und auf „Anmelden“ klicken. Erst dieser Klick löst den Link ein, damit E-Mail-Scanner ihn nicht vorzeitig verbrauchen.
- Links gelten 15 Minuten und nur einmal. Anfragen sind pro Adresse und IP begrenzt. Für unbekannte Adressen erscheint dieselbe Meldung, damit sich nicht ausprobieren lässt, wer registriert ist.
- Die Sitzung bleibt 90 Tage bestehen.
- **Admin-Konten ohne Terminal:** Die Adressen aus `ADMIN_EMAILS` werden bei jedem Container-Start als aktive Admins angelegt.

### Kalender

- **Ansichten:** Woche (Standard), Monat und Liste, farbig nach Fahrzeug. Die bevorzugte Standardansicht stellt jeder unter „Profil → Einstellungen“ ein.
- **Filter:** Über die Fahrzeug-Chips oben lassen sich Fahrzeuge ein- und ausblenden.
- **Liste:** Fahrzeug, Zweck und Name stehen mit Icons nebeneinander. Bereits beendete Buchungen sind grau.
- **Woche:** Fahrzeug, Zweck und Name stehen untereinander, auf dem Handy nur das Fahrzeug. Die Ansicht beginnt um 6 Uhr.
- **Monat:** Auf dem Handy erscheinen die Buchungen als farbige Punkte. Ein Tipp auf den Tag öffnet die Tagesliste.
- **Offene Konfliktanfragen** sind gestreift und mit „Angefragt“ markiert.
- **Zeitzone:** Alle Zeiten werden in Europe/Berlin angezeigt, inklusive Sommer- und Winterzeit.

### Buchen

1. Über den Plus-Button oder einen Klick auf einen Tag öffnet sich das Buchungsformular mit Fahrzeug, Von/Bis, Zweck, Ziel, Gruppe und Notizen.
2. Beim Ändern von Fahrzeug oder Zeitraum prüft die App sofort die Verfügbarkeit. Ist das Fahrzeug belegt, zeigt sie die kollidierende Buchung und freie Alternativfahrzeuge.
3. Doppelbuchungen verhindert zusätzlich die Datenbank (PostgreSQL-Exclusion-Constraint). Buchungen, die direkt aneinander anschließen (bis 14:00 / ab 14:00), sind erlaubt.
4. **Admins** wählen im Feld „Gebucht für“ per Suche (Name oder E-Mail) eine andere Person als Besitzer. Beim Bearbeiten lässt sich der Besitzer so auch tauschen.
5. **Meine Buchungen** zeigt die eigenen Buchungen, aufgeteilt in „Kommend“, „Vergangen“ und „Storniert“, jeweils mit Bearbeiten und Stornieren.

Mitglieder bearbeiten und stornieren nur ihre eigenen Buchungen, und nur solange sie noch nicht begonnen haben. Vergangene Buchungen ändern oder löschen nur Admins.

### Konflikte und Entscheidungen

Ist ein Fahrzeug bereits gebucht, kann man trotzdem eine **Entscheidung anfragen**.

1. **Anfrage:** Das geht bei neuen Buchungen und beim Verschieben bestehender Buchungen, jeweils mit Pflicht-Begründung. Die Anfrage wird als „wartet auf Entscheidung“ gespeichert und blockiert das Fahrzeug nicht. Bei einer Änderung bleibt die bisherige Buchung bis zur Entscheidung unverändert.
2. **Benachrichtigung:** Alle Entscheider bekommen eine Mail mit direktem Link zur Entscheidung. Solange etwas offen ist, zeigt die Navigation den roten Button **„Entscheidung (n)“**, auf dem Handy als rote Leiste.
3. **Entscheiden:** Die Entscheidungsseite zeigt Anfrage, Begründung, die bestehende Buchung und gegebenenfalls die bisherige Buchung.
   - **„Anfrage genehmigen“** gibt der Anfrage das Fahrzeug und storniert die kollidierenden Buchungen. Bei einer Änderung wird die bisherige Buchung ersetzt.
   - **„Bestehende Buchung behalten“** lehnt die Anfrage ab.
   - Eine Anmerkung ist optional. Entscheiden zwei gleichzeitig, zählt nur die erste Entscheidung.
4. **Information:** Anfragende Person und Besitzer der bestehenden Buchungen bekommen das Ergebnis per Mail. Die übrigen Entscheider erfahren, dass bereits entschieden wurde.
5. **Ändern oder zurücknehmen:** Eine getroffene Entscheidung kann umgedreht oder zurückgenommen werden. Beim Zurücknehmen wartet die Anfrage wieder, und stornierte Buchungen gelten wieder. Ist der Zeitraum inzwischen anderweitig belegt, lehnt die App das mit einer Meldung ab. Alle Beteiligten werden per Mail informiert.
6. **Automatische Reaktivierung:** Wird die Buchung, die Vorrang bekam, storniert, gelöscht oder so verschoben, dass sie nicht mehr kollidiert, wird die unterlegene Buchung automatisch wieder aktiv. Das gilt in beide Richtungen, sofern die unterlegene Buchung noch in der Zukunft liegt und ihr Zeitraum frei ist. Alle Beteiligten bekommen eine Mail.
7. **Zurückziehen:** Wer die eigene offene Anfrage storniert, zieht sie zurück. Die Entscheider werden informiert.
8. **Dokumentation:** Jede Entscheidung hat einen Verlauf mit Anfrage, Entscheidung, Änderungen und automatischen Reaktivierungen, jeweils mit Person, Zeitpunkt und Anmerkung. Unter „Entscheidungen“ stehen offene und erledigte Fälle.

### Feedback

- **Mitglieder** senden unter „Profil → Feedback an die Admins“ Nachrichten mit Betreff. Die Gespräche lassen sich im Chat-Stil nachlesen und beantworten. Neue Admin-Antworten erscheinen als roter Punkt am Menüpunkt „Profil“.
- **Admins** finden alle Gespräche im Admin-Bereich unter „Feedback“, mit Zähler für ungelesene. Dort können sie antworten und Gespräche als erledigt markieren oder wieder öffnen.
- Jede Nachricht wird per Mail an die Gegenseite geschickt, mit direktem Link zum Gespräch.

### Admin-Bereich (`/admin`)

- **Fahrzeuge:** Name, Kurzname, Farbe, Reihenfolge und aktiv/inaktiv.
- **Gruppen:** zum Beispiel Jugendfeuerwehr oder Musikzug. Mitglieder ordnen Buchungen einer Gruppe zu.
- **Nutzer:** Häkchen für Administrator, Entscheider und aktiv. Filter nach Gruppe, Rolle und Status. Außerdem lässt sich ein Login-Link direkt versenden.
- **Buchungen:** alle Buchungen mit Status (bestätigt, wartet auf Entscheidung, abgelehnt), Stornieren und Bearbeiten. Stornieren, Ändern und Löschen lösen dieselben Automatismen aus wie in der App.
- **Feedback:** siehe oben.
- **Dashboard:** Link zum Wachen-Monitor, die Top 5 der Mitglieder mit den meisten Buchungen, die Fahrzeuge sortiert nach Anzahl Buchungen und die anstehenden Buchungen. Über „Zur App“ geht es zurück zum Kalender.

### Wachen-Monitor

Nur-Lese-Ansicht für den Bildschirm in der Wache im Stil von Divera. Sie aktualisiert sich jede Minute von selbst und lädt alle 6 Stunden komplett neu.

- **Oben:** Uhr, Datum und Zähler für freie, bald belegte und belegte Fahrzeuge.
- **Fahrzeugkacheln:** Grün heißt frei (mit nächster Buchung). Gelb heißt ab einer Uhrzeit innerhalb der nächsten 2 Stunden gebucht. Rot heißt unterwegs bis …, jeweils mit Zweck und Name.
- **Liste:** die nächsten Buchungen der kommenden 7 Tage, offene Anfragen mit „Angefragt“ markiert.
- **Datenschutz:** Namen werden verkürzt angezeigt („Robert E.“). Es gibt keinerlei Bearbeitungsfunktionen, und die Seite ist keinem Benutzer zugeordnet.
- **Zugriff:** Der Wachen-PC öffnet `/monitor/<DISPLAY_TOKEN>` ohne Login. Ohne oder mit falschem Schlüssel antwortet die Seite mit 404. Admins öffnen `/monitor` direkt. Den fertigen Link zeigt das Admin-Dashboard. Wird `DISPLAY_TOKEN` in Coolify geändert, ist der alte Link ungültig.

### E-Mails

Alle Mails sind auf Deutsch und werden sofort verschickt, ein Queue-Worker ist nicht nötig. Kann eine Mail nicht zugestellt werden, landet der Fehler im Log. Die eigentliche Aktion, etwa eine Entscheidung, bleibt trotzdem gespeichert.

| Anlass                           | Empfänger                                               |
|----------------------------------|---------------------------------------------------------|
| Login-Link                       | die anfragende Person                                    |
| Entscheidung nötig               | alle Entscheider (ersatzweise Admins)                    |
| Entscheidung getroffen / geändert | anfragende Person und Besitzer der bestehenden Buchungen |
| Bereits entschieden              | die übrigen Entscheider                                  |
| Entscheidung zurückgenommen      | alle Beteiligten                                         |
| Buchung automatisch reaktiviert  | alle Beteiligten                                         |
| Anfrage zurückgezogen            | die Entscheider                                          |
| Neues Feedback / Antwort vom Mitglied | alle Admins                                         |
| Antwort vom Admin                | das Mitglied                                             |

## Tech-Stack

| Bereich    | Technologie                                          |
|------------|------------------------------------------------------|
| Backend    | PHP 8.4, Laravel 12, Livewire 3                      |
| Admin      | Filament 3                                           |
| Frontend   | Tailwind CSS 4, Alpine.js, FullCalendar 6, Vite      |
| Datenbank  | PostgreSQL (mit `btree_gist`-Extension)              |
| Tests      | Pest 3                                               |
| Deployment | Docker (serversideup/php), GitHub Actions, Coolify   |

## Lokale Entwicklung

### Mit Docker Compose (empfohlen)

```bash
docker compose up --build
```

Die App läuft danach unter <http://localhost:8080>. Migrationen werden beim Start automatisch ausgeführt (`AUTORUN_ENABLED=true`). E-Mails werden lokal nicht versendet, sondern ins Log geschrieben (`MAIL_MAILER=log`). Den Magic Link findest du also in `storage/logs/laravel.log`.

Stammdaten (Fahrzeuge, Gruppen, erster Admin) anlegen:

```bash
docker compose exec app php artisan db:seed
```

### Ohne Docker

Voraussetzungen: PHP 8.4 (mit `pdo_pgsql` und `intl`), Composer, Node.js und eine PostgreSQL-Datenbank.

```bash
cp .env.example .env        # anschließend DB_* und MAIL_* anpassen
composer setup              # install, key:generate, migrate, npm build
php artisan db:seed
composer dev                # Server, Queue-Worker, Logs und Vite parallel
```

> **Hinweis:** Die App setzt PostgreSQL voraus. Die Überschneidungsprüfung nutzt `tstzrange` und einen GiST-Exclusion-Constraint, deshalb funktionieren SQLite und MySQL nicht.

### Erster Login

Lokal legt der `UserSeeder` einen Admin an ([database/seeders/UserSeeder.php](database/seeders/UserSeeder.php)). Im Docker-Container werden stattdessen die Adressen aus `ADMIN_EMAILS` bei jedem Start als Admin angelegt. Fordere unter `/auth/login` einen Magic Link an und öffne danach `/admin`.

## Konfiguration

Die wichtigsten Variablen aus [.env.example](.env.example):

| Variable                 | Beschreibung                                            |
|--------------------------|---------------------------------------------------------|
| `APP_URL`                | Öffentliche URL. Wird für die Links in den Magic-Link-Mails verwendet. |
| `DB_*`                   | Zugangsdaten für PostgreSQL                             |
| `MAIL_*`                 | SMTP-Server für alle Mails. Für Port 465 gilt `MAIL_SCHEME=smtps`. |
| `ADMIN_EMAILS`           | Kommagetrennte Adressen, die beim Container-Start als Admin angelegt bzw. aktiviert werden |
| `DISPLAY_TOKEN`          | Geheimer Schlüssel für den Wachen-Monitor ohne Login (`/monitor/<Schlüssel>`). Leer bedeutet nur für Admins. |
| `MAGIC_LINK_TTL_MINUTES` | Gültigkeit eines Login-Links in Minuten (Standard: 15)  |
| `SESSION_LIFETIME`       | Session-Dauer in Minuten (Standard: 90 Tage)            |
| `LOG_CHANNEL`            | Im Docker-Image `stderr`, damit Fehler in den Container-Logs erscheinen |

Alle Mails werden sofort verschickt, ein Queue-Worker ist nicht nötig. Abgelaufene Login-Tokens räumt der Scheduler täglich auf (`php artisan schedule:work` bzw. ein Cronjob).

## Tests

```bash
composer test
# oder
./vendor/bin/pest
```

Die Tests brauchen eine PostgreSQL-Testdatenbank (siehe [phpunit.xml](phpunit.xml)).

## Deployment

Bei jedem Push auf `main` passiert über [GitHub Actions](.github/workflows) Folgendes:

1. Tests laufen gegen PostgreSQL 18.
2. Ein Docker-Image wird gebaut und nach `ghcr.io/roberteinsle/ff88-fahrzeugbuchung` gepusht (Tags `latest` und `sha-<commit>`).
3. Optional wird ein Coolify-Deployment per Webhook ausgelöst. Dafür die Repository-Variable `COOLIFY_DEPLOY_ENABLED=true` sowie die Secrets `COOLIFY_DEPLOY_WEBHOOK` und `COOLIFY_WEBHOOK_TOKEN` setzen.

Die App läuft hinter einem Reverse Proxy. Alle Proxy-Header werden vertraut, damit korrekte HTTPS-URLs erzeugt werden.

## Projektstruktur

```
app/
├── Console/          app:ensure-admins (Admins aus ADMIN_EMAILS)
├── Filament/         Admin-Bereich (Fahrzeuge, Gruppen, Nutzer, Buchungen, Feedback)
├── Http/             Magic-Link-Controller, Kalender-Feed, Middleware
├── Livewire/         Kalender, Buchungsformular, Meine Buchungen, Entscheidungen, Feedback, Einstellungen
├── Models/           Booking, BookingDecision, Vehicle, Group, User, FeedbackThread, FeedbackMessage, LoginToken
├── Notifications/    alle E-Mails
├── Policies/         Berechtigungen für Buchungen & Fahrzeuge
└── Services/         BookingService, DecisionService, FeedbackService, MagicLinkService
database/
├── migrations/       Schema inkl. Exclusion-Constraint, Grunddaten (Fahrzeuge, Gruppen)
└── seeders/          Fahrzeuge, Gruppen, erster Admin (lokal)
docker/entrypoint.d/  Startskript, das beim Container-Start die Admins anlegt
```
