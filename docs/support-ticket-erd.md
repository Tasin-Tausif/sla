# Support Ticket & SLA System - ERD (refined)

## Scope decisions

| Item | Decision |
|---|---|
| `agent_category` | Removed. Auto-assignment uses workload + availability, not category skills. Future work. |
| `sla_paused_at`, `sla_paused_minutes` | Removed. The SLA clock is not paused on "Waiting for Customer". Documented limitation. |
| `holidays` | Kept. `SlaCalculator` skips holiday dates (one extra check in the day loop). |
| Soft deletes on `tickets` | Kept in the schema only. No delete UI is built. |
| `external_reference` | Globally unique (nullable). Used for CSV duplicate detection. |
| `sla_due_at` | NOT NULL. Factory, seeder, `TicketService::create()` and the CSV import must compute it before insert. |

```mermaid
erDiagram
    users {
        bigint id PK
        varchar name
        varchar email UK
        timestamp email_verified_at
        varchar password
        boolean is_available "default true, agents only"
        smallint max_open_tickets "default 10, agents only"
        timestamp last_assigned_at "nullable, round-robin tie-breaker"
        varchar remember_token
        timestamp created_at
        timestamp updated_at
    }

    categories {
        bigint id PK
        varchar name UK
        varchar slug UK
        boolean is_active "default true"
        timestamp created_at
        timestamp updated_at
    }

    sla_policies {
        bigint id PK
        varchar priority UK "low, medium, high, critical"
        int resolution_minutes "business minutes"
        tinyint warning_threshold_percent "e.g. 80"
        boolean is_active
        timestamp created_at
        timestamp updated_at
    }

    business_hours {
        bigint id PK
        tinyint day_of_week UK "0=Sun .. 6=Sat"
        boolean is_working_day
        time start_time "nullable"
        time end_time "nullable"
        timestamp created_at
        timestamp updated_at
    }

    holidays {
        bigint id PK
        date holiday_date UK
        varchar name
        timestamp created_at
        timestamp updated_at
    }

    tickets {
        bigint id PK
        varchar ticket_number UK "TKT-2026-000001"
        varchar subject
        text description
        varchar priority "enum cast"
        varchar status "enum cast"
        bigint category_id FK
        bigint created_by FK
        bigint assigned_agent_id FK "nullable"
        timestamp assigned_at "nullable"
        bigint sla_policy_id FK "nullable"
        int sla_minutes "snapshot of policy applied"
        timestamp sla_due_at "NOT NULL, UTC"
        timestamp sla_warning_sent_at "nullable"
        timestamp sla_breached_at "nullable"
        timestamp first_response_at "nullable"
        timestamp resolved_at "nullable"
        bigint resolved_by FK "nullable"
        timestamp closed_at "nullable"
        bigint closed_by FK "nullable"
        timestamp escalated_at "nullable"
        tinyint escalation_level "default 0"
        varchar external_reference UK "nullable, CSV dedupe"
        bigint import_batch_id FK "nullable"
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at "nullable, soft delete"
    }

    ticket_replies {
        bigint id PK
        bigint ticket_id FK
        bigint user_id FK
        text body
        boolean is_internal "default false"
        timestamp created_at
        timestamp updated_at
    }

    attachments {
        bigint id PK
        bigint ticket_id FK
        bigint reply_id FK "nullable"
        bigint uploaded_by FK
        varchar original_name
        varchar stored_path "randomized name, private disk"
        varchar disk "default local"
        varchar mime_type
        bigint size
        timestamp created_at
        timestamp updated_at
    }

    import_batches {
        bigint id PK
        bigint user_id FK
        varchar original_name
        varchar file_path
        varchar job_batch_id "nullable"
        varchar status "pending, processing, completed, failed"
        int total_rows
        int processed_rows
        int imported_rows
        int duplicate_rows
        int failed_rows
        timestamp started_at "nullable"
        timestamp finished_at "nullable"
        timestamp created_at
        timestamp updated_at
    }

    import_errors {
        bigint id PK
        bigint import_batch_id FK
        int row_number
        json raw_data
        text error_message
        timestamp created_at
    }

    ticket_sequences {
        smallint year PK
        int last_number
    }

    activity_log {
        bigint id PK
        varchar log_name "ticket"
        text description
        varchar subject_type "nullable"
        varchar event "nullable"
        bigint subject_id "nullable"
        varchar causer_type "nullable"
        bigint causer_id "nullable, null = System"
        json properties "old / new values"
        char batch_uuid "nullable"
        timestamp created_at
        timestamp updated_at
    }

    notifications {
        char id PK "uuid"
        varchar type
        varchar notifiable_type
        bigint notifiable_id
        text data
        timestamp read_at "nullable"
        timestamp created_at
        timestamp updated_at
    }

    roles {
        bigint id PK
        varchar name
        varchar guard_name
        timestamp created_at
        timestamp updated_at
    }

    permissions {
        bigint id PK
        varchar name
        varchar guard_name
        timestamp created_at
        timestamp updated_at
    }

    role_has_permissions {
        bigint permission_id PK, FK
        bigint role_id PK, FK
    }

    model_has_roles {
        bigint role_id PK, FK
        varchar model_type PK
        bigint model_id PK
    }

    model_has_permissions {
        bigint permission_id PK, FK
        varchar model_type PK
        bigint model_id PK
    }

    users ||--o{ tickets : "created_by (customer)"
    users |o--o{ tickets : "assigned_agent_id"
    users |o--o{ tickets : "resolved_by"
    users |o--o{ tickets : "closed_by"
    categories ||--o{ tickets : "category_id"
    sla_policies |o--o{ tickets : "sla_policy_id"
    import_batches |o--o{ tickets : "import_batch_id"

    tickets ||--o{ ticket_replies : "ticket_id"
    users ||--o{ ticket_replies : "user_id"
    tickets ||--o{ attachments : "ticket_id"
    ticket_replies |o--o{ attachments : "reply_id"
    users ||--o{ attachments : "uploaded_by"

    users ||--o{ import_batches : "user_id"
    import_batches ||--o{ import_errors : "import_batch_id"

    roles ||--o{ role_has_permissions : "role_id"
    permissions ||--o{ role_has_permissions : "permission_id"
    roles ||--o{ model_has_roles : "role_id"
    permissions ||--o{ model_has_permissions : "permission_id"
```

