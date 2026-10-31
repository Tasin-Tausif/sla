<?php

namespace App\Services\Admin;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketActivityLogger;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserAdminService
{
    public function __construct(
        private readonly ConfigAuditLogger $audit,
        private readonly TicketActivityLogger $ticketActivity,
    ) {}

    public function create(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $user = User::create([
                'name' => $data['name'], 'email' => $data['email'],
                'password' => Hash::make($data['password']), 'email_verified_at' => now(),
                'is_available' => in_array('agent', $data['roles'], true) ? (bool) ($data['is_available'] ?? true) : false,
                'max_open_tickets' => in_array('agent', $data['roles'], true) ? ($data['max_open_tickets'] ?? 10) : 0,
            ]);
            $user->syncRoles($data['roles']);
            $this->audit->log('created', 'User created', $user, [], ['name' => $user->name, 'email' => $user->email, 'roles' => $data['roles']]);

            return $user;
        });
    }

    public function update(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data): User {
            $oldRoles = $user->getRoleNames()->all();
            $newRoles = $data['roles'];
            if ($user->is(auth()->user()) && ! in_array('admin', $newRoles, true) && in_array('admin', $oldRoles, true)) {
                throw ValidationException::withMessages(['roles' => 'You cannot remove your own admin role.']);
            }
            if (in_array('admin', $oldRoles, true) && ! in_array('admin', $newRoles, true) && User::role('admin')->count() <= 1) {
                throw ValidationException::withMessages(['roles' => 'The last administrator cannot be demoted.']);
            }
            $isAgent = in_array('agent', $newRoles, true);
            $user->fill(['name' => $data['name'], 'email' => $data['email'], 'is_available' => $isAgent ? (bool) ($data['is_available'] ?? true) : false, 'max_open_tickets' => $isAgent ? ($data['max_open_tickets'] ?? 10) : 0]);
            if (! empty($data['password'])) {
                $user->password = Hash::make($data['password']);
            }
            $user->save();
            $user->syncRoles($newRoles);
            $this->audit->log('updated', 'User updated', $user, ['roles' => $oldRoles], ['roles' => $newRoles]);
            if (in_array('agent', $oldRoles, true) && ! $isAgent) {
                $this->releaseAgentWorkload($user, auth()->user());
            }

            return $user;
        });
    }

    public function delete(User $user, User $actor): void
    {
        if ($user->is($actor)) {
            throw ValidationException::withMessages(['user' => 'You cannot delete yourself.']);
        }
        if ($user->hasRole('admin') && User::role('admin')->count() <= 1) {
            throw ValidationException::withMessages(['user' => 'The last administrator cannot be deleted.']);
        }
        if ($user->createdTickets()->exists() || $user->ticketReplies()->exists() || $user->attachments()->exists() || $user->importBatches()->exists()) {
            throw ValidationException::withMessages(['user' => 'This user has history, cannot be deleted.']);
        }
        $this->releaseAgentWorkload($user, $actor);
        try {
            $user->delete();
        } catch (QueryException) {
            throw ValidationException::withMessages(['user' => 'This user has history, cannot be deleted.']);
        }
        $this->audit->log('deleted', 'User deleted', null, ['user_id' => $user->id]);
    }

    public function makeAgent(User $user, User $actor): void
    {
        if (! $user->hasRole('agent')) {
            $user->assignRole('agent');
            $user->forceFill(['is_available' => true, 'max_open_tickets' => 10])->save();
            $this->audit->log('role_assigned', 'Agent role assigned', $user, ['roles' => $user->getRoleNames()->reject(fn ($r) => $r === 'agent')->values()->all()], ['roles' => $user->getRoleNames()->all()]);
        }
    }

    public function removeAgent(User $user, User $actor): void
    {
        if ($user->hasRole('agent')) {
            $user->removeRole('agent');
            $user->forceFill(['is_available' => false, 'max_open_tickets' => 0])->save();
            $this->releaseAgentWorkload($user, $actor);
        }
    }

    public function releaseAgentWorkload(User $agent, User $actor): void
    {
        $tickets = Ticket::query()->where('assigned_agent_id', $agent->id)->whereIn('status', TicketStatus::activeValues())->get();
        foreach ($tickets as $ticket) {
            $ticket->forceFill(['assigned_agent_id' => null, 'assigned_at' => null])->save();
            $this->ticketActivity->log($ticket, 'unassigned', 'Ticket unassigned', $actor, [
                'old' => ['assigned_agent_id' => $agent->id],
                'new' => ['assigned_agent_id' => null],
            ]);
            $this->audit->log('agent_workload_released', 'Agent workload released', $ticket, ['assigned_agent_id' => $agent->id], ['assigned_agent_id' => null]);
        }
    }
}
