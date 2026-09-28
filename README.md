# FF Braak Fahrzeugbuchung

Web-App zur Reservierung der Fahrzeuge der **Freiwilligen Feuerwehr Braak** – z. B. MTW oder Anhänger der Jugendfeuerwehr. Mitglieder sehen in einem gemeinsamen Kalender, wann welches Fahrzeug belegt ist, und buchen freie Zeiträume selbst. Die Anmeldung läuft ohne Passwort über einen Magic Link per E-Mail.

## Funktionen

- **Belegungskalender** (FullCalendar) mit Monats-, Wochen-, Tages- und Listenansicht, farbig nach Fahrzeug
- **Buchungen anlegen, ändern und stornieren** mit Zweck, Ziel, Gruppe und Notizen
- **Schutz vor Doppelbuchungen** direkt in der Datenbank (PostgreSQL-Exclusion-Constraint). Ist ein Fahrzeug belegt, schlägt die App freie Alternativen vor.
- **Meine Buchungen**: Übersicht der eigenen anstehenden und vergangenen Buchungen
- **Passwortloser Login** per Magic Link. Der Link wird erst durch einen Bestätigungsklick eingelöst, damit E-Mail-Scanner ihn nicht vorzeitig verbrauchen.
- **Admin-Bereich** (Filament) unter `/admin` zur Verwaltung von Fahrzeugen, Benutzern, Gruppen und Buchungen
- **Rechte**: Mitglieder bearbeiten nur ihre eigenen, zukünftigen Buchungen. Admins dürfen alle Buchungen bearbeiten, auch vergangene.

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

Der `UserSeeder` legt einen Admin-Benutzer an. Passe die E-Mail-Adresse in [database/seeders/UserSeeder.php](database/seeders/UserSeeder.php) vor dem Seeden an, fordere unter `/auth/login` einen Magic Link an und öffne danach `/admin`.

## Konfiguration

Die wichtigsten Variablen aus [.env.example](.env.example):

| Variable                 | Beschreibung                                            |
|--------------------------|---------------------------------------------------------|
| `APP_URL`                | Öffentliche URL. Wird für die Links in den Magic-Link-Mails verwendet. |
| `DB_*`                   | Zugangsdaten für PostgreSQL                             |
| `MAIL_*`                 | SMTP-Server für den Versand der Login-Links             |
| `MAGIC_LINK_TTL_MINUTES` | Gültigkeit eines Login-Links in Minuten (Standard: 15)  |
| `SESSION_LIFETIME`       | Session-Dauer in Minuten (Standard: 90 Tage)            |
| `QUEUE_CONNECTION`       | `database`, es wird kein Redis benötigt                 |

Für den Mailversand muss ein Queue-Worker laufen (`php artisan queue:work`). Abgelaufene Login-Tokens räumt der Scheduler täglich auf (`php artisan schedule:work` bzw. ein Cronjob).

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
├── Filament/         Admin-Bereich (Resources & Widgets)
├── Http/             Magic-Link-Controller, Kalender-Feed, Middleware
├── Livewire/         Kalender, Buchungsformular, Meine Buchungen
├── Models/           Booking, Vehicle, Group, User, LoginToken
├── Policies/         Berechtigungen für Buchungen & Fahrzeuge
└── Services/         BookingService, MagicLinkService
database/
├── migrations/       Schema inkl. Exclusion-Constraint
└── seeders/          Fahrzeuge, Gruppen, erster Admin
```
