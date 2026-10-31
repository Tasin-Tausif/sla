<?php

namespace App\Services\Admin;

use Illuminate\Database\Eloquent\Model;

class ConfigAuditLogger
{
    public function log(string $event, string $description, ?Model $subject = null, array $old = [], array $new = []): void
    {
        activity('admin')
            ->causedBy(auth()->user())
            ->event($event)
            ->performedOn($subject)
            ->withProperties([
                'old' => $this->sanitize($old),
                'new' => $this->sanitize($new),
            ])
            ->log($description);
    }

    private function sanitize(array $values): array
    {
        $result = [];
        foreach ($values as $key => $value) {
            if (preg_match('/password|remember_token|token/i', (string) $key)) {
                continue;
            }
            $result[$key] = is_array($value) ? $this->sanitize($value) : $value;
        }

        return $result;
    }
}
