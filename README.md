# Support Ticket & SLA Management System

Laravel 13 / PHP 8.3 / MySQL support-ticket application with Breeze Blade/Tailwind, Pest,
Spatie permissions and activity logging, queued CSV imports, and business-hours SLA tracking.

## Requirements

| Requirement | Implementation |
|---|---|
| Authentication | Breeze routes and `User` |
| Roles and permissions | `RolePermissionSeeder`, Spatie permission |
| Ticket CRUD | `TicketController`, `TicketService` |
| Ticket numbers | `TicketNumberGenerator` and `ticket_sequences` |
| Assignment | `TicketAssignmentService` and assignment job |
| Replies and notes | `TicketReplyService` |
| Attachments | `UploadedFileStorage`, `AttachmentController` |
| Categories | `CategoryController` |
| SLA calculation | `Sla\SlaCalculator` |
| SLA configuration | `Sla\SlaConfigLoader` and admin controllers |
| Business hours | `BusinessHoursSeeder` and calendar configuration |
| Escalation | `SlaEscalationService`, `tickets:check-sla` |
| Notifications | Events, queued listeners, database notifications |
| CSV import | `ImportController`, `TicketImportService`, `imports:generate-sample` |
| Audit history | `TicketActivityLogger` and `AuditController` |
| Authorization | `TicketPolicy`, `ImportBatchPolicy`, permission middleware |
| Testing | Pest feature/unit suite and Pint |

Extras include the admin configuration area, Docker review setup, CI, and CSS-variable theming.

## Render and TiDB Cloud

The production image is a single Render web service running Nginx, PHP-FPM, the queue worker,
and the scheduler under Supervisor. Create the service from `render.yaml`, set the secret
values marked `sync: false`, and deploy. Render's edge terminates TLS; `FORCE_HTTPS=true`
preserves HTTPS URL generation. The image listens on Render's `PORT` (default 10000).

Configure TiDB Cloud with its MySQL-compatible host, port 4000, `<prefix>.root` username,
password, and `/etc/ssl/cert.pem` as `MYSQL_ATTR_SSL_CA`. TiDB's incompatible full-text
feature is disabled with `TICKETS_FULLTEXT_SEARCH=false`; keyword search then uses escaped
`LIKE`. The existing migration also refuses to create the MySQL full-text index when the
server identifies itself as TiDB.

Render's filesystem is ephemeral, so uploaded attachments and import files are lost on
restart. `TICKETS_ATTACHMENTS_DISK` and `IMPORTS_DISK` are environment-driven; an
S3-compatible disk can be configured after installing
`league/flysystem-aws-s3-v3` (not included in this repository).

## Quick start with Docker

Install Docker Desktop, then run:

```sh
docker compose up --build
```

