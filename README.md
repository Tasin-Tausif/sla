# Support Ticket & SLA Management System

A Laravel 13 / PHP 8.3 application where customers raise support tickets and agents work them
against business-hours SLAs. It covers ticket management, role-based access, automatic and
manual assignment (safe under concurrency), internal notes, attachments, SLA tracking with
escalation, queued notifications, search and filtering, CSV import, a dashboard, a full audit
trail and an administration area.

- **Source code:** https://github.com/TasinTausif/support-ticket-sla
- **Live demo (Render + TiDB Cloud):** https://sla-5a8g.onrender.com
  (free hosting: the first request after inactivity can take up to a minute; demo credentials
  are provided in the submission email)
- **Docker image:** https://hub.docker.com/r/tasintausif/support-ticket-sla

## Contents

1. [Requirement coverage](#1-requirement-coverage)
2. [Tech stack](#2-tech-stack)
3. [Quick start with Docker](#3-quick-start-with-docker)
4. [Local setup (without Docker)](#4-local-setup-without-docker)
5. [Environment configuration](#5-environment-configuration)
6. [Queue worker and scheduler](#6-queue-worker-and-scheduler)
7. [Test accounts](#7-test-accounts)
8. [Roles and permissions](#8-roles-and-permissions)
9. [SLA, business hours and escalation](#9-sla-business-hours-and-escalation)
10. [Ticket assignment and concurrency](#10-ticket-assignment-and-concurrency)
11. [Conversation, internal notes and attachments](#11-conversation-internal-notes-and-attachments)
12. [Notifications](#12-notifications)
13. [Search, filtering and dashboard](#13-search-filtering-and-dashboard)
14. [CSV import](#14-csv-import)
15. [Activity and audit history](#15-activity-and-audit-history)
16. [Administration area](#16-administration-area)
17. [Database design and performance](#17-database-design-and-performance)
18. [Frontend](#18-frontend)
19. [Running the tests](#19-running-the-tests)
20. [Deploying to Render with TiDB Cloud](#20-deploying-to-render-with-tidb-cloud)
21. [Assumptions and limitations](#21-assumptions-and-limitations)
22. [Troubleshooting](#22-troubleshooting)
23. [Future improvements](#23-future-improvements)

---

## 1. Requirement coverage

| # | Assessment requirement | Implementation |
|---|---|---|
| 1 | Ticket management (create, view, update, resolve/close, active and closed lists) | `TicketController`, `TicketService`, `TicketStatusController`, `TicketPriorityController`; Active / Closed tabs on the ticket list |
| 2 | Ticket ID generation (`TKT-2026-000001`) | `TicketNumberGenerator` with a per-year row in `ticket_sequences` (transaction, `lockForUpdate`, `insertOrIgnore`) and a unique index on `ticket_number` |
| 3 | Roles and permissions | Spatie permission, permission catalog in `config/access.php`, non-destructive `RolePermissionSeeder`, `TicketPolicy`, permission middleware on admin routes |
| 4 | Manual and automatic assignment | `TicketAssignmentService` (`claim`, `assignManually`, `autoAssign`); strategy documented in section 10 |
| 5 | Concurrent assignment | Single atomic `UPDATE ... WHERE assigned_agent_id IS NULL` with an affected-row check; tested by simulating the race |
| 6 | Conversation and internal notes | `TicketReplyService`, `is_internal` flag, protected in queries, policy, timeline and notifications |
| 7 | Attachments | `UploadedFileStorage`, `AttachmentController`, private disk, random names, type and size validation, authorized download |
| 8 | SLA management | `Sla\SlaCalculator`, `Sla\SlaConfigLoader`, `sla_policies` table, `sla_due_at` / `sla_warning_at` columns, SLA scopes on `Ticket` |
| 9 | Business hours | `business_hours` and `holidays` tables, edited in the admin area; worked example in section 9 |
| 10 | SLA escalation | `SlaEscalationService`, `tickets:check-sla` command scheduled every minute, idempotent guarded updates |
| 11 | Notifications | Events, queued listeners, queued mail + database notifications, in-app notification page |
| 12 | Search and filtering | `FilterTicketsRequest`, `TicketFilter` (ticket number, keyword, status, priority, category, agent, customer, date range, SLA status), pagination |
| 13 | CSV import | `ImportController`, `ProcessTicketImport` job, `TicketCsvImporter`, `import_batches` / `import_errors` |
| 14 | Dashboard | `DashboardController`, `DashboardService` (aggregate queries, priority and agent breakdowns, urgent list) |
| 15 | Activity / audit history | spatie activitylog through `TicketActivityLogger` (ticket timeline) and `ConfigAuditLogger` (configuration history) |
| 16 | Database and performance | Query-driven indexes, eager loading, pagination, bulk inserts for imports, see section 17 |
| 17 | Automated tests | Pest feature and unit tests, see section 19 |
| Bonus | Docker setup, CI workflow, admin configuration area, theming with CSS variables, responsive UI, import progress tracking, configuration audit log | see the relevant sections |

## 2. Tech stack

- PHP 8.3, Laravel 13, MySQL 8 (TiDB Cloud in production)
- Blade views, Tailwind CSS (via Breeze), Vite
- `spatie/laravel-permission` (roles and permissions)
- `spatie/laravel-activitylog` (audit trail)
- `league/csv` (streamed CSV import and export)
- Pest 4 and Laravel Pint for tests and code style
- Database queue driver, database cache and sessions (no Redis required)

## 3. Quick start with Docker

Prerequisite: Docker Desktop.

```sh
docker compose up --build
```

- Application: http://localhost:8080
- Mailpit (catches all outgoing email): http://localhost:8025

On first start the container runs migrations, the base seeders and the demo data seeder. The
queue worker and the scheduler run inside the same container, so emails, CSV imports and SLA
escalation work without any extra command.

Useful commands:

```sh
docker compose exec app php artisan <command>      # run any artisan command
APP_PORT=8081 APP_URL=http://localhost:8081 docker compose up   # use another port
docker compose down                                  # stop (keeps data)
docker compose down -v                               # stop and DELETE the database and files
```

The image contains production dependencies only, so run the test suite with a local PHP
installation (section 19).

## 4. Local setup (without Docker)

Requirements: PHP 8.3 with the `pdo_mysql`, `mbstring`, `intl`, `zip`, `bcmath` and `fileinfo`
extensions, Composer, Node 20+ and MySQL 8.

Run the commands in this order:

```sh
composer install
npm install
npm run build
cp .env.example .env            # Windows PowerShell: Copy-Item .env.example .env
php artisan key:generate
```

Create the two databases (the second one is used only by the tests):

```sh
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS sla CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; CREATE DATABASE IF NOT EXISTS sla_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Set `DB_HOST`, `DB_PORT`, `DB_DATABASE=sla`, `DB_USERNAME` and `DB_PASSWORD` in `.env`, and set
`APP_URL` to the address you open in the browser (queued emails build their links from it).
Then migrate and seed:

```sh
php artisan migrate
php artisan db:seed                              # roles, permissions, categories, SLA policies,
                                                 # business hours, holidays and demo users
php artisan db:seed --class=DemoDataSeeder       # optional: about 40 demo tickets
```

**Seeding must run before you use the app**, because the roles and permissions are created by
the seeders. Then start three processes in separate terminals:

```sh
php artisan serve          # terminal 1: the web application
php artisan queue:work     # terminal 2: emails, notifications, CSV import
php artisan schedule:work  # terminal 3: SLA checks every minute
```

On Laragon or Valet you can serve the project from the web server instead of `php artisan serve`.
Never run `migrate:fresh` against a database that holds data you want to keep.

## 5. Environment configuration

The important settings in `.env` (see `.env.example` for the complete list):

| Variable | Example | Purpose |
|---|---|---|
| `APP_URL` | `http://localhost:8000` | Must match the URL you browse; used for links inside queued emails |
| `DB_*` | `sla` database | Main database connection |
| `QUEUE_CONNECTION` | `database` | Queued notifications and imports |
| `DB_QUEUE_RETRY_AFTER` | `3700` | Must be longer than the import job timeout (3600 s), so a long import is not picked up twice |
| `CACHE_STORE` / `SESSION_DRIVER` | `database` | No Redis needed |
| `MAIL_MAILER` | `log` | Emails are written to `storage/logs/laravel.log`; use `smtp` with Mailpit or Mailtrap to inspect them |
| `SEED_PASSWORD` | strong value | Password given to the seeded demo users (defaults to `password`); set a strong value on any public deployment |
| `TICKETS_FULLTEXT_SEARCH` | `true` | Use MySQL FULLTEXT for keyword search; set `false` on TiDB, where search falls back to `LIKE` |
| `FORCE_HTTPS` | `false` | Set `true` behind an HTTPS proxy such as Render |
| `MYSQL_ATTR_SSL_CA` | unset locally | Path of the CA bundle when the database requires TLS (TiDB Cloud). Leave it unset for a local MySQL |
| `TICKETS_ATTACHMENTS_DISK` / `IMPORTS_DISK` | `local` | Storage disk for attachments and uploaded CSV files |
| `DB_PROTECT_DESTRUCTIVE` | `false` | Set `true` locally to block `migrate:fresh` and `db:wipe` on your development database |

## 6. Queue worker and scheduler

Two background processes are required for the full feature set:

- `php artisan queue:work` sends queued emails and in-app notifications and runs CSV imports.
- `php artisan schedule:work` runs `tickets:check-sla` every minute (SLA warnings and
  escalations). On a server use cron instead: `* * * * * php artisan schedule:run`.

You can run the SLA check manually at any time with `php artisan tickets:check-sla`.
With Docker (and on Render) both processes run automatically inside the application container.

Mail: the `log` driver is the default. Docker uses Mailpit. Mailtrap or any SMTP provider
works by setting the `MAIL_*` variables.

## 7. Test accounts

Created by `UserSeeder`. The password is the value of `SEED_PASSWORD`, or `password` when it is
not set.

| Email | Role |
|---|---|
| `admin@example.com` | Administrator |
| `agent.aisha@example.com` | Support agent |
| `agent.daniel@example.com` | Support agent |
| `customer.fatima@example.com` | Customer |
| `customer.omar@example.com` | Customer |

New registrations on the sign-up page become customers.

## 8. Roles and permissions

It is one application with role-based menus, not three separate panels. After login, staff
(admins and agents) land on the dashboard and customers land on their ticket list.

Authorization is enforced on the server: every action goes through `TicketPolicy` (permission
check plus ownership and state rules), form requests, and `can:` middleware on the admin
routes. Hiding a button in the UI is never the only protection.

| Capability | Administrator | Support agent | Customer |
|---|---|---|---|
| Tickets visible | All | Unassigned tickets and their own | Only tickets they created |
| Create ticket | Yes | No | Yes |
| Edit ticket | Yes | Assigned tickets | Own ticket while it is Open |
| Reply | Yes | Visible, non-closed tickets | Own, non-closed tickets |
| Internal notes | Yes | Yes | Never |
| Claim an unassigned ticket | No (assigns manually instead) | Yes | No |
| Assign tickets to an agent | Yes | No | No |
| Change priority / resolve / close / reopen | Yes | Own assigned tickets | No |
| Dashboard | Yes | Yes | No |
| CSV import | Yes | Yes | No |
| Configuration pages | Everything | None by default | None |

The permission catalog and the default role matrix live in `config/access.php`.
`RolePermissionSeeder` is non-destructive: it creates what is missing, always gives the admin
role every permission, and never removes or overwrites changes an administrator made in the UI.

Safeguards: the admin role is locked and always has every permission; the built-in roles cannot
be renamed or deleted; administrators cannot delete themselves, remove their own admin role,
or remove the last administrator. Extra roles can be created to grant configuration access
only; ticket visibility is always decided by the built-in roles.

## 9. SLA, business hours and escalation

**Policies** (stored in `sla_policies`, editable in the admin area, measured in business minutes):

| Priority | Resolution time | Warning at |
|---|---|---|
| Low | 48 hours | 80 % of the time |
| Medium | 24 hours | 80 % |
| High | 8 hours | 80 % |
| Critical | 2 hours | 80 % |

**Business hours** (`business_hours`) default to Sunday to Thursday, 09:00 to 18:00, with holidays
in `holidays`. Times are stored in UTC and the calculation runs in `Asia/Dhaka`
(`config/sla.php`).

**Calculation.** `SlaCalculator` walks forward day by day: it skips non-working days and
holidays, starts at the opening time if the ticket arrives early, jumps to the next day if it
arrives after closing, and consumes the remaining minutes inside each working window.
Worked example: a Critical ticket (120 minutes) created on Thursday at 17:00 uses 60 minutes
until 18:00, skips Friday and Saturday, and is due on **Sunday at 10:00**. The calculator is a
plain class that receives its configuration, so it is unit-tested without a database.

**Configurability.** Policies, hours and holidays are read through the cached `SlaConfigLoader`;
the cache is cleared automatically whenever they are saved. Each ticket stores a snapshot of
its SLA (`sla_minutes`, `sla_due_at`, `sla_warning_at`), so changes apply to tickets created or
re-prioritised afterwards. When the priority changes, the due and warning times are recomputed
from the original creation time and the escalation markers are reset if the new deadline is in
the future.

**SLA status** is derived from two indexed columns, so the list filter, the dashboard and the
scheduler all use the same simple queries:

- Within SLA: open ticket and `sla_warning_at` is in the future
- Approaching breach: open ticket, `sla_warning_at <= now` and `sla_due_at > now`
- Breached: open ticket and `sla_due_at < now`

**Escalation.** `tickets:check-sla` runs every minute. For each newly approaching ticket it sets
`sla_warning_sent_at`, `escalation_level = 1`, writes an activity entry and notifies the assigned
agent and all administrators (administrators only if unassigned). For each newly breached
ticket it sets `sla_breached_at`, `escalated_at`, `escalation_level = 2`, writes an activity
entry and notifies the same people. The updates are guarded (`WHERE ... IS NULL` and an
affected-row check), so overlapping scheduler runs can never notify twice. Resolved and closed
tickets are ignored.

## 10. Ticket assignment and concurrency

Three ways to assign:

1. **Claim:** an agent takes an unassigned ticket.
2. **Manual:** an administrator assigns or reassigns a ticket to any agent.
3. **Automatic:** runs when a ticket is created.

**Automatic strategy (workload-based with a fair tie-break).** Candidates are agents who are
marked available and whose number of open assigned tickets is below their `max_open_tickets`.
The agent with the fewest open tickets wins; ties go to the agent who was assigned least
recently (`last_assigned_at`, never-assigned first), then the lowest id. If nobody qualifies the
ticket stays unassigned and any agent can claim it.

**Concurrency.** Two agents accepting the same ticket at the same moment is handled by one atomic
statement:

```sql
UPDATE tickets SET assigned_agent_id = ?, assigned_at = now(), ...
WHERE id = ? AND assigned_agent_id IS NULL
```

The database applies it to a single row, so only one request reports one affected row. The other
gets zero and is told the ticket was just taken. Nothing is read first and written later, so
there is no gap to race through. Automatic assignment additionally locks the chosen candidate
row (`lockForUpdate`) inside a transaction and re-checks the workload limit before claiming.
A test simulates the race.

## 11. Conversation, internal notes and attachments

**Replies.** Customers and agents reply on the ticket. A customer reply moves "Waiting for
Customer" back to "In Progress" and reopens a "Resolved" ticket. The first public reply from
staff sets `first_response_at`; internal notes never do.

**Internal notes are protected at every layer:** they are excluded from the reply query for
users without the internal-note permission, the policy refuses customers who forge the field,
the `note_added` event is hidden from customers' activity timeline, note bodies are never
written to the activity log, and internal notes never trigger notifications or emails.

**Attachments** can be added to a ticket or a reply. Files are validated for type and size
(`config/tickets.php`: at most 5 files of 5 MB; pdf, png, jpg, jpeg, gif, txt, csv, docx,
xlsx), stored on a private disk under random names (the original name is only metadata),
and served through an authorized download route that applies the same visibility rules as the
ticket (attachments of internal notes are staff-only) with `X-Content-Type-Options: nosniff`.
If saving fails, the database transaction is rolled back and already stored files are removed.

## 12. Notifications

Events are dispatched after the database commit; queued listeners send queued notifications on
the `mail` and `database` channels (three attempts with backoff). Email never blocks a web
request.

| Event | Recipient |
|---|---|
| Ticket created | The customer (confirmation with SLA due time) |
| Ticket assigned | The assigned agent |
| Agent replied | The customer |
| Customer replied | The assigned agent, or administrators if unassigned |
| Ticket resolved | The customer |
| SLA approaching / SLA breached | Assigned agent and administrators |

The author of an action is never notified about it. Notification links are stored as relative
paths, so they keep working on any host, and the in-app list is available under
"Notifications" with an unread counter. Opening a notification re-checks that the user may
still view the ticket.

## 13. Search, filtering and dashboard

**Ticket list filters** (all optional, combined with AND, always applied on top of the
visibility rules so a filter can never reveal another user's tickets): ticket number (prefix
search), keyword in subject and description, status, priority, category, assigned agent
(including "Unassigned"), customer name or email (matches any word), created date range
(interpreted in `Asia/Dhaka`), SLA status, and a whitelisted sort order. Results are paginated
(15 per page) and keep the query string in the pagination links. Staff-only filters are ignored
for customers.

Keyword search uses MySQL FULLTEXT when available and the term has at least 3 characters, and an
escaped `LIKE` search otherwise (always on TiDB, see `TICKETS_FULLTEXT_SEARCH`).

**Dashboard** (staff only): open, unassigned, critical, breached, approaching, within SLA and
resolved counts from one aggregate query, tickets by priority, tickets by agent (administrators),
and the ten most urgent tickets. Each card links to the matching filtered list. Agents see
numbers limited to the tickets they may access.

## 14. CSV import

Open **Import** (administrators and agents). Download the template from `/imports/template`.

| Column | Required | Rules |
|---|---|---|
| `subject` | yes | up to 255 characters |
| `description` | yes | up to 10 000 characters |
| `priority` | yes | low, medium, high or critical (case-insensitive) |
| `category_slug` | yes | slug of an active category |
| `customer_email` | yes | email of an existing customer (users are never auto-created) |
| `status` | no | open, in_progress, waiting_for_customer, resolved, closed (default open) |
| `external_reference` | no | unique per ticket; used for duplicate detection |
| `created_at` | no | date in `Asia/Dhaka`, not in the future (default now) |

How it works: the upload is stored privately, a queued job streams the file with `league/csv`
and processes it in chunks of 500 rows. Each chunk preloads its lookups (categories, customers,
existing references) in a few queries, validates every row, and bulk-inserts the valid tickets
with their SLA times and a "created by import" activity entry inside one transaction. Invalid
rows are stored in `import_errors` with the CSV line number, the raw row and the reasons;
duplicate `external_reference` values are counted but not treated as errors. A progress page
shows live counters, the error list, and a download of all errors as CSV (protected against
spreadsheet formula injection). Imported tickets are unassigned and send no notifications.
Generate a large test file with:

```sh
php artisan imports:generate-sample 50000 --invalid=5 --duplicates=2
```

## 15. Activity and audit history

Every important ticket event is written through `TicketActivityLogger` and shown on the ticket
page (newest first, with the acting user or "System"): created, assigned, unassigned,
status and priority changes (with old and new values), replies, notes (hidden from customers),
attachments, SLA warnings, escalations, resolve, close and reopen. Entries created by the
scheduler have no causer and are displayed as "System".

Administrators also have a **Configuration history** page for changes to users, roles,
permissions, categories, SLA policies, business hours and holidays (`log_name = admin`), with
old and new values. Passwords and tokens are never logged.

## 16. Administration area

Available from the profile menu (**Configuration**) according to permission:

- **Users:** create, edit and delete users; assign roles with checkboxes; "make agent" and
  "remove agent"; availability and maximum open tickets for agents. Removing the agent role (or
  deleting the user) returns the agent's active tickets to the unassigned queue with an activity
  entry. Users who have history cannot be deleted.
- **Roles and permissions:** create custom roles, edit which permissions each role has, reset
  built-in roles to their defaults, and view the permission catalog.
- **Categories:** create, edit, deactivate and delete (only when unused).
- **SLA policies, business hours and holidays:** edit the configuration described in section 9.
- **Configuration history:** audit log of all of the above.

## 17. Database design and performance

The schema and the reasoning behind it are documented in
[`docs/database_design_schema_specification.md`](docs/database_design_schema_specification.md),
including the ERD.

Main tables: `users`, `categories`, `tickets`, `ticket_replies`, `attachments`, `sla_policies`,
`business_hours`, `holidays`, `ticket_sequences`, `import_batches`, `import_errors`, plus the
Spatie permission tables, `activity_log` and `notifications`.

- **Integrity:** foreign keys with deliberate delete rules (for example the owner and the
  category are restricted, an agent's tickets return to the unassigned queue when the agent is
  removed), unique indexes on `ticket_number` and `external_reference`, transactions around
  multi-step writes, and soft deletes on tickets.
- **Indexes are driven by real queries:** `(status, sla_due_at)` and `(status, sla_warning_at)`
  for SLA status and the scheduler; `(assigned_agent_id, status)` for agent queues, workload and
  the unassigned list; `(created_by, status)` for "my tickets"; `(priority, status)` and
  `(category_id, status)` for filters and the dashboard; `created_at` for date filters;
  `(ticket_id, is_internal, created_at)` on replies; a FULLTEXT index on subject and
  description on MySQL; and the activity log indexes for the ticket timeline.
- **N+1 and pagination:** lists eager-load their relations, every list is paginated, the dashboard
  is built from a handful of aggregate queries, the import uses preloaded lookups and bulk
  inserts, and SLA status is a plain indexed comparison rather than a per-row calculation.
- **Timestamps:** stored in UTC, displayed in `Asia/Dhaka`.

## 18. Frontend

Blade views with reusable components: `<x-button>`, `<x-form.field>`, `<x-form.input>`,
`<x-form.select>`, `<x-form.textarea>`, `<x-form.checkbox>`, `<x-form.file>`, status, priority
and SLA badges, and a responsive list/table component. All colours, radii and shadows come from
CSS variables defined in the `TOKENS:START` block of `resources/css/app.css`, so the brand
colour can be changed in one place. Breakpoints: mobile below 640 px, tablet 640 to 1023 px,
desktop from 1024 px. Guard tests fail the build if a view contains a raw hard-coded colour or a
raw button or input. Email templates do not use CSS variables.

## 19. Running the tests

Tests use their own database so the development data is never touched. Create it once
(section 4), then:

```sh
./vendor/bin/pest            # whole suite
./vendor/bin/pest --filter=Sla    # a subset
./vendor/bin/pint --test     # code style check
```

`phpunit.xml` points at `sla_test`, and a guard aborts the run if the active database name does
not contain "test". Mail is captured in memory and the queue runs synchronously during tests.

Areas covered: SLA calculation (including the Thursday 17:00 Critical example, weekends,
holidays, early and late arrivals), ticket numbering, atomic assignment and the simulated
race, auto-assignment rules, policies and visibility for every role, internal notes never
reaching customers, attachments (validation, private storage, download authorization), reply
side effects, notifications and events, SLA escalation and its idempotency, filters and
dashboard counts, CSV import (valid rows, errors, duplicates, chunking, no side effects),
the administration area and its safeguards, upload failures, and the theming and component
guards.

A GitHub Actions workflow (`.github/workflows/tests.yml`) runs Pint and the Pest suite against
a MySQL service on every push.

## 20. Deploying to Render with TiDB Cloud

The `Dockerfile` builds one image (Apache + PHP) that also runs the queue worker and the
scheduler, so a single Render web service is enough.

1. **TiDB Cloud:** create a cluster and an empty database named `sla`. Allow access from
   anywhere (or Render's outbound addresses). Note the host, port `4000`, the username (it looks
   like `<prefix>.root`) and the password. TLS is required.
2. **APP_KEY:** run `php artisan key:generate --show` locally and keep the value.
3. **Render:** create a new Web Service from this repository, runtime **Docker**, health check
   path `/up`, and set the environment variables below.
4. Deploy. On first start the container runs the migrations and, because `RUN_SEED=true`, seeds
   the roles, permissions, base data and demo users.

| Variable | Value |
|---|---|
| `APP_NAME` | `Support Tickets` |
| `APP_ENV` / `APP_DEBUG` | `production` / `false` |
| `APP_KEY` | secret, from step 2 |
| `APP_URL` | `https://<your-service>.onrender.com` |
| `FORCE_HTTPS` | `true` |
| `LOG_CHANNEL` | `stderr` |
| `DB_CONNECTION` / `DB_PORT` | `mysql` / `4000` |
| `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | secrets from TiDB Cloud |
| `MYSQL_ATTR_SSL_CA` | `/etc/ssl/certs/ca-certificates.crt` |
| `TICKETS_FULLTEXT_SEARCH` | `false` (TiDB uses a different full-text feature, so search uses `LIKE`) |
| `SESSION_DRIVER`, `CACHE_STORE`, `QUEUE_CONNECTION` | `database` |
| `DB_QUEUE_RETRY_AFTER` | `3700` |
| `MAIL_MAILER` | `log` (or SMTP settings) |
| `SEED_PASSWORD` | secret, password of the demo accounts |
| `RUN_SEED` / `SEED_DEMO` | `true` / `true` for a reviewer demo |

Free-tier caveats: the service sleeps after about 15 minutes without traffic, so the queue
worker and scheduler pause and SLA checks catch up on the next run (they are state-based and
idempotent); files written to the container (attachments, import files) do not survive a
restart or redeploy unless an S3-compatible disk is configured; emails go to the log unless
SMTP is configured.

## 21. Assumptions and limitations

- The SLA clock does not pause while a ticket waits for the customer.
- Automatic assignment is workload-based; there is no category-based routing.
- SLA policies and business hours support one working window per day and four fixed priorities.
- Holidays are managed in the admin area; there is no recurring-holiday rule.
- Tickets have no delete screen (soft deletes exist in the schema).
- Imported tickets are unassigned and send no notifications.
- Keyword search falls back to `LIKE` for short terms and on TiDB.
- Email templates do not use the CSS variable theme.
- The Docker and Render setups are demo and review setups (default local passwords in
  `compose.yaml`, ephemeral file storage on the free Render tier).

## 22. Troubleshooting

| Problem | Fix |
|---|---|
| `There is no permission named ...` | Seeding is missing: `php artisan db:seed --class=RolePermissionSeeder` then `php artisan permission:cache-reset` |
| Emails or imports do nothing | `php artisan queue:work` is not running |
| No SLA warnings or escalations | `php artisan schedule:work` is not running (or run `php artisan tickets:check-sla`) |
| Links in emails point to the wrong address | Set `APP_URL` to the URL you browse, then restart the queue worker |
| `Cannot connect to MySQL using SSL` locally | Remove `MYSQL_ATTR_SSL_CA` from `.env` and from your shell environment |
| A change in `.env` has no effect | `php artisan config:clear`, and restart `queue:work` (workers keep the old config) |
| Upload fails | Check `upload_max_filesize` and `post_max_size` in `php.ini` (at least 20M and 25M); `php artisan uploads:check` prints the current values |
| Render: SSL or access denied errors | Check the `MYSQL_ATTR_SSL_CA` path and that the username includes the `<prefix>.` part |
| Render: 502 or the app does not start | Check the deploy log for the start script message about missing variables |

## 23. Future improvements

- Pause the SLA clock while waiting for the customer
- Category-based agent routing and skill groups
- S3-compatible storage for attachments and import files
- Real-time updates and a REST API with documentation
- Rate limiting on public endpoints
