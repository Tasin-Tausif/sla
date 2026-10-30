<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $ticket->ticket_number }}
            </h2>

            @can('update', $ticket)
                <a href="{{ route('tickets.edit', $ticket) }}"
                   class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    {{ __('Edit') }}
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md text-sm">
                    {{ session('status') }}
                </div>
            @endif

            @foreach ($errors->all() as $error)
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-md text-sm">
                    {{ $error }}
                </div>
            @endforeach

            {{-- Ticket header --}}
            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-6 py-4 border-b border-gray-100 flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">{{ $ticket->subject }}</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            {{ __('Created by :name on :date', [
                                'name' => $ticket->creator?->name ?? __('Unknown'),
                                'date' => $ticket->created_at->format('Y-m-d H:i'),
                            ]) }}
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <x-ticket.status-badge :ticket="$ticket" />
                        <x-ticket.priority-badge :ticket="$ticket" />
                        <x-ticket.sla-badge :ticket="$ticket" />
                    </div>
                </div>

                <div class="px-6 py-4 grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                    <div>
                        <div class="text-xs uppercase text-gray-400">{{ __('Category') }}</div>
                        <div class="text-gray-700">{{ $ticket->category?->name ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-xs uppercase text-gray-400">{{ __('Assignee') }}</div>
                        <div class="text-gray-700">{{ $ticket->assignedAgent?->name ?? __('Unassigned') }}</div>
                    </div>
                    <div>
                        <div class="text-xs uppercase text-gray-400">{{ __('SLA due') }}</div>
                        <div class="text-gray-700">{{ $ticket->sla_due_at?->format('Y-m-d H:i') ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-xs uppercase text-gray-400">{{ __('First response') }}</div>
                        <div class="text-gray-700">{{ $ticket->first_response_at?->format('Y-m-d H:i') ?? __('Not yet') }}</div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="px-6 py-4 border-t border-gray-100 flex flex-wrap items-end gap-4">
                    @can('claim', $ticket)
                        <form method="POST" action="{{ route('tickets.claim', $ticket) }}">
                            @csrf
                            <x-primary-button>{{ __('Claim') }}</x-primary-button>
                        </form>
                    @endcan

                    @can('assign', $ticket)
                        <form method="POST" action="{{ route('tickets.assign', $ticket) }}" class="flex items-end gap-2">
                            @csrf
                            <div>
                                <x-input-label for="agent_id" :value="__('Assign to')" />
                                <select id="agent_id" name="agent_id" required
                                        class="mt-1 block w-56 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    @foreach ($agents as $agent)
                                        <option value="{{ $agent->id }}" @selected($ticket->assigned_agent_id === $agent->id)>
                                            {{ $agent->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error class="mt-2" :messages="$errors->get('agent_id')" />
                            </div>
                            <x-primary-button>{{ __('Assign') }}</x-primary-button>
                        </form>
                    @endcan

                    @php
                        $current = $ticket->status;
                        $statusOptions = collect(App\Enums\TicketStatus::cases())
                            ->reject(fn (App\Enums\TicketStatus $status): bool => $status === $current)
                            ->filter(function (App\Enums\TicketStatus $status) use ($ticket, $current): bool {
                                $ability = match (true) {
                                    $status === App\Enums\TicketStatus::Resolved => 'resolve',
                                    $status === App\Enums\TicketStatus::Closed => 'close',
                                    in_array($current, [App\Enums\TicketStatus::Resolved, App\Enums\TicketStatus::Closed], true)
                                        && in_array($status, [App\Enums\TicketStatus::Open, App\Enums\TicketStatus::InProgress], true) => 'reopen',
                                    default => 'changeStatus',
                                };

                                return Illuminate\Support\Facades\Gate::allows($ability, $ticket);
                            });
                    @endphp

                    @if ($statusOptions->isNotEmpty())
                        <form method="POST" action="{{ route('tickets.status', $ticket) }}" class="flex items-end gap-2">
                            @csrf
                            @method('PATCH')
                            <div>
                                <x-input-label for="status" :value="__('Status')" />
                                <select id="status" name="status" required
                                        class="mt-1 block w-56 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    @foreach ($statusOptions as $status)
                                        <option value="{{ $status->value }}">{{ $status->label() }}</option>
                                    @endforeach
                                </select>
                                <x-input-error class="mt-2" :messages="$errors->get('status')" />
                            </div>
                            <x-secondary-button type="submit">{{ __('Update Status') }}</x-secondary-button>
                        </form>
                    @endif

                    @can('changePriority', $ticket)
                        <form method="POST" action="{{ route('tickets.priority', $ticket) }}" class="flex items-end gap-2">
                            @csrf
                            @method('PATCH')
                            <div>
                                <x-input-label for="priority" :value="__('Priority')" />
                                <select id="priority" name="priority" required
                                        class="mt-1 block w-44 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    @foreach (App\Enums\TicketPriority::cases() as $priority)
                                        <option value="{{ $priority->value }}" @selected($ticket->priority === $priority)>
                                            {{ $priority->label() }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error class="mt-2" :messages="$errors->get('priority')" />
                            </div>
                            <x-secondary-button type="submit">{{ __('Update Priority') }}</x-secondary-button>
                        </form>
                    @endcan
                </div>
            </div>

            {{-- Description --}}
            <div class="bg-white shadow sm:rounded-lg p-6">
                <h4 class="text-sm font-semibold uppercase text-gray-400 mb-2">{{ __('Description') }}</h4>
                <div class="text-gray-800 text-sm leading-relaxed">{!! nl2br(e($ticket->description)) !!}</div>
            </div>

            {{-- Ticket-level attachments --}}
            @if ($attachments->isNotEmpty())
                <div class="bg-white shadow sm:rounded-lg p-6">
                    <h4 class="text-sm font-semibold uppercase text-gray-400 mb-3">{{ __('Attachments') }}</h4>
                    <ul class="space-y-2">
                        @foreach ($attachments as $attachment)
                            <li class="flex items-center justify-between text-sm">
                                <span class="text-gray-700">
                                    {{ $attachment->original_name }}
                                    <span class="text-gray-400">({{ number_format($attachment->size / 1024, 1) }} KB)</span>
                                </span>
                                @can('downloadAttachment', [$ticket, $attachment])
                                    <a href="{{ route('attachments.download', $attachment) }}"
                                       class="text-indigo-600 hover:text-indigo-900">{{ __('Download') }}</a>
                                @endcan
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Conversation --}}
            <div class="bg-white shadow sm:rounded-lg p-6">
                <h4 class="text-sm font-semibold uppercase text-gray-400 mb-4">{{ __('Conversation') }}</h4>

                <div class="space-y-4">
                    @forelse ($replies as $reply)
                        <div class="border rounded-md p-4 {{ $reply->is_internal ? 'bg-amber-50 border-amber-200' : 'bg-gray-50 border-gray-200' }}">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-2 text-sm">
                                    <span class="font-medium text-gray-800">{{ $reply->user?->name ?? __('System') }}</span>
                                    @if ($reply->is_internal)
                                        <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800">
                                            {{ __('Internal note') }}
                                        </span>
                                    @endif
                                </div>
                                <span class="text-xs text-gray-500">{{ $reply->created_at->format('Y-m-d H:i') }}</span>
                            </div>

                            <div class="text-sm text-gray-800 leading-relaxed">{!! nl2br(e($reply->body)) !!}</div>

                            @if ($reply->attachments->isNotEmpty())
                                <ul class="mt-3 space-y-1 border-t border-gray-200 pt-2">
                                    @foreach ($reply->attachments as $attachment)
                                        <li class="flex items-center justify-between text-sm">
                                            <span class="text-gray-600">
                                                {{ $attachment->original_name }}
                                                <span class="text-gray-400">({{ number_format($attachment->size / 1024, 1) }} KB)</span>
                                            </span>
                                            @can('downloadAttachment', [$ticket, $attachment])
                                                <a href="{{ route('attachments.download', $attachment) }}"
                                                   class="text-indigo-600 hover:text-indigo-900">{{ __('Download') }}</a>
                                            @endcan
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">{{ __('No replies yet.') }}</p>
                    @endforelse
                </div>

                @can('reply', $ticket)
                    <form method="POST" action="{{ route('tickets.replies.store', $ticket) }}"
                          enctype="multipart/form-data" class="mt-6 border-t border-gray-200 pt-4 space-y-4">
                        @csrf

                        <div>
                            <x-input-label for="body" :value="__('Your reply')" />
                            <textarea id="body" name="body" rows="5" required
                                      class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('body') }}</textarea>
                            <x-input-error class="mt-2" :messages="$errors->get('body')" />
                        </div>

                        @can('addInternalNote', $ticket)
                            <label class="flex items-center space-x-2 text-sm text-gray-700">
                                <input type="hidden" name="is_internal" value="0">
                                <input type="checkbox" name="is_internal" value="1" @checked(old('is_internal'))
                                       class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                <span>{{ __('Mark as internal note (staff only)') }}</span>
                            </label>
                        @endcan

                        <div>
                            <x-input-label for="reply-attachments" :value="__('Attachments')" />
                            <input id="reply-attachments" name="attachments[]" type="file" multiple
                                   class="mt-1 block w-full text-sm text-gray-600" />
                            <p class="mt-2 text-xs text-gray-500">
                                {{ __('Up to :max files, :mb MB each; allowed: :types.', [
                                    'max' => config('tickets.attachments.max_files'),
                                    'mb' => (int) ceil(config('tickets.attachments.max_kb') / 1024),
                                    'types' => implode(', ', config('tickets.attachments.mimes')),
                                ]) }}
                            </p>
                            <x-input-error class="mt-2" :messages="$errors->get('attachments')" />
                            <x-input-error class="mt-2" :messages="$errors->get('attachments.*')" />
                        </div>

                        <x-primary-button>{{ __('Send Reply') }}</x-primary-button>
                    </form>
                @endcan
            </div>

            {{-- Activity --}}
            <div class="bg-white shadow sm:rounded-lg p-6">
                <h4 class="text-sm font-semibold uppercase text-gray-400 mb-4">{{ __('Activity') }}</h4>

                @if ($activities->isEmpty())
                    <p class="text-sm text-gray-500">{{ __('No activity recorded.') }}</p>
                @else
                    <ul class="space-y-3">
                        @foreach ($activities as $activity)
                            <li class="flex items-start justify-between gap-4 text-sm">
                                <div>
                                    <span class="font-medium text-gray-800">
                                        {{ $activity->causer?->name ?? __('System') }}
                                    </span>
                                    <span class="text-gray-600">{{ $activity->description }}</span>

                                    @if ($activity->properties->has('old'))
                                        <div class="mt-1 text-xs text-gray-500">
                                            @foreach ((array) $activity->properties->get('old') as $key => $from)
                                                {{ $key }}: {{ $from }} → {{ $activity->properties->get('new', [])[$key] ?? '' }}<br>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                                <span class="whitespace-nowrap text-xs text-gray-400">
                                    {{ $activity->created_at->format('Y-m-d H:i') }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
