<div class="mx-auto w-full max-w-6xl space-y-6">
    <header class="reveal space-y-1.5">
        <h1 class="display-type text-gradient text-3xl">Informan teknis</h1>
        <p class="max-w-prose text-zinc-600">{{ $period->name }} · Gunakan kode anonim informan, tanpa token atau identitas responden.</p>
    </header>

    @if (session('status'))
        <flux:callout variant="success" icon="check-circle">{{ session('status') }}</flux:callout>
    @endif

    <form wire:submit="save" class="space-y-6">
        <div class="bento-card space-y-4 p-5 sm:p-6">
            <flux:input wire:model="anonymousCode" label="Kode anonim informan" placeholder="Contoh: TEK-01" />
            <flux:text class="text-sm">Setiap informan wajib menilai seluruh 13 modul. Nilai tidak lengkap tidak dapat disimpan.</flux:text>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead><tr class="border-b border-zinc-200 text-xs uppercase tracking-wide text-zinc-500"><th class="p-2.5 font-semibold">Modul</th><th class="p-2.5 font-semibold">Estimasi hari</th><th class="p-2.5 font-semibold">Urgensi arsitektur (1–5)</th></tr></thead>
                    <tbody>
                        @foreach ($units as $unit)
                            <tr class="border-b border-zinc-100 transition hover:bg-indigo-50/40">
                                <td class="p-2.5"><span class="font-medium text-zinc-900">{{ $unit->name }}</span><br><span class="font-mono text-xs text-zinc-500">{{ $unit->code }}</span></td>
                                <td class="p-2"><flux:input wire:model="assessments.{{ $unit->id }}.days" type="number" min="0.01" step="0.01" label="Estimasi hari untuk {{ $unit->name }}" /></td>
                                <td class="p-2"><flux:input wire:model="assessments.{{ $unit->id }}.urgency" type="number" min="1" max="5" step="1" label="Urgensi untuk {{ $unit->name }}" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bento-card space-y-4 p-5 sm:p-6">
            <flux:heading size="lg">Alokasi bobot kriteria</flux:heading>
            <flux:text>Setiap informan memiliki tepat satu alokasi C1, C2, dan C3 dengan total 100 poin.</flux:text>
            <div class="grid gap-4 md:grid-cols-3">
                <flux:input wire:model="weights.c1" type="number" min="0" max="100" step="1" label="C1 (poin)" />
                <flux:input wire:model="weights.c2" type="number" min="0" max="100" step="1" label="C2 (poin)" />
                <flux:input wire:model="weights.c3" type="number" min="0" max="100" step="1" label="C3 (poin)" />
            </div>
            @error('weights')<flux:callout variant="danger" icon="exclamation-triangle">Total C1, C2, dan C3 harus tepat 100 poin.</flux:callout>@enderror
        </div>

        <flux:button type="submit" variant="primary">Simpan informan</flux:button>
    </form>

    <div class="bento-card space-y-4 p-5 sm:p-6">
        <flux:heading size="lg">Konsensus teknis</flux:heading>
        <div class="flex flex-wrap items-center gap-3">
            <flux:text>{{ $consensus->informantCount }} informan</flux:text>
            <flux:badge color="{{ $consensus->isComplete ? 'green' : 'amber' }}">{{ $consensus->isComplete ? 'Lengkap' : 'Belum lengkap' }}</flux:badge>
        </div>
        @if (! $consensus->isComplete)
            <flux:callout variant="warning" icon="exclamation-triangle">
                <ul class="list-disc pl-5">
                    @foreach ($consensus->incompleteReasons as $reason)
                        <li>{{ $reason }}</li>
                    @endforeach
                </ul>
            </flux:callout>
        @endif
        <div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead><tr class="border-b border-zinc-200 text-xs uppercase tracking-wide text-zinc-500"><th class="p-2.5 font-semibold">Modul</th><th class="p-2.5 text-center font-semibold">n</th><th class="p-2.5 text-right font-semibold">Rata-rata hari</th><th class="p-2.5 text-right font-semibold">SD hari</th><th class="p-2.5 text-right font-semibold">Rata-rata urgensi</th><th class="p-2.5 text-right font-semibold">SD urgensi</th></tr></thead><tbody>
            @foreach ($units as $unit)
                @php($summary = $consensus->units[$unit->id])
                <tr class="border-b border-zinc-200 dark:border-zinc-700">
                    <td class="p-2">{{ $unit->name }}</td>
                    <td class="p-2">{{ $summary->n }}</td>
                    <td class="p-2">{{ $summary->meanDays === null ? 'Belum ada' : number_format($summary->meanDays, 4) }}</td>
                    <td class="p-2">{{ $summary->standardDeviationDays === null ? 'Belum tersedia' : number_format($summary->standardDeviationDays, 4) }}</td>
                    <td class="p-2">{{ $summary->meanUrgency === null ? 'Belum ada' : number_format($summary->meanUrgency, 4) }}</td>
                    <td class="p-2">{{ $summary->standardDeviationUrgency === null ? 'Belum tersedia' : number_format($summary->standardDeviationUrgency, 4) }}</td>
                </tr>
            @endforeach
        </tbody></table></div>
        <dl class="grid gap-3 text-sm md:grid-cols-3">
            @foreach ($consensus->weights as $criterion => $weight)
                <div><dt class="font-medium">{{ strtoupper($criterion) }}</dt><dd>{{ $weight === null ? 'Belum ada' : number_format($weight, 4) }}</dd></div>
            @endforeach
        </dl>
    </div>
</div>
