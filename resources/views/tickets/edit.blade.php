<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Ticket') }} — {{ $ticket->ticket_number }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('tickets.update', $ticket) }}" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <div>
                            <x-input-label for="subject" :value="__('Subject')" />
                            <x-text-input id="subject" name="subject" type="text" class="mt-1 block w-full"
                                          :value="old('subject', $ticket->subject)" required autofocus />
                            <x-input-error class="mt-2" :messages="$errors->get('subject')" />
                        </div>

                        <x-form.field name="category_id" :label="__('Category')" :required="true">
                            <x-form.select name="category_id" :options="$categories->pluck('name', 'id')->all()" :selected="$ticket->category_id" required />
                        </x-form.field>

                        <x-form.field name="description" :label="__('Description')" :required="true">
                            <x-form.textarea name="description" :value="$ticket->description" rows="10" required />
                        </x-form.field>

                        <div class="flex items-center space-x-4">
                            <x-primary-button>{{ __('Save Changes') }}</x-primary-button>
                            <a href="{{ route('tickets.show', $ticket) }}" class="text-sm text-gray-600 hover:text-gray-900">
                                {{ __('Cancel') }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
