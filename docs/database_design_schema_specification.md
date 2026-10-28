# Database Design First

Designing the schema before the code is the right order here. Everything downstream (policies, SLA, assignment, import) depends on it, and migrations are the most expensive thing to change later. Below is the full schema, followed by the architectural decisions to settle before writing migrations.

---

## Tables Overview

| Table | Source | Purpose |
| :--- | :--- | :--- |
| `users` | Laravel + 1 migration | User accounts, agent availability, and workload capacity |
| `roles`, `permissions`, `model_has_*`, `role_has_permissions` | Spatie (Installed) | Role-Based Access Control (RBAC) |
| `activity_log` | Spatie (Installed) + 1 index migration | Audit timeline & history |
| `categories` | New | Ticket categories |
| `tickets` | New | Core ticket entity |
| `ticket_sequences` | New | Race-safe ticket numbers generation |
| `ticket_replies` | New | Public customer/agent replies and internal notes |
| `attachments` | New | Files uploaded on tickets or replies |
| `import_batches`, `import_errors` | New | CSV import processing and error tracking |
| `notifications` | Laravel | Database notifications table |
| `jobs`, `failed_jobs`, `sessions`, `cache` | Laravel Defaults | Infrastructure tables (already exist) |

---

## Table Definitions

### 1. `users` (Additions to existing table)
* `is_available` (`boolean`, default `true`): Auto-assignment eligibility.
* `max_open_tickets` (`unsigned smallint`, `nullable`): Optional workload cap.
* *Note: No `role` column is needed as Spatie handles roles.*

### 2. `categories`
* `id` (`bigint`, PK)
* `name` (`varchar(255)`, unique)
* `slug` (`varchar(255)`, unique)
* `is_active` (`boolean`, default `true`)
* `created_at`, `updated_at` (`timestamps`)

### 3. `tickets`

| Column | Type | Notes |
| :--- | :--- | :--- |
| `id` | `bigint` PK | Primary Key |
| `ticket_number` | `varchar(20)` | Unique (e.g., `TKT-2026-000001`) |
| `subject` | `varchar(255)` | Ticket title |
| `description` | `text` | Detailed description |
| `priority` | `varchar(20)` | Enum cast (not DB enum) |
| `status` | `varchar(30)` | Enum cast (not DB enum) |
| `category_id` | FK `categories` | `restrictOnDelete` |
| `created_by` | FK `users` | `restrictOnDelete` |
| `assigned_agent_id` | FK `users`, `nullable` | `nullOnDelete` |
| `sla_due_at` | `timestamp`, `nullable` | UTC, indexed (key to fast SLA queries) |
| `first_response_at` | `timestamp`, `nullable` | First agent response timestamp |
| `resolved_at` | `timestamp`, `nullable` | Resolution timestamp |
| `closed_at` | `timestamp`, `nullable` | Closure timestamp |
| `escalated_at` | `timestamp`, `nullable` | `null` = never escalated |
| `escalation_level` | `unsigned tinyint` | Default `0` |
| `sla_warning_sent_at` | `timestamp`, `nullable` | Prevents duplicate warning notifications |
| `external_reference` | `varchar(100)`, `nullable` | CSV duplicate detection |
| `created_at`, `updated_at` | `timestamps` | Standard Laravel timestamps |

#### Indexes on `tickets`:
* `unique(ticket_number)`
* `(status, sla_due_at)`: Scheduler scans, SLA filters, dashboard breach counts.
* `(assigned_agent_id, status)`: Agent queue and auto-assign workload count.
* `(created_by, status)`: Customer "my tickets" view.
* `(priority, status)`: Priority dashboards and Critical ticket count.
* `(category_id)`
* `(created_at)`
* `unique(created_by, external_reference)`: MySQL allows multiple NULLs in a unique index, so standard tickets remain unaffected.

> **Index Strategy Note:** We use `(assigned_agent_id, status)` instead of `(status, assigned_agent_id)`. Because an agent’s queue filters by agent first, putting `assigned_agent_id` leading allows the index to serve both "all of agent X's tickets" and "agent X's open tickets", as well as unassigned queries (`assigned_agent_id IS NULL AND status = 'open'`).

### 4. `ticket_sequences`
* `year` (`smallint unsigned`, PK)
* `last_number` (`unsigned int`)

### 5. `ticket_replies`
* `id` (`bigint`, PK)
* `ticket_id` (FK `tickets`, `cascadeOnDelete`)
* `user_id` (FK `users`, `restrictOnDelete`)
* `body` (`text`)
* `is_internal` (`boolean`, default `false`)
* `created_at`, `updated_at` (`timestamps`)
* **Index:** `(ticket_id, is_internal, created_at)` — Optimized for customer views that filter `is_internal = false` and sort by time.

