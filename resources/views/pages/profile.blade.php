<x-layouts.app title="Profil">

    <div class="max-w-lg mx-auto space-y-6">

        <h1 class="text-xl font-bold text-gray-900">Mein Profil</h1>

        {{-- Flash messages --}}
        @if(session('success'))
        <div class="rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
        @endif

        {{-- Profile card --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 space-y-4">

            <div>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Name</p>
                <p class="mt-1 text-sm font-semibold text-gray-900">{{ auth()->user()->name }}</p>
            </div>

            <div>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">E-Mail</p>
                <p class="mt-1 text-sm text-gray-900">{{ auth()->user()->email }}</p>
            </div>

            @if(auth()->user()->phone)
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Telefon</p>
                <p class="mt-1 text-sm text-gray-900">{{ auth()->user()->phone }}</p>
            </div>
            @endif

            @if(auth()->user()->groups->isNotEmpty())
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Gruppen</p>
                <div class="mt-1 flex flex-wrap gap-1.5">
                    @foreach(auth()->user()->groups as $group)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        {{ $group->name }}
                    </span>
                    @endforeach
                </div>
            </div>
            @endif

            @if(auth()->user()->last_login_at)
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Letzter Login</p>
                <p class="mt-1 text-sm text-gray-500">
                    {{ auth()->user()->last_login_at->setTimezone('Europe/Berlin')->isoFormat('D. MMMM YYYY, H:mm') }} Uhr
                </p>
            </div>
            @endif

        </div>

        {{-- Logout --}}
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button
                type="submit"
                class="w-full py-2.5 px-4 rounded-xl border border-red-200 text-sm font-medium text-red-600 hover:bg-red-50 transition-colors"
            >
                Abmelden
            </button>
        </form>

    </div>

</x-layouts.app>
