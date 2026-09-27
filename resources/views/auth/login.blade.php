<x-layouts.app title="Anmelden – FF Braak Fahrzeugbuchung">
    <div class="min-h-screen flex items-center justify-center bg-gray-50 px-4">
        <div class="w-full max-w-sm">
            {{-- Logo / Header --}}
            <div class="text-center mb-8">
                <img src="{{ asset('logo.svg') }}" alt="FF Braak Logo" class="h-24 w-auto mx-auto mb-4">
                <h1 class="text-2xl font-bold text-fw-grey">FF Braak</h1>
                <p class="text-sm text-gray-500 mt-1">Fahrzeugbuchung</p>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <livewire:auth.login-form />
            </div>
        </div>
    </div>
</x-layouts.app>
