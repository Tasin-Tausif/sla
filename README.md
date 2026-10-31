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
