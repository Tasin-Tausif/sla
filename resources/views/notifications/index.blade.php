<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-content leading-tight">{{ __('Notifications') }}</h2>
            @if (auth()->user()->unreadNotifications()->exists())
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <x-button type="submit" variant="secondary" size="sm" class="normal-case shadow-none border-0 text-sm text-primary hover:text-primary">{{ __('Mark all as read') }}</x-button>
                </form>
            @endif
        </div>
    </x-slot>

    <div class="py-12">
        <div class="page-shell">
            <div class="bg-surface shadow-sm sm:rounded-lg divide-y divide-line">
                @forelse ($notifications as $notification)
                    <form method="POST" action="{{ route('notifications.read', $notification->id) }}"
                          class="block px-6 py-4 {{ $notification->read_at === null ? 'bg-primary-soft' : '' }}">
                        @csrf
                        <x-button type="submit" variant="secondary" class="block w-full text-left normal-case shadow-none border-0">
                            <div class="font-medium text-content">{{ $notification->data['ticket_number'] ?? __('Ticket notification') }}</div>
                            <div class="text-sm text-content-muted">{{ $notification->data['message'] ?? '' }}</div>
                            <div class="text-xs text-content-muted mt-1">{{ $notification->created_at->format('d M Y, h:i A') }}</div>
                        </x-button>
                    </form>
                @empty
                    <p class="px-6 py-8 text-content-muted">{{ __('No notifications yet.') }}</p>
                @endforelse
            </div>

            <div class="mt-4">{{ $notifications->links() }}</div>
        </div>
    </div>
</x-app-layout>
