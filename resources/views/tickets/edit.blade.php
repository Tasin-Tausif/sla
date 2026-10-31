<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-content leading-tight">
            {{ __('Edit Ticket') }} ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â {{ $ticket->ticket_number }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="page-shell max-w-3xl">
            <div class="bg-surface overflow-hidden shadow-sm sm:rounded-lg">
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
                            <a href="{{ route('tickets.show', $ticket) }}" class="text-sm text-content-muted hover:text-content">
                                {{ __('Cancel') }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
