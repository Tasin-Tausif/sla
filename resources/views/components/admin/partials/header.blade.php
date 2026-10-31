<div class="page-shell space-y-2">
    <h1 class="text-2xl font-semibold text-content">{{ $title }}</h1>
    <nav class="flex flex-wrap gap-3 text-sm text-content-muted">
        {{-- @can('users.manage') <a class="hover:text-primary" href="{{ route('admin.users.index') }}">{{ __('Users') }}</a> @endcan
        @can('roles.manage') <a class="hover:text-primary" href="{{ route('admin.roles.index') }}">{{ __('Roles') }}</a> @endcan
        @can('categories.manage') <a class="hover:text-primary" href="{{ route('admin.categories.index') }}">{{ __('Categories') }}</a> @endcan
        @can('sla.manage') <a class="hover:text-primary" href="{{ route('admin.sla.policies.index') }}">{{ __('SLA') }}</a> @endcan --}}
    </nav>
</div>
