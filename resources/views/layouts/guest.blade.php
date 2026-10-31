<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'Laravel') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-bg font-sans text-content antialiased">
        <div class="min-h-screen lg:grid lg:grid-cols-2">
            <aside class="relative hidden overflow-hidden bg-brand p-8 text-on-primary lg:flex lg:flex-col lg:justify-between xl:p-12">
                <div class="relative z-10">
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-3 text-lg font-semibold">
                        <svg class="h-10 w-10" viewBox="0 0 48 48" fill="none" aria-hidden="true">
                            <path d="M24 4 41 14v20L24 44 7 34V14L24 4Z" stroke="currentColor" stroke-width="3"/>
                            <path d="m15 19 9 5 9-5M24 24v11" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                        </svg>
                        <span>{{ config('app.name') }}</span>
                    </a>
                </div>
                <div class="relative z-10 max-w-lg">
                    <h1 class="text-4xl font-semibold leading-tight xl:text-5xl">{{ __('Support tickets that never miss their SLA') }}</h1>
                    <p class="mt-5 max-w-md text-base text-on-primary/80">{{ __('Keep every request moving with clear ownership, timely escalation, and a complete support history.') }}</p>
                    <ul class="mt-8 space-y-4 text-sm">
                        @foreach ([
                            'Smart SLA tracking with business hours',
                            'Automatic assignment and escalation',
                            'Private internal notes for your team',
                        ] as $feature)
                            <li class="flex items-center gap-3">
                                <svg class="h-5 w-5 shrink-0 text-on-primary/80" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                    <circle cx="10" cy="10" r="8" stroke="currentColor" stroke-width="1.5"/>
                                    <path d="m6.5 10 2.25 2.25L13.5 7.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <span>{{ __($feature) }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
                <div class="absolute -bottom-24 -right-16 h-72 w-72 rounded-full border border-on-primary/10"></div>
                <div class="absolute -right-24 top-24 h-48 w-48 rounded-full border border-on-primary/10"></div>
            </aside>

            <main class="flex min-h-screen flex-col items-center px-[var(--page-padding)] py-6 sm:py-10 lg:justify-center lg:px-8">
                <div class="mb-8 lg:hidden">
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-2 text-lg font-semibold">
                        <svg class="h-9 w-9 text-primary" viewBox="0 0 48 48" fill="none" aria-hidden="true">
                            <path d="M24 4 41 14v20L24 44 7 34V14L24 4Z" stroke="currentColor" stroke-width="3"/>
                            <path d="m15 19 9 5 9-5M24 24v11" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                        </svg>
                        <span>{{ config('app.name') }}</span>
                    </a>
                </div>

                <div class="w-full max-w-md rounded-lg bg-surface p-6 shadow-card sm:p-8">
                    @isset($title)
                        <h2 class="text-2xl font-semibold tracking-tight text-content">{{ $title }}</h2>
                    @endisset
                    @isset($subtitle)
                        <p class="mt-2 text-sm text-content-muted">{{ $subtitle }}</p>
                    @endisset
                    <div class="mt-8">
                        {{ $slot }}
                    </div>
                </div>

                @isset($footer)
                    <div class="mt-6 w-full max-w-md text-center text-sm text-content-muted">
                        {{ $footer }}
                    </div>
                @endisset
            </main>
        </div>
    </body>
</html>
