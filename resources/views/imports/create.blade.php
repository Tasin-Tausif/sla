<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">{{ __('Import tickets') }}</h2></x-slot>
    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="rounded-lg bg-white p-6 shadow-sm">
                <a class="text-sm text-indigo-600" href="{{ route('imports.template') }}">{{ __('Download sample template') }}</a>
                <form method="POST" action="{{ route('imports.store') }}" enctype="multipart/form-data" class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <x-input-label for="file" :value="__('CSV file')" />
                        <input id="file" name="file" type="file" accept=".csv,.txt" class="mt-1 block w-full rounded-md border-gray-300">
                        <x-input-error :messages="$errors->get('file')" class="mt-2" />
                    </div>
                    <x-primary-button>{{ __('Start import') }}</x-primary-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
