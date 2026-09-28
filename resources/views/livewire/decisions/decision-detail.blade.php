<div class="space-y-4">
    <div class="flex items-center justify-between gap-2">
        <h1 class="text-xl font-bold text-gray-900">Buchungskonflikt</h1>
        @if($decision->isPending())
        <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-fw-red text-white">Entscheidung offen</span>
        @else
        <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-gray-100 text-gray-700">{{ $decision->statusLabel() }}</span>
        @endif
    </div>

    <x-booking-card :booking="$request" label="Anfrage" :highlight="$decision->isPending()">
        <div class="mt-2 rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-900">
            <span class="font-medium">Begründung:</span> {{ $decision->reason }}
        </div>
    </x-booking-card>

    @foreach($conflicts as $existing)
    <x-booking-card :booking="$existing" label="Bestehende Buchung" />
    @endforeach

    @if($decision->isPending())
    <div class="rounded-2xl border border-gray-200 bg-white p-4 space-y-3">
        <label for="note" class="block text-sm font-medium text-gray-700">Anmerkung (optional, geht an beide Seiten)</label>
        <textarea
            id="note"
            wire:model="note"
            rows="2"
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-fw-navy focus:border-transparent resize-none"
        ></textarea>
        @error('decision') <p class="text-sm text-fw-red">{{ $message }}</p> @enderror

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
            <button
                wire:click="approve"
                wire:confirm="Anfrage genehmigen? Die bestehende Buchung wird storniert."
                wire:loading.attr="disabled"
                class="py-2.5 px-4 rounded-xl bg-fw-red hover:bg-fw-red-dark text-sm font-semibold text-white transition-colors"
            >
                Anfrage genehmigen
            </button>
            <button
                wire:click="reject"
                wire:confirm="Bestehende Buchung behalten und die Anfrage ablehnen?"
                wire:loading.attr="disabled"
                class="py-2.5 px-4 rounded-xl bg-fw-navy hover:opacity-90 text-sm font-semibold text-white transition-colors"
            >
                Bestehende Buchung behalten
            </button>
        </div>
        <p class="text-xs text-gray-500">
            Genehmigen storniert die bestehende Buchung. Beide Seiten und die anderen Entscheider bekommen eine E-Mail.
        </p>
    </div>
    @else
    <div class="rounded-2xl border border-gray-200 bg-white p-4 text-sm space-y-1">
        <p class="font-semibold text-gray-900">{{ $decision->statusLabel() }}</p>
        @if($decision->decider)
        <p class="text-gray-600">
            Entschieden von {{ $decision->decider->name }}
            am {{ $decision->decided_at->setTimezone('Europe/Berlin')->isoFormat('D. MMM YYYY, HH:mm') }} Uhr
        </p>
        @elseif($decision->decided_at)
        <p class="text-gray-600">am {{ $decision->decided_at->setTimezone('Europe/Berlin')->isoFormat('D. MMM YYYY, HH:mm') }} Uhr</p>
        @endif
        @if($decision->decision_note)
        <p class="text-gray-700"><span class="font-medium">Anmerkung:</span> {{ $decision->decision_note }}</p>
        @endif
    </div>
    @endif
</div>
