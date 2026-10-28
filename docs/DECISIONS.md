Database design first

Designing the schema before the code is the right order here. Everything downstream (policies, SLA, assignment, import) depends on it, and migrations are the most expensive thing to change later. Below is the full schema, then the decisions you should settle before opencode writes a single migration.

Tables overview
Table	Source	Purpose
users	Laravel + 1 migration	Accounts, agent availability
roles, permissions, model_has_*, role_has_permissions	Spatie (done)	RBAC
activity_log	Spatie (done) + 1 index migration	Audit timeline
categories	New	Ticket categories
tickets	New	Core entity
ticket_sequences	New	Race-safe ticket numbers
ticket_replies	New	Public replies and internal notes
attachments	New	Files on tickets or replies
import_batches, import_errors	New	CSV import tracking
notifications	Laravel	Database notifications
jobs, failed_jobs, sessions, cache	Laravel defaults	Already exist
Table definitions

users (add to the existing table)

is_available boolean, default true (auto-assignment eligibility)
max_open_tickets unsigned smallint, nullable (optional workload cap)
No role column. Spatie owns roles.

categories

id, name (unique), slug (unique), is_active boolean default true, timestamps

tickets

Column	Type	Notes
id	bigint PK	
ticket_number	varchar(20)	unique, e.g. TKT-2026-000001
subject	varchar(255)	
description	text	
priority	varchar(20)	enum cast, not a DB enum
status	varchar(30)	enum cast
category_id	FK categories	restrictOnDelete
created_by	FK users	restrictOnDelete
assigned_agent_id	FK users, nullable	nullOnDelete
sla_due_at	timestamp, nullable	UTC, indexed, the key to fast SLA queries
first_response_at	timestamp, nullable	
resolved_at, closed_at	timestamp, nullable	
escalated_at	timestamp, nullable	null = never escalated
escalation_level	unsigned tinyint, default 0	
sla_warning_sent_at	timestamp, nullable	prevents repeated warnings
external_reference	varchar(100), nullable	CSV duplicate detection
timestamps		

Indexes on tickets (each maps to a real query):

unique ticket_number
(status, sla_due_at): scheduler scan, SLA filters, dashboard breach counts
(assigned_agent_id, status): an agent’s queue and the auto-assign workload count
(created_by, status): a customer’s “my tickets”
(priority, status): dashboard by priority and the Critical count
(category_id), (created_at)
unique (created_by, external_reference): MySQL allows many NULLs in a unique index, so normal tickets are unaffected

One change from what I suggested earlier: I’m using (assigned_agent_id, status) instead of (status, assigned_agent_id). An agent’s queue filters by agent first, and with agent leading, the index serves both “all of agent X’s tickets” and “agent X’s open tickets”. The unassigned queue (assigned_agent_id IS NULL AND status = open) also uses it.

ticket_sequences

year smallint unsigned PK, last_number unsigned int

ticket_replies

id, ticket_id (FK, cascadeOnDelete), user_id (FK, restrictOnDelete), body text, is_internal boolean default false, timestamps
Index (ticket_id, is_internal, created_at). The customer view filters is_internal = false and sorts by time, and this serves both.

attachments

id, ticket_id (FK, cascade), reply_id (nullable FK, cascade), uploaded_by (FK), original_name, stored_path, disk (default local), mime_type, size unsigned bigint, timestamps
Indexes on ticket_id and reply_id
Visibility is derived: an attachment on an internal reply is hidden from customers. Don’t duplicate an is_internal flag here.

import_batches

id, user_id, original_name, file_path, status (pending, processing, completed, failed), total_rows, processed_rows, imported_rows, duplicate_rows, failed_rows, started_at, finished_at, timestamps

import_errors

id, import_batch_id (FK, cascade), row_number, raw_data json, error_message, created_at
Index (import_batch_id, row_number)

activity_log (Spatie, existing)

Use log_name = 'ticket', subject = the ticket, causer = the user (null means System), event = a fixed vocabulary, properties = {old: ..., new: ...}
Add one migration for an index on (subject_type, subject_id, created_at). The timeline query is “all entries for this ticket, newest first”.
Event vocabulary: created, assigned, unassigned, status_changed, priority_changed, replied, note_added, attachment_added, sla_warning, escalated, resolved, closed, reopened

notifications: generate with php artisan make:notifications-table (older Laravel: notifications:table).

Relationships
User 1─∞ Ticket (created_by)        User 1─∞ Ticket (assigned_agent_id)
Category 1─∞ Ticket                  Ticket 1─∞ TicketReply 1─∞ Attachment
Ticket 1─∞ Attachment                Ticket 1─∞ Activity (morph, Spatie)
User 1─∞ ImportBatch 1─∞ ImportError
Decisions to settle now

These affect the schema, so decide before opencode builds. My recommended defaults:

Priority changes after creation: recompute sla_due_at from the original created time with the new priority’s SLA. If the new due time is already past, the ticket is breached immediately. Simple and explainable.
“Waiting for Customer”: don’t pause the SLA clock. Say so in the README as a known limitation. Pausing needs sla_paused_at and sla_paused_minutes columns. Add them only if you finish early.
Agent visibility: agents see unassigned tickets plus their own, not everyone’s. This makes the “accept” flow meaningful. Admin sees all.
Resolved vs closed: agents resolve, then the ticket auto-closes (or the admin closes it). A customer reply on a resolved ticket reopens it. Keep this as a documented transition map.
Auto-assignment trigger: on ticket creation (queued job), picking the available agent with the fewest open tickets, ties broken by who was assigned least recently. Manual assign remains for admin.
Soft deletes: skip them. Tickets are never deleted in your flows, and restricted foreign keys protect history.
Suggested workflow from here
Stage	Output	Time
1. Database	docs/database.md (the tables above), then migrations, models, factories, seeders	now, to hour 3
2. Backend design	A short written plan: services, policies, permission map, routes, events and jobs, state transition map	20 min
3. Backend build	Phases 2 to 8 from the earlier plan	hours 3 to 11
4. Frontend	Blade pages: list, detail, create, dashboard, import	hours 11 to 13
5. README and interview prep		last 3 hours

Doing the frontend last is fine, but build the backend with feature tests that hit routes, so each feature works before it has a UI. That keeps the frontend phase quick and low-risk.

Next step

Tell me if you accept the six defaults above (or change any). Then I’ll produce two things you can paste straight in:

A docs/database.md file with this schema, so opencode has a single source of truth
The Phase 1 prompt rewritten to build exactly this schema

If you’d like a visual first, I can also draw the ER diagram so you can check the relationships before any code is written.
