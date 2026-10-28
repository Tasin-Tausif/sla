# Support Ticket & SLA System - ERD

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
        boolean is_active
        timestamp created_at
        timestamp updated_at
    }

    agent_category {
        bigint user_id PK, FK
        bigint category_id PK, FK
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
        varchar priority
        varchar status
        bigint category_id FK
        bigint created_by FK
        bigint assigned_agent_id FK "nullable"
        timestamp assigned_at "nullable"
        bigint sla_policy_id FK "nullable"
        int sla_minutes "snapshot of policy applied"
        timestamp sla_due_at
        timestamp sla_warning_sent_at "nullable"
        timestamp sla_breached_at "nullable"
        timestamp sla_paused_at "nullable, optional"
        int sla_paused_minutes "default 0, optional"
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
        varchar stored_path
        varchar disk
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
        varchar job_batch_id "nullable, links to job_batches.id"
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
        varchar log_name "nullable"
        text description
        varchar subject_type "nullable"
        varchar event "nullable"
        bigint subject_id "nullable"
        varchar causer_type "nullable"
        bigint causer_id "nullable"
        json properties "nullable"
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

    users ||--o{ agent_category : "user_id"
    categories ||--o{ agent_category : "category_id"

    users ||--o{ import_batches : "user_id"
    import_batches ||--o{ import_errors : "import_batch_id"

    roles ||--o{ role_has_permissions : "role_id"
    permissions ||--o{ role_has_permissions : "permission_id"
    roles ||--o{ model_has_roles : "role_id"
    permissions ||--o{ model_has_permissions : "permission_id"
```

## Indexes

**tickets**
- `(status, assigned_agent_id)` - agent queues, unassigned list
- `(status, sla_due_at)` - escalation scheduler
- `(created_by, status)` - customer views
- `(category_id, status)`, `(priority, status)`, `created_at`
- Unique: `ticket_number`, `external_reference` (nullable)

**ticket_replies**: `(ticket_id, created_at)`, optionally `(ticket_id, is_internal)`

**attachments**: `ticket_id`, `reply_id`

**notifications**: `(notifiable_type, notifiable_id)` (Laravel default)

**activity_log**: `(subject_type, subject_id)`, `(causer_type, causer_id)`, `log_name`

**import_errors**: `import_batch_id`

**users**: `(is_available, last_assigned_at)` - auto-assignment

## Design notes

1. SLA deadlines are computed in business minutes using `business_hours` and `holidays`.
2. `sla_minutes` is a snapshot of the policy applied; recalculate only on priority change.
3. `sla_paused_*` columns are optional (only for pausing on "Waiting for Customer").
4. `agent_category` is optional (only for category-based auto-assignment).
5. Concurrent assignment: atomic `UPDATE ... WHERE assigned_agent_id IS NULL` or `lockForUpdate()` in a transaction.
6. Ticket numbers: increment `ticket_sequences` inside a transaction with a row lock.