### 6. `attachments`
* `id` (`bigint`, PK)
* `ticket_id` (FK `tickets`, `cascadeOnDelete`)
* `reply_id` (FK `ticket_replies`, `nullable`, `cascadeOnDelete`)
* `uploaded_by` (FK `users`)
* `original_name` (`varchar(255)`)
* `stored_path` (`varchar(255)`)
* `disk` (`varchar(50)`, default `'local'`)
* `mime_type` (`varchar(100)`)
* `size` (`unsigned bigint`)
* `created_at`, `updated_at` (`timestamps`)
* **Indexes:** `ticket_id`, `reply_id`
* *Visibility is derived:* An attachment linked to an internal reply automatically inherits internal visibility (no duplicate `is_internal` flag required).

### 7. `import_batches`
* `id` (`bigint`, PK)
* `user_id` (FK `users`)
* `original_name` (`varchar(255)`)
* `file_path` (`varchar(255)`)
* `status` (`varchar(30)`) — `pending`, `processing`, `completed`, `failed`
* `total_rows`, `processed_rows`, `imported_rows`, `duplicate_rows`, `failed_rows` (`unsigned int`, default `0`)
* `started_at`, `finished_at` (`timestamp`, `nullable`)
* `created_at`, `updated_at` (`timestamps`)

### 8. `import_errors`
* `id` (`bigint`, PK)
* `import_batch_id` (FK `import_batches`, `cascadeOnDelete`)
* `row_number` (`unsigned int`)
* `raw_data` (`json`)
* `error_message` (`text`)
* `created_at` (`timestamp`)
* **Index:** `(import_batch_id, row_number)`

### 9. `activity_log` (Spatie Configuration)
* `log_name` = `'ticket'`, `subject` = Ticket instance, `causer` = User instance (`null` = System), `event` = Fixed vocabulary string, `properties` = `{"old": ..., "new": ...}`.
* **Add Migration:** Index on `(subject_type, subject_id, created_at)` to optimize "ticket history timeline (newest first)" queries.
* **Event Vocabulary:** `created`, `assigned`, `unassigned`, `status_changed`, `priority_changed`, `replied`, `note_added`, `attachment_added`, `sla_warning`, `escalated`, `resolved`, `closed`, `reopened`.

### 10. `notifications`
* Generated via `php artisan make:notifications-table`.

---

## Entity Relationships

```
User 1 ─────────< Ticket (created_by)
User 1 ─────────< Ticket (assigned_agent_id)
Category 1 ─────< Ticket
Ticket 1 ───────< TicketReply 1 ───< Attachment
Ticket 1 ───────< Attachment
Ticket 1 ───────< ActivityLog (Polymorphic)
User 1 ─────────< ImportBatch 1 ───< ImportError
```

---

## Architectural Decisions

1. **Priority Changes After Creation:** Recompute `sla_due_at` from the original creation timestamp using the new priority's SLA limit. If the calculated due time is already in the past, the ticket is flagged as breached immediately.
2. **"Waiting for Customer" SLA Clock:** SLA clock continues running without pausing. (Documented in README as a design decision; pausing requires extra tracking columns).
3. **Agent Ticket Visibility:** Support agents see unassigned tickets and tickets assigned to themselves. Administrators can view all tickets across the system.
4. **Resolved vs Closed Lifecycle:** Agents mark tickets as `Resolved`. Tickets auto-close via scheduled background jobs (or via explicit Admin action). Customer responses on `Resolved` tickets trigger an automatic status change back to `In Progress` / `Reopened`.
5. **Auto-Assignment Strategy:** Triggered upon ticket creation via a queued background job. Evaluates active, available agents, assigning to the agent with the lowest open ticket count (ties broken by the agent assigned least recently).
6. **Soft Deletes:** Omitted. Tickets are retained permanently for audit compliance, with FK restrictions protecting historical records.

---

## Suggested Development Workflow

| Stage | Output | Estimated Time |
| :--- | :--- | :--- |
| **1. Database Setup** | Documentation (`docs/database.md`), Migrations, Models, Factories, Seeders | ~2 - 3 Hours |
| **2. Backend Design** | Written architectural plan: Services, Policies, Middleware, Route Map, Events/Jobs | ~20 Mins |
| **3. Backend Build** | Core Ticket logic, SLA engine, CSV importer, Notifications & Testing | ~8 Hours |
| **4. Frontend Views** | Blade templates (Dashboard, Ticket Index, Detail Page, Import Form) | ~2 - 3 Hours |
| **5. Documentation & Prep** | Comprehensive README, setup instructions, interview prep | ~3 Hours |