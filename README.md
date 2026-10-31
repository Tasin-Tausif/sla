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
| CSV import | `ImportController`, `TicketCsvImporter`, `imports:generate-sample` |
| Audit history | `TicketActivityLogger` and `AuditController` |
| Authorization | `TicketPolicy`, `ImportBatchPolicy`, permission middleware |
| Testing | Pest feature/unit suite and Pint |

## First-time setup (local)

Run these commands in order:

```sh
composer install
npm install
npm run build
cp .env.example .env
php artisan key:generate
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS sla CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; CREATE DATABASE IF NOT EXISTS sla_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Set `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` in `.env` for the
`sla` database, then run:

```sh
php artisan migrate
php artisan db:seed
# Optional demo tickets:
php artisan db:seed --class=DemoDataSeeder
```

Seeding must run before using the app because it creates the roles and permissions. Then use
three separate terminals:

```sh
# Terminal 1
php artisan serve

# Terminal 2
php artisan queue:work

# Terminal 3
php artisan schedule:work
```

If you see `There is no permission named ...`, run
`php artisan db:seed --class=RolePermissionSeeder` followed by
`php artisan permission:cache-reset`.

## Roles and access

This is one application with role-based menus, not three separate panels. Ticket visibility is
decided by the built-in roles: admins see all tickets, agents see unassigned and their assigned
tickets, and customers see tickets they created.

| Access | Admin | Agent | Customer |
|---|---|---|---|
| What they see | All tickets, all users, all configuration permitted by the application | Unassigned and assigned tickets, dashboard, imports, notifications, profile | Their own tickets, notifications, profile |
| Ticket actions | View, create, update, reply, internal notes, assign, claim, resolve, close, reopen, change priority | View, update assigned tickets, claim unassigned tickets, reply, internal notes, resolve/close/reopen assigned tickets | Create, view, update own open tickets, reply to non-closed tickets, close own resolved tickets |
| Internal notes | Yes | Yes | No |
| Assignment | Assign tickets as admin; claim is available through the permission matrix | Claim unassigned tickets; cannot assign tickets to other users | No |
| Import | Yes | Yes | No |
| Dashboard | Yes | Yes | No |
| Configuration pages | Categories, SLA/calendar, users, roles, permissions, and audit history | None by default | None |
| Landing page after login | Dashboard | Dashboard | Ticket list |

The admin role always has every catalog permission and cannot be reduced. Admins grant or
revoke permissions for the other roles on the Roles page. Safeguards prevent deleting yourself,
removing the last admin, or renaming/deleting system roles. Extra roles can be created for
configuration access, but ticket visibility remains controlled by the built-in roles.

## Test accounts

`UserSeeder` creates these accounts. Their password is `SEED_PASSWORD` when configured;
otherwise the default is `password`.

| Email | Role | Password |
|---|---|---|
| `admin@example.com` | Admin | `SEED_PASSWORD` or `admin@example.com` |
| `agent.aisha@example.com` | Agent | `SEED_PASSWORD` or `agent.aisha@example.com` |
| `agent.daniel@example.com` | Agent | `SEED_PASSWORD` or `password` |
| `customer.fatima@example.com` | Customer | `SEED_PASSWORD` or `password` |
| `customer.omar@example.com` | Customer | `SEED_PASSWORD` or `password` |

## Run with Docker

Prerequisite: Docker Desktop.

```sh
docker compose up --build
```

The app is at [http://localhost:8080](http://localhost:8080) and Mailpit at
[http://localhost:8025](http://localhost:8025). The first start runs migrations, base seeders,
and demo data. The queue worker and scheduler run automatically in the Docker setup, so emails,
CSV imports, and SLA escalation are processed without separate worker commands.

Run Artisan commands with `docker compose exec app php artisan ...`. Change the port with
`APP_PORT=8081 APP_URL=http://localhost:8081 docker compose up`. `docker compose down -v`
deletes local database and storage data. The production image omits development dependencies,
so run `./vendor/bin/pest` locally against `sla_test`.

## Background processes

For local use, `php artisan queue:work` must be running for queued emails and CSV imports.
`php artisan schedule:work` must be running for SLA escalation via `tickets:check-sla`.
Cron may run `* * * * * php artisan schedule:run`. Local mail uses the log driver by default;
Docker uses Mailpit.

## Tests and architecture

Create `sla_test`, keep it separate from the development `sla` database, and run:

```sh
./vendor/bin/pest
./vendor/bin/pint --test
```

SLA times are stored in UTC and calculated in `Asia/Dhaka`; business-hours traversal handles
weekends and holidays. SLA configuration is cached by `SlaConfigLoader` and cleared on save.
Ticket number allocation uses `ticket_sequences`, row locks, and `insertOrIgnore`; assignment
uses an atomic update with an affected-row check.

Internal notes are excluded from customer replies, timelines, notifications, and email.
Attachments use private storage, random names, authorized downloads, and `nosniff`. CSV imports
stream rows, preload lookups, bulk insert, record duplicates/errors, and send no notifications.

## Frontend and imports

Reusable components include `<x-button>`, `<x-primary-button>`, `<x-form.field>`,
`<x-form.input>`, `<x-form.select>`, `<x-form.textarea>`, `<x-form.checkbox>`, and
`<x-form.file>`. Design tokens live in the `TOKENS:START` block of `resources/css/app.css`.
Breakpoints are mobile below 640px, tablet 640-1023px, and desktop at 1024px.

The import template is available at `/imports/template`; generate a sample with
`php artisan imports:generate-sample {rows} --invalid=5 --duplicates=2`.

## Assumptions and limitations

The SLA clock does not pause while waiting for a customer. There is no category-based routing.
Tickets have no delete UI. Imported tickets are unassigned and do not notify users. Email
templates do not use CSS variables. Docker is a demo/review setup with debug enabled and
local-demo password defaults.

See the [database schema specification](docs/database_design_schema_specification.md).
The referenced root `support-ticket-erd.md` is not present in this checkout.
