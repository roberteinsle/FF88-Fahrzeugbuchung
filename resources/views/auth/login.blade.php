<x-layouts.app title="Anmelden – FF Braak Fahrzeugbuchung">
    <div class="min-h-screen flex items-center justify-center bg-gray-50 px-4">
        <div class="w-full max-w-sm">
            {{-- Logo / Header --}}
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-red-700 rounded-2xl mb-4">
                    <svg class="h-9 w-9 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10l2 2h10m0-10h3l3 3v4h-3m-3-7h2"/>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-900">FF Braak</h1>
                <p class="text-sm text-gray-500 mt-1">Fahrzeugbuchung</p>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <livewire:auth.login-form />
            </div>
        </div>
    </div>
</x-layouts.app>
