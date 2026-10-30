<?php

namespace App\Policies;

use App\Models\ImportBatch;
use App\Models\User;

class ImportBatchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('tickets.import');
    }

    public function create(User $user): bool
    {
        return $user->can('tickets.import');
    }

    public function view(User $user, ImportBatch $batch): bool
    {
        return $user->can('tickets.import')
            && ($user->hasRole('admin') || $batch->user_id === $user->id);
    }
}
