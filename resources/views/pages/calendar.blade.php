<x-layouts.app title="Kalender">

    {{-- Flash messages --}}
    @if(session('success'))
    <div class="mb-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
        {{ session('success') }}
    </div>
    @endif

    {{-- Calendar component --}}
    <livewire:calendar.booking-calendar />

    {{-- BookingForm (FAB-triggered or date-click) --}}
    <livewire:booking.booking-form />

    {{-- FAB: New Booking --}}
    <button
        @click="window.Livewire?.dispatch('open-booking-form', { date: null, vehicleId: null })"
        class="fab-button w-14 h-14 rounded-full bg-blue-600 hover:bg-blue-700 text-white shadow-lg flex items-center justify-center transition-colors"
        title="Neue Buchung"
    >
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
        </svg>
    </button>

</x-layouts.app>
