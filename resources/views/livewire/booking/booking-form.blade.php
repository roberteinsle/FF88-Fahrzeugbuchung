<div>
    {{-- Slide-over panel backdrop + panel --}}
    @if($show)
    <div class="fixed inset-0 z-[60] flex justify-end">
        {{-- Backdrop --}}
        <div
            class="absolute inset-0 bg-black/40"
            wire:click="close"
        ></div>

        {{-- Panel --}}
        <div class="relative z-50 w-full max-w-md bg-white shadow-xl flex flex-col h-full overflow-y-auto">

            {{-- Header --}}
            <div class="flex items-center justify-between px-4 py-4 border-b border-gray-200 sticky top-0 bg-white z-10">
                <h2 class="text-lg font-semibold text-gray-900">
                    {{ $bookingId ? 'Buchung bearbeiten' : 'Neue Buchung' }}
                </h2>
                <button wire:click="close" class="p-1 rounded-full text-gray-400 hover:text-gray-600 hover:bg-gray-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Form --}}
            <form wire:submit="save" class="flex-1 flex flex-col px-4 py-5 gap-5">

                {{-- Fahrzeug --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fahrzeug *</label>
                    <div class="flex flex-wrap gap-2">
                        @foreach($vehicles as $vehicle)
                        <button
                            type="button"
                            wire:click="$set('vehicleId', {{ $vehicle->id }})"
                            class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-medium border-2 transition-all"
                            @style([
                                "border-color: {$vehicle->color}; background-color: {$vehicle->color}; color: white" => $vehicleId === $vehicle->id,
                                "border-color: {$vehicle->color}; color: #374151; background: white" => $vehicleId !== $vehicle->id,
                            ])
                        >
                            {{ $vehicle->short_name ?? $vehicle->name }}
                        </button>
                        @endforeach
                    </div>
                    @error('vehicleId') <p class="mt-1 text-sm text-fw-red">{{ $message }}</p> @enderror
                </div>

                {{-- Availability notice --}}
                @if(!$available && $conflict)
                <div class="rounded-lg bg-red-50 border border-red-200 p-3 text-sm text-red-800">
                    <p class="font-semibold">Fahrzeug bereits gebucht</p>
                    <p class="mt-0.5 text-fw-red">
                        Von {{ $conflict['userName'] }} ({{ $conflict['startsAt'] }} – {{ $conflict['endsAt'] }})
                        @if($conflict['purpose']) · {{ Str::limit($conflict['purpose'], 40) }} @endif
                    </p>
                    @if(count($alternatives) > 0)
                    <p class="mt-2 font-medium text-red-800">Verfügbare Alternativen:</p>
                    <div class="flex flex-wrap gap-1.5 mt-1">
                        @foreach($alternatives as $alt)
                        <button
                            type="button"
                            wire:click="$set('vehicleId', {{ $alt['id'] }})"
                            class="flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium text-white"
                            style="background-color: {{ $alt['color'] }}"
                        >
                            {{ $alt['name'] }}
                        </button>
                        @endforeach
                    </div>
                    @endif
                </div>
                @endif

                {{-- Zeitraum --}}
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="starts_at" class="block text-sm font-medium text-gray-700 mb-1">Von *</label>
                        <input
                            id="starts_at"
                            type="datetime-local"
                            wire:model.live.debounce.500ms="startsAt"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-fw-navy focus:border-transparent"
                        />
                        @error('startsAt') <p class="mt-1 text-sm text-fw-red">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="ends_at" class="block text-sm font-medium text-gray-700 mb-1">Bis *</label>
                        <input
                            id="ends_at"
                            type="datetime-local"
                            wire:model.live.debounce.500ms="endsAt"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-fw-navy focus:border-transparent"
                        />
                        @error('endsAt') <p class="mt-1 text-sm text-fw-red">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Zweck --}}
                <div>
                    <label for="purpose" class="block text-sm font-medium text-gray-700 mb-1">Zweck *</label>
                    <input
                        id="purpose"
                        type="text"
                        wire:model="purpose"
                        placeholder="z. B. Jugendfeuerwehr Übung"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-fw-navy focus:border-transparent"
                    />
                    @error('purpose') <p class="mt-1 text-sm text-fw-red">{{ $message }}</p> @enderror
                </div>

                {{-- Ziel --}}
                <div>
                    <label for="destination" class="block text-sm font-medium text-gray-700 mb-1">Ziel / Ort</label>
                    <input
                        id="destination"
                        type="text"
                        wire:model="destination"
                        placeholder="z. B. Feuerwehrhaus Braak"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-fw-navy focus:border-transparent"
                    />
                </div>

                {{-- Gruppe --}}
                @if($groups->isNotEmpty())
                <div>
                    <label for="group_id" class="block text-sm font-medium text-gray-700 mb-1">Gruppe (optional)</label>
                    <select
                        id="group_id"
                        wire:model="groupId"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-fw-navy focus:border-transparent"
                    >
                        <option value="">– keine –</option>
                        @foreach($groups as $group)
                        <option value="{{ $group->id }}">{{ $group->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                {{-- Notizen --}}
                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Notizen</label>
                    <textarea
                        id="notes"
                        wire:model="notes"
                        rows="3"
                        placeholder="Weitere Infos für Mitglieder …"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-fw-navy focus:border-transparent resize-none"
                    ></textarea>
                    @error('notes') <p class="mt-1 text-sm text-fw-red">{{ $message }}</p> @enderror
                </div>

                {{-- Submit --}}
                <div class="mt-auto pt-3 border-t border-gray-100">
                    <button
                        type="submit"
                        class="w-full py-2.5 px-4 rounded-xl font-semibold text-white text-sm transition-colors
                               {{ $available ? 'bg-fw-red hover:bg-fw-red-dark' : 'bg-gray-400 cursor-not-allowed' }}"
                        @disabled(!$available && $conflict !== null)
                    >
                        <span wire:loading.remove wire:target="save">
                            {{ $bookingId ? 'Speichern' : 'Buchen' }}
                        </span>
                        <span wire:loading wire:target="save">Wird gespeichert …</span>
                    </button>
                </div>

            </form>
        </div>
    </div>
    @endif
</div>
