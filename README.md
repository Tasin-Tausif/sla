# sla

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
