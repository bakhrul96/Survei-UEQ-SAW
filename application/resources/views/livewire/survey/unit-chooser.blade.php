<div class="mx-auto max-w-5xl space-y-6 p-4 sm:p-6">
    <header class="space-y-2">
        <h1 class="text-2xl font-semibold text-zinc-900">Pilih modul layanan</h1>
        <p class="text-zinc-600">Pilih hanya modul Wong Reang yang benar-benar pernah Anda gunakan.</p>
    </header>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($units as $unit)
            @php($completed = in_array($unit->id, $completedUnitIds, true))
            <button
                type="button"
                wire:click="choose({{ $unit->id }})"
                @disabled($completed)
                class="min-h-32 rounded-xl border p-5 text-left shadow-sm transition {{ $completed ? 'cursor-not-allowed border-zinc-200 bg-zinc-100 text-zinc-500' : 'border-indigo-200 bg-white text-zinc-900 hover:border-indigo-500 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-indigo-500' }}"
            >
                <span class="block text-lg font-semibold">{{ $unit->name }}</span>
                @if ($completed)
                    <span class="mt-3 inline-block rounded-full bg-zinc-200 px-3 py-1 text-sm font-medium">Sudah dinilai</span>
                @else
                    <span class="mt-3 block text-sm text-zinc-600">Mulai penilaian</span>
                @endif
            </button>
        @endforeach
    </div>
</div>