## Foreign key on-delete rules

| Column | Rule | Why |
|---|---|---|
| `tickets.created_by` | restrictOnDelete | Never lose the ticket owner |
| `tickets.category_id` | restrictOnDelete | Deactivate categories (`is_active`) instead of deleting |
| `tickets.assigned_agent_id` | nullOnDelete | Ticket returns to the unassigned queue |
| `tickets.resolved_by`, `tickets.closed_by` | nullOnDelete | History stays, actor reference cleared |
| `tickets.sla_policy_id` | nullOnDelete | `sla_minutes` snapshot keeps the applied SLA |
| `tickets.import_batch_id` | nullOnDelete | Tickets outlive the import record |
| `ticket_replies.ticket_id` | cascadeOnDelete | Replies belong to the ticket |
| `ticket_replies.user_id` | restrictOnDelete | Keep conversation authorship |
| `attachments.ticket_id`, `attachments.reply_id` | cascadeOnDelete | Attachments belong to ticket or reply |
| `attachments.uploaded_by` | restrictOnDelete | Keep uploader |
| `import_batches.user_id` | restrictOnDelete | Keep who ran the import |
| `import_errors.import_batch_id` | cascadeOnDelete | Errors belong to the batch |

## Indexes

Each index maps to a real query.

**tickets**
- Unique: `ticket_number`, `external_reference` (nullable; MySQL allows many NULLs in a unique index)
- `(status, sla_due_at)`: escalation scheduler, SLA status filters, dashboard breach counts
- `(assigned_agent_id, status)`: agent queue, workload count for auto-assign, unassigned queue (`assigned_agent_id IS NULL`)
- `(created_by, status)`: customer "my tickets"
- `(priority, status)`: dashboard by priority, Critical count
- `(category_id, status)`
- `(created_at)`: date filters and default sorting

**ticket_replies**
- `(ticket_id, is_internal, created_at)`: the customer view filters `is_internal = false` and sorts by time

**attachments**
- `(ticket_id)`, `(reply_id)`

**import_errors**
- `(import_batch_id, row_number)`

**users**
- `(is_available, last_assigned_at)`: auto-assignment lookup

**activity_log** (Spatie defaults plus one follow-up migration)
- Defaults: `(subject_type, subject_id)`, `(causer_type, causer_id)`, `log_name`
- Added: `(subject_type, subject_id, created_at)`: the timeline query "all entries for this ticket, newest first"

**notifications**
- `(notifiable_type, notifiable_id)` (Laravel default)

## Activity log conventions

- `log_name = 'ticket'`, `subject` = the ticket, `causer` = the user (null is displayed as "System").
- `properties = {"old": {...}, "new": {...}}`.
- Event vocabulary: `created`, `assigned`, `unassigned`, `status_changed`, `priority_changed`, `replied`, `note_added`, `attachment_added`, `sla_warning`, `escalated`, `resolved`, `closed`, `reopened`.
- All entries are written through `TicketActivityLogger`, never by calling `activity()` directly.

## Design notes

1. SLA deadlines are computed in business minutes from `business_hours` and `holidays`. Times are stored in UTC and calculated in `Asia/Dhaka`.
2. `SlaCalculator` is a pure class. It receives a cached config object (business hours, holidays, policies) and never queries the database inside its loop.
3. `sla_minutes` is a snapshot of the policy applied at creation. On a priority change, `sla_due_at` is recomputed from `created_at` with the new priority's minutes.
4. SLA status is derived from stored columns: breached when `sla_due_at < now` and the ticket is not resolved or closed; approaching when within the warning threshold of the due time.
5. Idempotent escalation: `sla_warning_sent_at`, `sla_breached_at` and `escalated_at` are set with guarded updates (`whereNull(...)`), so overlapping scheduler runs cannot notify twice.
6. Concurrent assignment: atomic `UPDATE tickets SET assigned_agent_id = ? WHERE id = ? AND assigned_agent_id IS NULL`, then check the affected row count.
7. Ticket numbers: increment `ticket_sequences` for the current year inside a transaction with `lockForUpdate()`. The unique index on `ticket_number` is the safety net.
8. Attachment visibility is derived from the reply (`reply.is_internal`), not duplicated on the attachment.
9. Agent visibility: agents see unassigned tickets plus their own. Admins see all. Customers see only tickets where `created_by` is themselves.

## Not implemented (future work)

- Pausing the SLA clock on "Waiting for Customer" (needs `sla_paused_at`, `sla_paused_minutes`)
- Category-based agent routing (`agent_category` pivot)
- Holiday management UI (holidays are seeded)