Open [http://localhost:8080](http://localhost:8080) and Mailpit at
[http://localhost:8025](http://localhost:8025). The first app start migrates the database,
seeds base data, and (by default) creates demo tickets. Default demo accounts all use
`password`: `admin@example.com` (admin), `agent.aisha@example.com` and
`agent.daniel@example.com` (agents), and `customer.fatima@example.com` and
`customer.omar@example.com` (customers).

Useful commands:

```sh
docker compose exec app php artisan route:list
docker compose exec app ./vendor/bin/pest
docker compose logs queue scheduler
docker compose exec app php artisan imports:generate-sample
docker compose cp app:/var/www/html/storage/app/samples/tickets-sample.csv .
```

Change the web port with `APP_PORT=8081 APP_URL=http://localhost:8081 docker compose up`.
`docker compose down -v` resets this demo by deleting its volumes; do not use it against
important data. Windows users should preserve LF line endings in `docker/entrypoint.sh`.

## Local setup

1. Install PHP 8.3, Composer, MySQL, and Node.js 22.
2. Clone the repository and run `composer install`.
3. Copy `.env.example` to `.env`, set `DB_*` for databases `sla` and `sla_test`, then run `php artisan key:generate`.
4. Run `npm install && npm run build`.
5. Run `php artisan migrate --seed`.
6. Optionally run `php artisan db:seed --class=DemoDataSeeder`.
7. Run `php artisan serve`.

`APP_URL` must match the URL used in the browser because queued emails build absolute links
from it. Run `php artisan queue:work` and `php artisan schedule:work` separately; cron can
run `* * * * * php artisan schedule:run`. Notifications, imports, and SLA escalation need
these workers. Local mail logs by default; Docker uses Mailpit, and Mailtrap is optional.

## Tests and architecture

Create `sla_test`, keep it separate from the development `sla` database, and run:

```sh
./vendor/bin/pest
./vendor/bin/pint --test
```

The test safety guard refuses non-test databases. SLA times are stored in UTC and calculated
in `Asia/Dhaka`; business-hours traversal handles weekends and holidays. A Critical ticket
created Thursday at 17:00 can have a two-business-hour deadline on Sunday at 10:00. Stored
`sla_warning_at` and `sla_due_at` make status queries indexed and plain.

SLA configuration is cached by `SlaConfigLoader` and cleared on save. Ticket number allocation
uses `ticket_sequences`, row locks, and `insertOrIgnore`; assignment uses an atomic update
with an affected-row check. Internal notes are excluded from customer replies, timelines,
notifications, and email. Attachments use private storage, random names, authorized downloads,
and `nosniff`. CSV imports stream rows, preload lookups, bulk insert, record duplicates/errors,
and send no notifications; the queue retry window is 3700 seconds.

Administrators manage categories, SLA policies, hours, holidays, users, roles, permissions,
and configuration history. System roles, the last admin, and self-removal are protected;
removing an agent releases active tickets. Policies combine permission, ownership, and state.
Agents see unassigned or assigned tickets, customers see their own tickets, and admins see all.

## Frontend and imports

Reusable components include `<x-button>`, `<x-primary-button>`, `<x-form.field>`,
`<x-form.input>`, `<x-form.select>`, `<x-form.textarea>`, `<x-form.checkbox>`, and
`<x-form.file>`. Use `<x-form.input name="subject" required />` in a form. Design tokens live
in the `TOKENS:START` block of `resources/css/app.css`; change the brand color there.
Breakpoints are mobile below 640px, tablet 640-1023px, and desktop at 1024px. Staff land on
the dashboard and customers on tickets.

The import template is available at `/imports/template`; generate a large sample with
`php artisan imports:generate-sample {rows} --invalid=5 --duplicates=2`.

## Assumptions and limitations

The SLA clock does not pause while waiting for a customer. There is no category-based routing.
Tickets have no delete UI. MySQL full-text search falls back to `LIKE` for short terms and
other drivers. Imported tickets are unassigned and do not notify users. Email templates do
not use CSS variables. Docker is a demo/review setup with debug enabled and local-demo
password defaults.

See the [database schema specification](docs/database_design_schema_specification.md).
The referenced root `support-ticket-erd.md` is not present in this checkout.

Future improvements include category-based routing, pausable SLA clocks, richer email
theming, production Docker secrets/observability, and durable object storage.

## Run with Docker

Prerequisite: Docker Desktop. Start the three-service local stack with:

```sh
docker compose up --build
```

The app is at [http://localhost:8080](http://localhost:8080) and Mailpit is at
[http://localhost:8025](http://localhost:8025). The first start runs migrations, base seeders,
and demo data; the queue worker and scheduler run inside the app container. Test accounts are
listed above. Run Artisan commands with `docker compose exec app php artisan ...`.
Change ports with `APP_PORT=8081 APP_URL=http://localhost:8081 docker compose up`.
`docker compose down -v` deletes local database and storage data. The production image omits
development dependencies, so run `./vendor/bin/pest` locally against `sla_test`.

## Deploy to Render with TiDB Cloud

Create an empty TiDB Cloud database named `sla`, allow the Render service IP access, and copy
the TiDB host, port `4000`, `<prefix>.root` username, and password. Create a Render Web Service
using the root Dockerfile and health check path `/up`. Generate `APP_KEY` with
`php artisan key:generate --show`, then configure:

| Variable | Purpose |
|---|---|
| `APP_NAME` | Application name |
| `APP_ENV=production` | Production mode |
| `APP_DEBUG=false` | Disable debug output |
| `APP_KEY` | Encryption secret |
| `APP_URL=https://<service>.onrender.com` | Canonical URL |
| `FORCE_HTTPS=true` | HTTPS URL generation |
| `LOG_CHANNEL=stderr` | Render logs |
| `DB_CONNECTION=mysql` | MySQL-compatible driver |
| `DB_HOST` | TiDB host |
| `DB_PORT=4000` | TiDB TLS port |
| `DB_DATABASE` | TiDB database |
| `DB_USERNAME` | TiDB `<prefix>.root` user |
| `DB_PASSWORD` | TiDB password |
| `MYSQL_ATTR_SSL_CA=/etc/ssl/certs/ca-certificates.crt` | TLS CA |
| `TICKETS_FULLTEXT_SEARCH=false` | Use LIKE on TiDB |
| `SESSION_DRIVER=database` | Database sessions |
| `CACHE_STORE=database` | Database cache |
| `QUEUE_CONNECTION=database` | Database queue |
| `DB_QUEUE_RETRY_AFTER=3700` | Long import retry window |
| `MAIL_MAILER=log` | Log emails |
| `SEED_PASSWORD` | Seed account password |
| `RUN_SEED=true` | Enable initial seeding |
| `SEED_DEMO=true` | Optional reviewer demo data |

On Render's free tier, sleeping pauses queue and scheduler work; state-based idempotent SLA
checks catch up on the next tick. Local attachments/import files are lost after restart or
redeploy. TiDB uses LIKE keyword search and emails go to logs. For troubleshooting, verify
the CA path, the `<prefix>.root` username, TiDB IP allow-list, Render `PORT`, and any
`start.sh` missing-variable message.
