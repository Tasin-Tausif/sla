# Support Ticket & SLA Management System

Laravel 13 / PHP 8.3 / MySQL take-home assessment. Stack: Breeze (Blade + Tailwind), Pest 4, spatie/laravel-permission (roles: admin, agent, customer), spatie/laravel-activitylog v4, league/csv.

The design document is `support-ticket-erd.md` in the repo root. Read it, never edit it.

## Rules for AI agents
- Do NOT install Laravel Boost or run `boost:install`. Do not install any new packages and do not make network requests. Use only what is in `composer.json` and `vendor`.
- Do not ask questions. Make reasonable assumptions and list them at the end.
- Be economical: use targeted reads and searches, run focused tests per task, and run the full suite and `./vendor/bin/pint` once at the end.
- Never commit real credentials.
- The development database is `sla` and contains user data. Never run `migrate:fresh`, `migrate:refresh`, `migrate:reset`, `db:wipe`, or destructive seed commands against it. Only run the explicitly approved idempotent seeders when the task requires them.

## Conventions
- Business logic lives in `app/Services`: TicketService, TicketReplyService, TicketAssignmentService, TicketActivityLogger, TicketNumberGenerator, Sla\SlaCalculator, Sla\SlaConfigLoader. Controllers are thin, validation is in Form Requests, authorization uses `Gate::authorize()` with `app/Policies/TicketPolicy.php`.
- Enums live in `app/Enums` (TicketStatus, TicketPriority, ImportStatus). Never hardcode status or priority strings. Active statuses = `TicketStatus::activeValues()`.
- Times are stored in UTC and displayed and SLA-calculated in Asia/Dhaka (`config('sla.timezone')`).
- `sla_breached_at`, `sla_warning_sent_at` and `escalated_at` are idempotency markers set by the scheduler. They are never used to decide whether a ticket is currently breached (use the `breachedSla` / `approachingSla` scopes).
- Internal notes must never reach customers: not in replies, activity timeline, notifications or emails.
- Listeners are auto-discovered from `handle()` type hints. Do not register them manually.

## Testing
- `./vendor/bin/pest` and `./vendor/bin/pint`. If MySQL is unavailable use `DB_CONNECTION=sqlite DB_DATABASE=:memory:` as an environment override only.