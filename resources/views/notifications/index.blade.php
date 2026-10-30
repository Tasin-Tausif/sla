<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Notifications') }}</h2>
            @if (auth()->user()->unreadNotifications()->exists())
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <x-button type="submit" variant="secondary" size="sm" class="normal-case shadow-none border-0 text-sm text-indigo-600 hover:text-indigo-900">{{ __('Mark all as read') }}</x-button>
                </form>
            @endif
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg divide-y divide-gray-200">
                @forelse ($notifications as $notification)
                    <form method="POST" action="{{ route('notifications.read', $notification->id) }}"
                          class="block px-6 py-4 {{ $notification->read_at === null ? 'bg-indigo-50' : '' }}">
                        @csrf
                        <x-button type="submit" variant="secondary" class="block w-full text-left normal-case shadow-none border-0">
                            <div class="font-medium text-gray-900">{{ $notification->data['ticket_number'] ?? __('Ticket notification') }}</div>
                            <div class="text-sm text-gray-600">{{ $notification->data['message'] ?? '' }}</div>
                            <div class="text-xs text-gray-500 mt-1">{{ $notification->created_at->format('d M Y, h:i A') }}</div>
                        </x-button>
                    </form>
                @empty
                    <p class="px-6 py-8 text-gray-600">{{ __('No notifications yet.') }}</p>
                @endforelse
            </div>

            <div class="mt-4">{{ $notifications->links() }}</div>
        </div>
    </div>
</x-app-layout>
