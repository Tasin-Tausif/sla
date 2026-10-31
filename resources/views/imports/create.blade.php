<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-content">{{ __('Import tickets') }}</h2></x-slot>
    <div class="py-12">
        <div class="page-shell max-w-2xl">
            <div class="rounded-lg bg-surface p-6 shadow-sm">
                <x-button href="{{ route('imports.template') }}" variant="warning" size="sm">{{ __('Download sample template') }}</x-button>
                <form method="POST" action="{{ route('imports.store') }}" enctype="multipart/form-data" class="mt-4 space-y-4">
                    @csrf
                    <x-form.field name="file" :label="__('CSV file')" :hint="__('CSV up to :mb MB.', ['mb' => (int) ceil(config('imports.max_upload_kb') / 1024)])">
                        <x-form.file name="file" accept=".csv,.txt" />
                    </x-form.field>
                    <x-primary-button>{{ __('Start import') }}</x-primary-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
