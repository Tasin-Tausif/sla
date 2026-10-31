# sla

## Theming and responsive design

The design tokens live in the `/* TOKENS:START */` block in
[`resources/css/app.css`](resources/css/app.css). Change the brand colours,
surface colours, borders, typography, shadows, radii, and layout spacing there;
Tailwind utilities consume those variables through `tailwind.config.js`.

Token groups include semantic colours, ticket status/priority/SLA colours,
shape and shadow values, typography, container sizing, page padding, and touch
control height. The responsive breakpoints are mobile below 640px, tablet from
640px through 1023px, and desktop at 1024px and above.

Email templates are intentionally not themed with CSS variables because mail
clients do not reliably support them.

## Administration

Administrators can manage categories, SLA policies, business hours, holidays,
users, roles, permissions, and configuration history from the Configuration
area. Each section is protected by its matching permission, while ticket
visibility remains controlled by the built-in admin, agent, and customer roles.
Users cannot remove the last administrator, delete themselves, or delete users
with ticket history.

SLA, business-hours, and holiday changes apply to tickets created or
re-prioritised after saving; existing tickets keep their stored SLA snapshot.
The permission catalog lives in `config/access.php`. Running
`php artisan db:seed --class=RolePermissionSeeder` is non-destructive: it
keeps administrator edits and adds only newly introduced default permissions.

## Reusable Blade components

Shared controls keep the Breeze/Tailwind design consistent:

- Buttons: `<x-button>`, `<x-primary-button>`, `<x-secondary-button>`, and `<x-danger-button>`.
- Forms: `<x-form.field>`, `<x-form.input>`, `<x-form.select>`, `<x-form.textarea>`, `<x-form.checkbox>`, and `<x-form.file>`.
- Existing Breeze components include `<x-input-label>`, `<x-input-error>`, and `<x-text-input>`.

```blade
<x-button href="{{ route('tickets.create') }}">New ticket</x-button>

<x-form.field name="subject" label="Subject">
    <x-form.input name="subject" required />
</x-form.field>

<x-form.select name="status" :options="\App\Enums\TicketStatus::options()" placeholder="All statuses" />
```
