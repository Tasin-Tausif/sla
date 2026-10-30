<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Create Ticket') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
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
                            <div>
                                <x-input-label for="category_id" :value="__('Category')" />
                                <select id="category_id" name="category_id"
                                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}" @selected((int) old('category_id') === $category->id)>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error class="mt-2" :messages="$errors->get('category_id')" />
                            </div>

                            <div>
                                <x-input-label for="priority" :value="__('Priority')" />
                                <select id="priority" name="priority"
                                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    @foreach (App\Enums\TicketPriority::cases() as $priority)
                                        <option value="{{ $priority->value }}" @selected(old('priority', App\Enums\TicketPriority::Medium->value) === $priority->value)>
                                            {{ $priority->label() }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error class="mt-2" :messages="$errors->get('priority')" />
                            </div>
                        </div>

                        <div>
                            <x-input-label for="description" :value="__('Description')" />
                            <textarea id="description" name="description" rows="8" required
                                      class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('description') }}</textarea>
                            <x-input-error class="mt-2" :messages="$errors->get('description')" />
                        </div>

                        <div>
                            <x-input-label for="attachments" :value="__('Attachments')" />
                            <input id="attachments" name="attachments[]" type="file" multiple
                                   class="mt-1 block w-full text-sm text-gray-600" />
                            <p class="mt-2 text-xs text-gray-500">
                                {{ __('Up to :max files, :mb MB each (:types).', [
                                    'max' => config('tickets.attachments.max_files'),
                                    'mb' => (int) ceil(config('tickets.attachments.max_kb') / 1024),
                                    'types' => implode(', ', config('tickets.attachments.mimes')),
                                ]) }}
                            </p>
                            <x-input-error class="mt-2" :messages="$errors->get('attachments')" />
                            <x-input-error class="mt-2" :messages="$errors->get('attachments.*')" />
                        </div>

                        <div class="flex items-center space-x-4">
                            <x-primary-button>{{ __('Create Ticket') }}</x-primary-button>
                            <a href="{{ route('tickets.index') }}" class="text-sm text-gray-600 hover:text-gray-900">
                                {{ __('Cancel') }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
