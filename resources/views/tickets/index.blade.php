<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Tickets') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @forelse ($tickets as $ticket)
                        <p>{{ $ticket->ticket_number }} — {{ $ticket->subject }}</p>
                    @empty
                        <p>{{ __('No tickets found.') }}</p>
                    @endforelse

                    {{ $tickets->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
