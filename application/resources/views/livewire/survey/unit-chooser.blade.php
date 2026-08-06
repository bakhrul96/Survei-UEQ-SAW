<div class="space-y-6">
    @php($doneCount = count($completedUnitIds))
    @php($totalCount = $units->count())
    @php($pct = $totalCount > 0 ? (int) round(($doneCount / $totalCount) * 100) : 0)

    <header class="reveal space-y-3">
        <div class="space-y-2">
            <h1 class="display-type text-gradient text-3xl">Pilih Modul</h1>
            <p class="max-w-prose text-zinc-600">Pilih hanya modul Wong Reang yang benar-benar pernah Anda gunakan.</p>
        </div>

        <div class="space-y-1.5">
            <div class="flex items-center justify-between text-sm">
                <span class="font-medium text-zinc-700">{{ $doneCount }} dari {{ $totalCount }} modul dinilai</span>
                <span class="font-semibold text-indigo-700">{{ $pct }}%</span>
            </div>
            <div class="h-2 overflow-hidden rounded-full bg-zinc-200" role="progressbar" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100" aria-label="Progres penilaian modul">
                <div class="h-full rounded-full bg-gradient-to-r from-indigo-600 to-violet-500 transition-all duration-500" style="width: {{ $pct }}%"></div>
            </div>
        </div>
    </header>

    <div class="grid gap-3 sm:grid-cols-2">
        @foreach ($units as $unit)
            @php($completed = in_array($unit->id, $completedUnitIds, true))
            <button
                type="button"
                wire:click="choose({{ $unit->id }})"
                @disabled($completed)
                class="bento-card focus-ring group min-h-28 p-5 text-left {{ $completed ? 'cursor-not-allowed opacity-60' : 'hover:border-indigo-400' }}"
            >
                <span class="flex items-start justify-between gap-3">
                    <span class="text-lg font-semibold text-zinc-900">{{ $unit->name }}</span>
                    @if ($completed)
                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M12.416 3.376a.75.75 0 0 1 .208 1.04l-5 7.5a.75.75 0 0 1-1.154.114l-3-3a.75.75 0 0 1 1.06-1.06l2.353 2.353 4.493-6.74a.75.75 0 0 1 1.04-.207Z" clip-rule="evenodd"/></svg>
                            Sudah dinilai
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700 transition group-hover:bg-indigo-100">
                            Mulai
                            <svg class="h-3.5 w-3.5 transition-transform group-hover:translate-x-0.5" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M2 8a.75.75 0 0 1 .75-.75h8.69L8.22 4.03a.75.75 0 1 1 1.06-1.06l4.5 4.5a.75.75 0 0 1 0 1.06l-4.5 4.5a.75.75 0 0 1-1.06-1.06l3.22-3.22H2.75A.75.75 0 0 1 2 8Z" clip-rule="evenodd"/></svg>
                        </span>
                    @endif
                </span>
                <span class="mt-2 block text-sm text-zinc-500">{{ $completed ? 'Terima kasih, modul ini sudah Anda nilai.' : 'Ketuk untuk mulai menilai modul ini.' }}</span>
            </button>
        @endforeach
    </div>
</div>