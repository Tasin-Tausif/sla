# Project rules
- Laravel (version in repo), Blade + Breeze + Tailwind. No SPA, no API unless asked.
- Before using a package, read its installed README/config in vendor/ (versions are new). Do not rely on memory.
- Roles/permissions: spatie/laravel-permission (roles: admin, agent, customer). No `role` column on users.
  Policies handle ownership rules and call `$user->can(...)` for permissions.
- Audit history: spatie/laravel-activitylog, wrapped in `App\Services\TicketActivityLogger`.
  Every entry sets event, causer (null = System), subject (ticket), description, and old/new properties.
  Do not rely on auto-logging alone.
- CSV import: league/csv streaming in a queued job. No maatwebsite/excel.
- Packages allowed: spatie/laravel-permission, spatie/laravel-activitylog, league/csv, Pest. Ask before adding others.
- SLA minutes, business days/hours, timezone live in `config/support.php`.
  `SlaCalculator` is handwritten, never "now + N hours".
- Store datetimes in UTC, calculate business hours in Asia/Dhaka.
- Business logic in `app/Services` or `app/Actions`. Controllers stay thin.
- Always use Form Requests, Policies, eager loading, and paginate().
- Ticket assignment: atomic conditional update or lockForUpdate, never check-then-update.
- Internal notes filtered in queries (a `visibleTo` scope), not just hidden in views.
- Customer lists are scoped by created_by in the query, not only by permission.
- Attachments on the private disk, downloaded through an authorized controller.
- Tests: Pest, RefreshDatabase on MySQL `sla_test` (see phpunit.xml).
  Never migrate/seed it manually, never point tests at `sla`, never switch to SQLite.
  In tests, seed roles/permissions and clear the permission cache (PermissionRegistrar::forgetCachedPermissions).
- Write tests per feature and run `php artisan test` before finishing a task.
- Do not touch unrelated files. No secrets in the repo.
- Small, clear commits after each task.
