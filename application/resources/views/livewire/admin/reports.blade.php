<div class="mx-auto w-full max-w-7xl space-y-6 overflow-x-clip">
    <div class="reveal flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="min-w-0 space-y-1.5">
            <flux:heading size="xl" class="display-type text-gradient !text-3xl">Laporan Agregat Penelitian (Bab IV)</flux:heading>
            <p class="max-w-prose text-zinc-600">{{ $period->name }} · Visualisasi &amp; Ekspor Data Hasil Penelitian</p>
        </div>
        <div class="flex flex-col gap-2 sm:flex-row">
            <a data-testid="export-xlsx" href="{{ route('admin.exports.aggregate.xlsx', $period) }}" class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white">Ekspor XLSX Agregat</a>
            <a data-testid="export-csv" href="{{ route('admin.exports.aggregate.csv', $period) }}" class="inline-flex items-center justify-center rounded-lg bg-zinc-800 px-4 py-2 text-sm font-semibold text-white">Ekspor CSV</a>
        </div>
    </div>

    <div class="bento-card space-y-3 p-5 sm:p-6">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <flux:heading size="lg">Status Run Acuan Laporan</flux:heading>
            @if($reportData->isOfficial)
                <span class="w-fit rounded-md bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-800">OFFICIAL / LOCKED (ACUAN PENELITIAN)</span>
            @elseif($reportData->selectedRun)
                <span class="w-fit rounded-md bg-sky-100 px-2.5 py-1 text-xs font-bold text-sky-800">PREVIEW TERAKHIR</span>
            @else
                <span class="w-fit rounded-md bg-zinc-100 px-2.5 py-1 text-xs font-bold text-zinc-800">BELUM ADA KALKULASI</span>
            @endif
        </div>
        @if($reportData->selectedRun)
            <dl class="grid gap-3 rounded-lg border border-zinc-200 bg-zinc-50 p-3 text-xs sm:grid-cols-2 lg:grid-cols-4">
                <div><dt class="font-semibold">Run ID</dt><dd>#{{ $reportData->selectedRun->id }}</dd></div>
                <div><dt class="font-semibold">Algoritma</dt><dd>{{ $reportData->selectedRun->algorithm_version }}</dd></div>
                <div><dt class="font-semibold">Included / Excluded</dt><dd>{{ $reportData->selectedRun->included_count }} / {{ $reportData->selectedRun->excluded_count }}</dd></div>
                <div><dt class="font-semibold">Status</dt><dd>{{ strtoupper($reportData->selectedRun->status) }}</dd></div>
                <div class="min-w-0 sm:col-span-2 lg:col-span-4"><dt class="font-semibold">Input Hash</dt><dd class="break-all font-mono">{{ $reportData->selectedRun->input_hash }}</dd></div>
            </dl>
        @endif
    </div>

    @if($reportData->selectedRun)
        <div class="bento-card space-y-4 p-5 sm:p-6">
            <div>
                <flux:heading size="lg">Mean UEQ per Modul dan Skala</flux:heading>
                <flux:text>Domain UEQ -3 sampai +3; garis tengah menunjukkan nilai nol.</flux:text>
            </div>
            <div data-chart="ueq-mean" role="img" aria-label="Grafik mean UEQ per unit dan skala pada rentang minus tiga sampai plus tiga" class="space-y-3">
                @foreach($reportData->ueqSummary as $unit)
                    @foreach($unit['scales'] as $scale => $values)
                        @php($mean = $values['mean'] === null ? 0.0 : (float) $values['mean'])
                        @php($meanPosition = min(100, max(0, (($mean + 3) / 6) * 100)))
                        <div role="img" aria-label="{{ $unit['unit_name'] }}, {{ $scale }}, mean {{ number_format($mean, 4) }}" class="space-y-1">
                            <div class="flex flex-wrap justify-between gap-2 text-xs"><span>{{ $unit['unit_code'] }} · {{ $scale }}</span><span class="font-mono">{{ number_format($mean, 4) }}</span></div>
                            <div class="relative h-3 rounded bg-zinc-100">
                                <div class="absolute inset-y-0 left-1/2 w-px bg-zinc-500"></div>
                                <div class="absolute top-0 size-3 -translate-x-1/2 rounded-full bg-indigo-600" style="left: {{ $meanPosition }}%"></div>
                            </div>
                        </div>
                    @endforeach
                @endforeach
            </div>
            <div data-report-table role="region" tabindex="0" aria-label="Tabel angka UEQ" class="overflow-x-auto rounded focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-600 focus-visible:ring-offset-2">
                <flux:heading size="sm" class="mb-2">Tabel angka UEQ</flux:heading>
                <table class="min-w-[900px] w-full text-left text-xs"><thead><tr class="border-b"><th class="p-2">Modul</th><th class="p-2">Skala</th><th class="p-2">n</th><th class="p-2">Mean</th><th class="p-2">SD</th><th class="p-2">SE</th><th class="p-2">CI bawah</th><th class="p-2">CI atas</th><th class="p-2">Alpha</th></tr></thead><tbody>
                    @foreach($reportData->ueqSummary as $unit) @foreach($unit['scales'] as $scale => $values)
                        <tr class="border-b"><td class="p-2">{{ $unit['unit_code'] }}</td><td class="p-2">{{ $scale }}</td><td class="p-2">{{ $values['n'] }}</td><td class="p-2 font-mono">{{ $values['mean'] === null ? '-' : number_format((float) $values['mean'], 4) }}</td><td class="p-2 font-mono">{{ $values['standard_deviation'] === null ? '-' : number_format((float) $values['standard_deviation'], 4) }}</td><td class="p-2 font-mono">{{ $values['standard_error'] === null ? '-' : number_format((float) $values['standard_error'], 4) }}</td><td class="p-2 font-mono">{{ $values['ci95_lower'] === null ? '-' : number_format((float) $values['ci95_lower'], 4) }}</td><td class="p-2 font-mono">{{ $values['ci95_upper'] === null ? '-' : number_format((float) $values['ci95_upper'], 4) }}</td><td class="p-2 font-mono">{{ $values['cronbach_alpha'] === null ? '-' : number_format((float) $values['cronbach_alpha'], 4) }}</td></tr>
                    @endforeach @endforeach
                </tbody></table>
            </div>
        </div>

        <div class="bento-card space-y-4 p-5 sm:p-6">
            <div><flux:heading size="lg">Gap UEQ per Skala</flux:heading><flux:text>Gap positif menunjukkan jarak mean dari benchmark Good.</flux:text></div>
            @php($maximumGap = max(1.0, (float) $reportData->ueqSummary->flatMap(fn ($unit) => collect($unit['scales'])->pluck('gap'))->filter()->max()))
            <div data-chart="gap-by-scale" role="img" aria-label="Grafik gap UEQ enam skala per unit" class="space-y-3">
                @foreach($reportData->ueqSummary as $unit) @foreach($unit['scales'] as $scale => $values)
                    @php($gap = $values['gap'] === null ? 0.0 : (float) $values['gap'])
                    <div role="img" aria-label="{{ $unit['unit_name'] }}, {{ $scale }}, gap {{ number_format($gap, 4) }}">
                        <div class="flex justify-between gap-2 text-xs"><span>{{ $unit['unit_code'] }} · {{ $scale }}</span><span class="font-mono">{{ number_format($gap, 4) }}</span></div>
                        <div class="mt-1 h-3 rounded bg-zinc-100"><div class="h-3 rounded bg-emerald-500" style="width: {{ min(100, ($gap / $maximumGap) * 100) }}%"></div></div>
                    </div>
                @endforeach @endforeach
            </div>
            <div data-report-table role="region" tabindex="0" aria-label="Tabel gap UEQ per skala" class="overflow-x-auto rounded focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-600 focus-visible:ring-offset-2">
                <table class="min-w-[520px] w-full text-left text-xs"><thead><tr class="border-b"><th class="p-2">Modul</th><th class="p-2">Skala</th><th class="p-2">Benchmark</th><th class="p-2">Mean</th><th class="p-2">Gap</th></tr></thead><tbody>
                    @foreach($reportData->ueqSummary as $unit) @foreach($unit['scales'] as $scale => $values)
                        @php($benchmark = $reportData->benchmarks->firstWhere('scale', $scale))
                        <tr class="border-b"><td class="p-2">{{ $unit['unit_code'] }}</td><td class="p-2">{{ $scale }}</td><td class="p-2 font-mono">{{ $benchmark ? number_format((float) $benchmark['good_threshold'], 4) : '-' }}</td><td class="p-2 font-mono">{{ $values['mean'] === null ? '-' : number_format((float) $values['mean'], 4) }}</td><td class="p-2 font-mono">{{ $values['gap'] === null ? '-' : number_format((float) $values['gap'], 4) }}</td></tr>
                    @endforeach @endforeach
                </tbody></table>
            </div>
        </div>

        <div class="bento-card space-y-4 p-5 sm:p-6">
            <div><flux:heading size="lg">Kontribusi Kriteria SAW</flux:heading><flux:text>Segmen C1, C2, dan C3 berjumlah sama dengan nilai preferensi Vi.</flux:text></div>
            <div data-chart="saw-contribution" role="img" aria-label="Grafik kontribusi C1 C2 C3 terhadap nilai preferensi SAW setiap unit" class="space-y-4">
                @foreach($reportData->sawRanking as $row)
                    @php($vi = max(0.000001, (float) $row['vi']))
                    <div role="img" aria-label="{{ $row['unit_name'] }}, C1 {{ number_format((float) $row['contribution_c1'], 6) }}, C2 {{ number_format((float) $row['contribution_c2'], 6) }}, C3 {{ number_format((float) $row['contribution_c3'], 6) }}, Vi {{ number_format((float) $row['vi'], 6) }}">
                        <div class="flex justify-between text-xs"><span>#{{ $row['rank'] }} · {{ $row['unit_code'] }}</span><span class="font-mono">Vi {{ number_format((float) $row['vi'], 6) }}</span></div>
                        <div class="mt-1 flex h-4 overflow-hidden rounded bg-zinc-100"><div class="bg-indigo-500" style="width: {{ ((float) $row['contribution_c1'] / $vi) * 100 }}%"></div><div class="bg-sky-500" style="width: {{ ((float) $row['contribution_c2'] / $vi) * 100 }}%"></div><div class="bg-amber-500" style="width: {{ ((float) $row['contribution_c3'] / $vi) * 100 }}%"></div></div>
                    </div>
                @endforeach
            </div>
            <div data-report-table role="region" tabindex="0" aria-label="Tabel kontribusi SAW" class="overflow-x-auto rounded focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-600 focus-visible:ring-offset-2">
                <flux:heading size="sm" class="mb-2">Tabel kontribusi SAW</flux:heading>
                <table class="min-w-[620px] w-full text-left text-xs"><thead><tr class="border-b"><th class="p-2">Rank</th><th class="p-2">Modul</th><th class="p-2">C1</th><th class="p-2">C2</th><th class="p-2">C3</th><th class="p-2">Vi</th></tr></thead><tbody>@foreach($reportData->sawRanking as $row)<tr class="border-b"><td class="p-2">#{{ $row['rank'] }}</td><td class="p-2">{{ $row['unit_code'] }}</td><td class="p-2 font-mono">{{ number_format((float) $row['contribution_c1'], 6) }}</td><td class="p-2 font-mono">{{ number_format((float) $row['contribution_c2'], 6) }}</td><td class="p-2 font-mono">{{ number_format((float) $row['contribution_c3'], 6) }}</td><td class="p-2 font-mono">{{ number_format((float) $row['vi'], 6) }}</td></tr>@endforeach</tbody></table>
            </div>
        </div>

        <div class="bento-card space-y-4 p-5 sm:p-6">
            <div>
                <flux:heading size="lg">Perubahan Peringkat Sensitivitas</flux:heading>
                <div class="mt-2 flex flex-wrap gap-2 text-xs font-bold">
                    @foreach(['S1', 'S2'] as $scenario)
                        <span @class(['rounded px-2 py-1', 'bg-emerald-100 text-emerald-800' => $reportData->sensitivityComparison->topThreeStable[$scenario], 'bg-amber-100 text-amber-800' => ! $reportData->sensitivityComparison->topThreeStable[$scenario]])>{{ $scenario }} TOP 3: {{ $reportData->sensitivityComparison->topThreeStable[$scenario] ? 'STABIL' : 'BERUBAH' }}</span>
                    @endforeach
                </div>
            </div>
            <div data-chart="rank-change" role="img" aria-label="Grafik perubahan rank S1 dan S2 dibanding S0 untuk setiap unit" class="space-y-3">
                @foreach($reportData->sensitivityMatrix as $row)
                    @php($changed = in_array($row['unit_id'], [...$reportData->sensitivityComparison->changedTopThreeUnitIds['S1'], ...$reportData->sensitivityComparison->changedTopThreeUnitIds['S2']], true))
                    <div role="img" aria-label="{{ $row['unit_name'] }}, rank S0 {{ $row['scenarios']['S0']['rank'] ?? 'tidak ada' }}, delta S1 {{ $row['scenarios']['S1']['delta_rank'] ?? 0 }}, delta S2 {{ $row['scenarios']['S2']['delta_rank'] ?? 0 }}" @class(['rounded border p-3', 'border-amber-400 bg-amber-50' => $changed, 'border-zinc-200' => ! $changed])>
                        <div class="flex flex-wrap items-center justify-between gap-2 text-xs"><span class="font-semibold">{{ $row['unit_code'] }}</span><span>S0 #{{ $row['scenarios']['S0']['rank'] ?? '-' }} · S1 Δ {{ $row['scenarios']['S1']['delta_rank'] ?? 0 }} · S2 Δ {{ $row['scenarios']['S2']['delta_rank'] ?? 0 }}</span></div>
                    </div>
                @endforeach
            </div>
            <div data-report-table role="region" tabindex="0" aria-label="Tabel perubahan peringkat sensitivitas" class="overflow-x-auto rounded focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-600 focus-visible:ring-offset-2">
                <table class="min-w-[620px] w-full text-left text-xs"><thead><tr class="border-b"><th class="p-2">Modul</th><th class="p-2">S0 rank</th><th class="p-2">S1 rank</th><th class="p-2">S1 delta</th><th class="p-2">S2 rank</th><th class="p-2">S2 delta</th></tr></thead><tbody>@foreach($reportData->sensitivityMatrix as $row)<tr class="border-b"><td class="p-2">{{ $row['unit_code'] }}</td><td class="p-2">{{ $row['scenarios']['S0']['rank'] ?? '-' }}</td><td class="p-2">{{ $row['scenarios']['S1']['rank'] ?? '-' }}</td><td class="p-2">{{ $row['scenarios']['S1']['delta_rank'] ?? 0 }}</td><td class="p-2">{{ $row['scenarios']['S2']['rank'] ?? '-' }}</td><td class="p-2">{{ $row['scenarios']['S2']['delta_rank'] ?? 0 }}</td></tr>@endforeach</tbody></table>
            </div>
        </div>

        <div class="bento-card space-y-3 p-5 sm:p-6">
            <flux:heading size="lg">Peringkat Analitis SAW vs Backlog Operasional</flux:heading>
            <div data-report-table role="region" tabindex="0" aria-label="Tabel backlog operasional" class="overflow-x-auto rounded focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-600 focus-visible:ring-offset-2">
                <table class="min-w-[760px] w-full text-left text-xs"><thead><tr class="border-b"><th class="p-2">Rank SAW</th><th class="p-2">Urutan operasional</th><th class="p-2">Modul</th><th class="p-2">Keputusan</th><th class="p-2">Alasan</th><th class="p-2">Reviewer</th></tr></thead><tbody>@foreach($reportData->operationalBacklog as $row) @php($saw = $reportData->sawRanking->firstWhere('unit_id', $row['unit_id']))<tr class="border-b"><td class="p-2">#{{ $saw['rank'] ?? '-' }}</td><td class="p-2">#{{ $row['operational_order'] }}</td><td class="p-2">{{ $row['unit_code'] }}</td><td class="p-2">{{ strtoupper($row['decision']) }}</td><td class="p-2">{{ $row['reason'] }}</td><td class="p-2">{{ $row['reviewer_name'] }}</td></tr>@endforeach</tbody></table>
            </div>
        </div>
    @else
        <div class="bento-card p-6"><flux:text class="block py-6 text-center">Belum ada data kalkulasi acuan untuk laporan.</flux:text></div>
    @endif
</div>
