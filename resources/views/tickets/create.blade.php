<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-content leading-tight">
            {{ __('Create Ticket') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="page-shell max-w-3xl">
            <div class="bg-surface overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('tickets.store') }}" enctype="multipart/form-data" class="space-y-6">
                        @csrf

                        <div>
                            <x-input-label for="subject" :value="__('Subject')" />
                            <x-text-input id="subject" name="subject" type="text" class="mt-1 block w-full"
                                          :value="old('subject')" required autofocus />
                            <x-input-error class="mt-2" :messages="$errors->get('subject')" />
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <x-form.field name="category_id" :label="__('Category')" :required="true">
                                <x-form.select name="category_id" :options="$categories->pluck('name', 'id')->all()" :selected="old('category_id')" required />
                            </x-form.field>

                            <x-form.field name="priority" :label="__('Priority')" :required="true">
                                <x-form.select name="priority" :options="\App\Enums\TicketPriority::options()" :selected="\App\Enums\TicketPriority::Medium->value" required />
                            </x-form.field>
                        </div>

                        <x-form.field name="description" :label="__('Description')" :required="true">
                            <x-form.textarea name="description" rows="8" required />
                        </x-form.field>

                        <x-form.field name="attachments" :label="__('Attachments')" :hint="__('Up to :max files, :mb MB each (:types).', [
                                    'max' => config('tickets.attachments.max_files'),
                                    'mb' => (int) ceil(config('tickets.attachments.max_kb') / 1024),
                                    'types' => implode(', ', config('tickets.attachments.mimes')),
                                ])">
                            <x-form.file name="attachments" :multiple="true" />
                        </x-form.field>

                        <div class="flex items-center space-x-4">
                            <x-primary-button>{{ __('Create Ticket') }}</x-primary-button>
                            <a href="{{ route('tickets.index') }}" class="text-sm text-content-muted hover:text-content">
                                {{ __('Cancel') }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
