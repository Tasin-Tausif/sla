<x-app-layout>
    <x-slot name="header"><x-admin.partials.header title="{{ __('Configuration') }}" /></x-slot>
    <div class="py-8"><div class="page-shell grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @can('users.manage') <x-button href="{{ route('admin.users.index') }}" class="justify-center">{{ __('Users') }}</x-button> @endcan
        @can('roles.manage') <x-button href="{{ route('admin.roles.index') }}" class="justify-center">{{ __('Roles and permissions') }}</x-button> @endcan
        @can('categories.manage') <x-button href="{{ route('admin.categories.index') }}" class="justify-center">{{ __('Categories') }}</x-button> @endcan
        @can('sla.manage') <x-button href="{{ route('admin.sla.policies.index') }}" class="justify-center">{{ __('SLA policies') }}</x-button> @endcan
        @can('sla.manage') <x-button href="{{ route('admin.sla.business-hours.index') }}" class="justify-center">{{ __('Business hours') }}</x-button> @endcan
        @can('sla.manage') <x-button href="{{ route('admin.holidays.index') }}" class="justify-center">{{ __('Holidays') }}</x-button> @endcan
    </div></div>
</x-app-layout>
