<!DOCTYPE html>
<html lang="de" class="h-full bg-gray-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'FF Braak Fahrzeugbuchung' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full">
    <div class="min-h-full flex flex-col">
        {{-- Top Header (only on desktop) --}}
        <header class="hidden sm:block bg-fw-red text-white shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-14">
                    <div class="flex items-center gap-3">
                        <img src="{{ asset('logo.svg') }}" alt="FF Braak" class="h-8 w-auto">
                        <span class="font-semibold text-sm">FF Braak Fahrzeugbuchung</span>
                    </div>
                    @auth
                    <nav class="flex items-center gap-6 text-sm">
                        <a href="{{ route('calendar') }}" class="hover:text-red-100 {{ request()->routeIs('calendar') ? 'font-semibold' : '' }}">Kalender</a>
                        <a href="{{ route('my-bookings') }}" class="hover:text-red-100 {{ request()->routeIs('my-bookings') ? 'font-semibold' : '' }}">Meine Buchungen</a>
                        <a href="{{ route('profile') }}" class="hover:text-red-100 {{ request()->routeIs('profile') ? 'font-semibold' : '' }}">Profil</a>
                        @if(auth()->user()->is_admin)
                        <a href="{{ route('filament.admin.pages.dashboard') }}" class="hover:text-red-100">Admin</a>
                        @endif
                    </nav>
                    @endauth
                </div>
            </div>
        </header>

        {{-- Main content --}}
        <main class="flex-1 pb-20 sm:pb-0 px-4 sm:px-6 lg:px-8 py-4 max-w-7xl mx-auto w-full">
            {{ $slot }}
        </main>

        {{-- Mobile bottom tab bar --}}
        @auth
        <nav class="sm:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 z-40">
            <div class="grid grid-cols-3 h-16">
                <a href="{{ route('calendar') }}"
                   class="flex flex-col items-center justify-center gap-1 text-xs {{ request()->routeIs('calendar') ? 'text-fw-red' : 'text-gray-500' }}">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    Kalender
                </a>
                <a href="{{ route('my-bookings') }}"
                   class="flex flex-col items-center justify-center gap-1 text-xs {{ request()->routeIs('my-bookings') ? 'text-fw-red' : 'text-gray-500' }}">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    Meine
                </a>
                <a href="{{ route('profile') }}"
                   class="flex flex-col items-center justify-center gap-1 text-xs {{ request()->routeIs('profile') ? 'text-fw-red' : 'text-gray-500' }}">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    Profil
                </a>
            </div>
        </nav>
        @endauth
    </div>
    @livewireScripts
</body>
</html>
