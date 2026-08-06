<div class="mx-auto w-full max-w-7xl space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Laporan Agregat Penelitian (Bab IV)</flux:heading>
            <flux:text>{{ $period->name }} · Visualisasi &amp; Ekspor Data Hasil Penelitian</flux:text>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.exports.aggregate.xlsx', $period) }}" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 transition">
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Ekspor XLSX Agregat
            </a>
            <a href="{{ route('admin.exports.aggregate.csv', $period) }}" class="inline-flex items-center gap-2 rounded-lg bg-zinc-800 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-zinc-900 transition">
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Ekspor CSV
            </a>
        </div>
    </div>

    <!-- Status Metadata Run -->
    <flux:card class="space-y-2">
        <div class="flex items-center justify-between">
            <flux:heading size="lg">
                Status Run Acuan Laporan
                @if($reportData->officialRun)
                    <span class="inline-flex items-center rounded-md bg-emerald-100 px-2.5 py-0.5 text-xs font-bold text-emerald-800">OFFICIAL / LOCKED (ACUAN PENELITIAN)</span>
                @elseif($reportData->latestRun)
                    <span class="inline-flex items-center rounded-md bg-sky-100 px-2.5 py-0.5 text-xs font-bold text-sky-800">PREVIEW TERAKHIR</span>
                @else
                    <span class="inline-flex items-center rounded-md bg-zinc-100 px-2.5 py-0.5 text-xs font-bold text-zinc-800">BELUM ADA KALKULASI</span>
                @endif
            </flux:heading>
            @if($reportData->officialRun && $reportData->officialRun->lockedBy)
                <flux:text class="text-xs text-zinc-500">Dikunci oleh {{ $reportData->officialRun->lockedBy->name }} pada {{ $reportData->officialRun->official_locked_at?->format('d M Y H:i') }}</flux:text>
            @endif
        </div>
        @if($reportData->latestRun)
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-xs text-zinc-600 bg-zinc-50 p-3 rounded-lg border border-zinc-200">
                <div><span class="font-semibold text-zinc-800">Run ID:</span> #{{ $reportData->latestRun->id }}</div>
                <div><span class="font-semibold text-zinc-800">Algoritma:</span> {{ $reportData->latestRun->algorithm_version }}</div>
                <div><span class="font-semibold text-zinc-800">Respons Included:</span> {{ $reportData->latestRun->included_count }}</div>
                <div><span class="font-semibold text-zinc-800">Respons Excluded:</span> {{ $reportData->latestRun->excluded_count }}</div>
                <div class="col-span-2 md:col-span-4 font-mono text-[11px] truncate"><span class="font-semibold font-sans text-zinc-800">Input Hash:</span> {{ $reportData->latestRun->input_hash }}</div>
            </div>
        @endif
    </flux:card>

    @if($reportData->sawRanking->isNotEmpty())
        <!-- Visualisasi Ringkasan Gap UEQ per Modul -->
        <flux:card class="space-y-4">
            <div>
                <flux:heading size="lg">Visualisasi Gap UEQ per Modul (C1 Benefit)</flux:heading>
                <flux:text class="text-xs text-zinc-500">
                    Makin besar gap terhadap batas Good, makin tinggi kebutuhan perbaikan pengalaman pengguna pada modul tersebut.
                </flux:text>
            </div>
            <div class="space-y-3">
                @php
                    $maxGapVal = max(1, $reportData->sawRanking->max('x1_gap') ?: 1);
                @endphp
                @foreach($reportData->sawRanking as $row)
                    @php
                        $percentage = min(100, max(5, ($row['x1_gap'] / $maxGapVal) * 100));
                    @endphp
                    <div>
                        <div class="flex justify-between text-xs font-semibold mb-1">
                            <span>{{ $row['unit_name'] }} ({{ $row['unit_code'] }})</span>
                            <span class="font-mono text-emerald-700">Gap: {{ number_format($row['x1_gap'], 4) }}</span>
                        </div>
                        <div class="w-full bg-zinc-100 rounded-full h-3 overflow-hidden">
                            <div class="bg-emerald-500 h-3 rounded-full transition-all duration-500" style="width: {{ $percentage }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </flux:card>

        <!-- Perbandingan Peringkat SAW (S0) vs Backlog Operasional (Expert Judgment) -->
        <flux:card class="space-y-4">
            <div>
                <flux:heading size="lg">Peringkat Analitis SAW vs Backlog Operasional</flux:heading>
                <flux:text class="text-xs text-zinc-500">
                    Membandingkan hasil matematis SAW (S0) dengan urutan prioritas operasional yang telah disesuaikan oleh pertimbangan ahli.
                </flux:text>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm border-collapse">
                    <thead>
                        <tr class="border-b border-zinc-200 bg-zinc-50 text-zinc-700">
                            <th class="py-2 px-3 text-center">Rank SAW (S0)</th>
                            <th class="py-2 px-3">Modul</th>
                            <th class="py-2 px-3 text-right">Nilai Preferensi (Vi)</th>
                            <th class="py-2 px-3 text-center">Urutan Backlog Operasional</th>
                            <th class="py-2 px-3">Catatan / Alasan Penyesuaian</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200">
                        @foreach($reportData->sawRanking as $saw)
                            @php
                                $ej = $reportData->operationalBacklog->firstWhere('unit_id', $saw['unit_id']);
                            @endphp
                            <tr class="hover:bg-zinc-50/50">
                                <td class="py-2 px-3 text-center font-bold text-indigo-700">#{{ $saw['rank'] }}{{ $saw['is_tied'] ? ' (Seri)' : '' }}</td>
                                <td class="py-2 px-3 font-semibold">{{ $saw['unit_name'] }}</td>
                                <td class="py-2 px-3 text-right font-mono font-bold">{{ number_format($saw['vi'], 6) }}</td>
                                <td class="py-2 px-3 text-center font-bold">
                                    @if($ej)
                                        <span class="inline-flex items-center rounded bg-amber-100 px-2 py-0.5 text-xs text-amber-800">
                                            #{{ $ej['operational_order'] }}
                                        </span>
                                    @else
                                        <span class="text-zinc-400">#{{ $saw['rank'] }} (Sama)</span>
                                    @endif
                                </td>
                                <td class="py-2 px-3 text-xs text-zinc-600">
                                    {{ $ej['reason'] ?? 'Mengikuti hasil matematis SAW S0' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </flux:card>

        <!-- Matriks Analisis Sensitivitas S0 vs S1 vs S2 -->
        @if($reportData->sensitivityMatrix->isNotEmpty())
            <flux:card class="space-y-4">
                <div>
                    <flux:heading size="lg">Matriks Analisis Sensitivitas (S0, S1, S2)</flux:heading>
                    <flux:text class="text-xs text-zinc-500">
                        S0 (Baseline Informan) · S1 (Dominasi UX: 0.6 C1, 0.2 C2, 0.2 C3) · S2 (Dominasi Teknis: 0.2 C1, 0.4 C2, 0.4 C3)
                    </flux:text>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm border-collapse">
                        <thead>
                            <tr class="border-b border-zinc-200 bg-zinc-50 text-zinc-700">
                                <th class="py-2 px-3">Modul</th>
                                <th class="py-2 px-3 text-center">Rank S0 (Baseline)</th>
                                <th class="py-2 px-3 text-center">Rank S1 (Dominasi UX)</th>
                                <th class="py-2 px-3 text-center">Δ Rank S1</th>
                                <th class="py-2 px-3 text-center">Rank S2 (Dominasi Teknis)</th>
                                <th class="py-2 px-3 text-center">Δ Rank S2</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200">
                            @foreach($reportData->sensitivityMatrix as $row)
                                <tr class="hover:bg-zinc-50/50">
                                    <td class="py-2 px-3 font-semibold">{{ $row['unit_name'] }}</td>
                                    <td class="py-2 px-3 text-center font-bold">#{{ $row['scenarios']['S0']['rank'] ?? '-' }}</td>
                                    <td class="py-2 px-3 text-center font-mono">#{{ $row['scenarios']['S1']['rank'] ?? '-' }}</td>
                                    <td class="py-2 px-3 text-center font-mono font-bold text-xs">
                                        @php $d1 = $row['scenarios']['S1']['delta_rank'] ?? 0; @endphp
                                        @if($d1 > 0)
                                            <span class="text-emerald-600">▲ +{{ $d1 }}</span>
                                        @elseif($d1 < 0)
                                            <span class="text-rose-600">▼ {{ $d1 }}</span>
                                        @else
                                            <span class="text-zinc-400">= 0</span>
                                        @endif
                                    </td>
                                    <td class="py-2 px-3 text-center font-mono">#{{ $row['scenarios']['S2']['rank'] ?? '-' }}</td>
                                    <td class="py-2 px-3 text-center font-mono font-bold text-xs">
                                        @php $d2 = $row['scenarios']['S2']['delta_rank'] ?? 0; @endphp
                                        @if($d2 > 0)
                                            <span class="text-emerald-600">▲ +{{ $d2 }}</span>
                                        @elseif($d2 < 0)
                                            <span class="text-rose-600">▼ {{ $d2 }}</span>
                                        @else
                                            <span class="text-zinc-400">= 0</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </flux:card>
        @endif
    @else
        <flux:card>
            <flux:text class="text-center text-zinc-500 py-6">
                Belum ada data kalkulasi acuan untuk laporan. Silakan jalankan kalkulasi dan kumpulkan data informan teknis pada menu Kalkulasi.
            </flux:text>
        </flux:card>
    @endif
</div>
