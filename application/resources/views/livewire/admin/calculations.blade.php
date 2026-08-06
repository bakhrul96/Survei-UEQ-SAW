<div class="mx-auto w-full max-w-7xl space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Kalkulasi UEQ dan SAW</flux:heading>
            <flux:text>{{ $period->name }} · Analisis Sensitivitas &amp; Penguncian Hasil Resmi</flux:text>
        </div>
        <div class="flex items-center gap-3">
            <flux:button wire:click="runPreview" variant="primary">Jalankan preview</flux:button>
            @if($run && $run->status !== 'official')
                <flux:button wire:click="lockOfficial" variant="filled" color="teal">Kunci Hasil Resmi (Official)</flux:button>
            @endif
        </div>
    </div>

    @if(session('status'))
        <flux:callout variant="success">{{ session('status') }}</flux:callout>
    @endif
    @if(session('error'))
        <flux:callout variant="danger">{{ session('error') }}</flux:callout>
    @endif

    @if($run)
        <flux:card class="space-y-2">
            <div class="flex items-center justify-between">
                <flux:heading size="lg">
                    Calculation Run #{{ $run->id }}
                    @if($run->status === 'official')
                        <span class="inline-flex items-center rounded-md bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-800">OFFICIAL / LOCKED</span>
                    @elseif($run->status === 'stale')
                        <span class="inline-flex items-center rounded-md bg-rose-100 px-2 py-1 text-xs font-semibold text-rose-800">STALE</span>
                    @else
                        <span class="inline-flex items-center rounded-md bg-sky-100 px-2 py-1 text-xs font-semibold text-sky-800">PREVIEW</span>
                    @endif
                </flux:heading>
                @if($run->status === 'official' && $run->lockedBy)
                    <flux:text class="text-xs text-zinc-500">Dikunci oleh {{ $run->lockedBy->name }} pada {{ $run->official_locked_at?->format('d M Y H:i') }}</flux:text>
                @endif
            </div>
            <dl class="grid gap-3 text-xs md:grid-cols-2 lg:grid-cols-4">
                <div><dt class="font-medium text-zinc-500">ID run</dt><dd>#{{ $run->id }}</dd></div>
                <div><dt class="font-medium text-zinc-500">Versi algoritma</dt><dd>{{ $run->algorithm_version }}</dd></div>
                <div><dt class="font-medium text-zinc-500">Status</dt><dd>{{ strtoupper($run->status) }}</dd></div>
                <div><dt class="font-medium text-zinc-500">Dibuat oleh</dt><dd>{{ $run->creator->name }}</dd></div>
                <div><dt class="font-medium text-zinc-500">Waktu kalkulasi</dt><dd>{{ $run->calculated_at?->format('d M Y H:i:s') }}</dd></div>
                <div><dt class="font-medium text-zinc-500">Included / Excluded</dt><dd>{{ $run->included_count }} / {{ $run->excluded_count }}</dd></div>
                <div class="md:col-span-2"><dt class="font-medium text-zinc-500">Input hash</dt><dd><code class="break-all rounded bg-zinc-100 px-1 py-0.5 font-mono text-zinc-800">{{ $run->input_hash }}</code></dd></div>
            </dl>
            @foreach($run->warnings as $warning)
                <flux:text class="text-amber-700 text-xs font-medium">⚠️ {{ $warning }}</flux:text>
            @endforeach
        </flux:card>

        <!-- Hasil UEQ -->
        <flux:card class="space-y-4">
            <flux:heading size="lg">Hasil UEQ per Modul &amp; Skala</flux:heading>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm border-collapse">
                    <thead>
                        <tr class="border-b border-zinc-200 bg-zinc-50 text-zinc-700">
                            <th class="py-2 px-3">Modul</th>
                            <th class="py-2 px-3">Skala</th>
                            <th class="py-2 px-3 text-center">n</th>
                            <th class="py-2 px-3 text-right">Mean</th>
                            <th class="py-2 px-3 text-right">SD</th>
                            <th class="py-2 px-3 text-right">SE</th>
                            <th class="py-2 px-3 text-right">CI 95% bawah</th>
                            <th class="py-2 px-3 text-right">CI 95% atas</th>
                            <th class="py-2 px-3 text-right">Alpha</th>
                            <th class="py-2 px-3">Warning reliabilitas</th>
                            <th class="py-2 px-3 text-right">Batas Good</th>
                            <th class="py-2 px-3 text-right">Gap</th>
                            <th class="py-2 px-3">Ketidaktersediaan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200">
                        @foreach($run->ueqResults as $row)
                            <tr class="hover:bg-zinc-50/50">
                                <td class="py-2 px-3 font-medium">{{ $row->unit->name }}</td>
                                <td class="py-2 px-3">{{ $row->scale }}</td>
                                <td class="py-2 px-3 text-center">{{ $row->n }}</td>
                                <td class="py-2 px-3 text-right font-mono">{{ $row->mean !== null ? number_format($row->mean, 4) : '-' }}</td>
                                <td class="py-2 px-3 text-right font-mono">{{ $row->standard_deviation !== null ? number_format($row->standard_deviation, 4) : '-' }}</td>
                                <td class="py-2 px-3 text-right font-mono">{{ $row->standard_error !== null ? number_format($row->standard_error, 4) : '-' }}</td>
                                <td class="py-2 px-3 text-right font-mono">{{ $row->ci95_lower !== null ? number_format($row->ci95_lower, 4) : '-' }}</td>
                                <td class="py-2 px-3 text-right font-mono">{{ $row->ci95_upper !== null ? number_format($row->ci95_upper, 4) : '-' }}</td>
                                <td class="py-2 px-3 text-right font-mono">{{ $row->cronbach_alpha !== null ? number_format($row->cronbach_alpha, 4) : '-' }}</td>
                                <td class="py-2 px-3 text-xs">
                                    {{ collect($row->reliability_warnings)->join(', ') ?: ($row->reliability_unavailable_reason ?? 'Tidak ada') }}
                                </td>
                                <td class="py-2 px-3 text-right font-mono">{{ isset($benchmarkByScale[$row->scale]) ? number_format((float) $benchmarkByScale[$row->scale], 4) : '-' }}</td>
                                <td class="py-2 px-3 text-right font-mono text-emerald-700 font-semibold">{{ $row->gap !== null ? number_format($row->gap, 4) : '-' }}</td>
                                <td class="py-2 px-3 text-xs text-zinc-500">{{ $row->unavailable_reason ?? 'Deskriptif tersedia' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </flux:card>

        <flux:card class="space-y-4">
            <div>
                <flux:heading size="lg">Pooled reliability</flux:heading>
                <flux:text class="text-xs text-zinc-500">Diagnostik pooled lintas modul; bukan pengganti reliabilitas per modul.</flux:text>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm border-collapse">
                    <thead><tr class="border-b border-zinc-200 bg-zinc-50 text-zinc-700"><th class="py-2 px-3">Skala</th><th class="py-2 px-3">Scope</th><th class="py-2 px-3 text-center">Pooled n</th><th class="py-2 px-3 text-right">Alpha</th><th class="py-2 px-3">Warning</th><th class="py-2 px-3">Ketidaktersediaan</th></tr></thead>
                    <tbody class="divide-y divide-zinc-200">
                        @foreach($run->ueqPooledResults as $row)
                            <tr><td class="py-2 px-3">{{ $row->scale }}</td><td class="py-2 px-3">{{ $row->scope }}</td><td class="py-2 px-3 text-center">{{ $row->n }}</td><td class="py-2 px-3 text-right font-mono">{{ $row->cronbach_alpha !== null ? number_format($row->cronbach_alpha, 4) : '-' }}</td><td class="py-2 px-3 text-xs">{{ collect($row->warnings)->join(', ') ?: 'Tidak ada' }}</td><td class="py-2 px-3 text-xs">{{ $row->unavailable_reason ?? 'Tersedia' }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </flux:card>

        <!-- Peringkat SAW -->
        <flux:card class="space-y-4">
            <flux:heading size="lg">Peringkat SAW (S0 Baseline Informan)</flux:heading>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm border-collapse">
                    <thead>
                        <tr class="border-b border-zinc-200 bg-zinc-50 text-zinc-700">
                            <th class="py-2 px-3">Rank</th>
                            <th class="py-2 px-3">Modul</th>
                            <th class="py-2 px-3 text-right">X1 gap</th>
                            <th class="py-2 px-3 text-right">X2 hari</th>
                            <th class="py-2 px-3 text-right">X3 urgensi</th>
                            <th class="py-2 px-3 text-right">R1</th>
                            <th class="py-2 px-3 text-right">R2</th>
                            <th class="py-2 px-3 text-right">R3</th>
                            <th class="py-2 px-3 text-right">Kontribusi C1</th>
                            <th class="py-2 px-3 text-right">Kontribusi C2</th>
                            <th class="py-2 px-3 text-right">Kontribusi C3</th>
                            <th class="py-2 px-3 text-right">Vi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200">
                        @forelse($run->sawResults as $row)
                            <tr class="hover:bg-zinc-50/50">
                                <td class="py-2 px-3 font-bold text-center">
                                    #{{ $row->rank }}{{ $row->is_tied ? ' (Seri)' : '' }}
                                </td>
                                <td class="py-2 px-3 font-semibold">{{ $row->unit->name }}</td>
                                <td class="py-2 px-3 text-right font-mono">{{ number_format($row->x1_gap, 4) }}</td>
                                <td class="py-2 px-3 text-right font-mono">{{ number_format($row->x2_days, 2) }}</td>
                                <td class="py-2 px-3 text-right font-mono">{{ number_format($row->x3_urgency, 2) }}</td>
                                <td class="py-2 px-3 text-right font-mono">{{ number_format($row->r1, 4) }}</td>
                                <td class="py-2 px-3 text-right font-mono">{{ number_format($row->r2, 4) }}</td>
                                <td class="py-2 px-3 text-right font-mono">{{ number_format($row->r3, 4) }}</td>
                                <td class="py-2 px-3 text-right font-mono">{{ number_format($row->contribution_c1, 6) }}</td>
                                <td class="py-2 px-3 text-right font-mono">{{ number_format($row->contribution_c2, 6) }}</td>
                                <td class="py-2 px-3 text-right font-mono">{{ number_format($row->contribution_c3, 6) }}</td>
                                <td class="py-2 px-3 text-right font-mono font-bold text-indigo-600">{{ number_format($row->preference_value, 6) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="py-3 px-3 text-center text-zinc-500">Belum ada peringkat SAW. Penuhi data informan teknis terlebih dahulu.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </flux:card>

        <!-- Analisis Sensitivitas -->
        @if($sensitivityGrid !== [])
            <flux:card class="space-y-4">
                <div>
                    <flux:heading size="lg">Analisis Sensitivitas Peringkat (S0 vs S1 vs S2)</flux:heading>
                    <flux:text class="text-xs text-zinc-500">
                        S0 = Baseline Informan · S1 = Dominasi UX (0.6 C1, 0.2 C2, 0.2 C3) · S2 = Dominasi Pertimbangan Teknis (0.2 C1, 0.4 C2, 0.4 C3)
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
                            @foreach($sensitivityGrid as $unitId => $data)
                                <tr class="hover:bg-zinc-50/50">
                                    <td class="py-2 px-3 font-semibold">{{ $data['name'] }}</td>
                                    <td class="py-2 px-3 text-center font-bold">#{{ $data['S0']['rank'] ?? '-' }}</td>
                                    <td class="py-2 px-3 text-center font-mono">#{{ $data['S1']['rank'] ?? '-' }}</td>
                                    <td class="py-2 px-3 text-center font-mono font-bold text-xs">
                                        @php $d1 = $data['S1']['deltaRank'] ?? 0; @endphp
                                        @if($d1 > 0)
                                            <span class="text-emerald-600">▲ +{{ $d1 }}</span>
                                        @elseif($d1 < 0)
                                            <span class="text-rose-600">▼ {{ $d1 }}</span>
                                        @else
                                            <span class="text-zinc-400">= 0</span>
                                        @endif
                                    </td>
                                    <td class="py-2 px-3 text-center font-mono">#{{ $data['S2']['rank'] ?? '-' }}</td>
                                    <td class="py-2 px-3 text-center font-mono font-bold text-xs">
                                        @php $d2 = $data['S2']['deltaRank'] ?? 0; @endphp
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

        <!-- Expert Judgment & Backlog Operasional -->
        <flux:card class="space-y-4">
            <div>
                <flux:heading size="lg">Expert Judgment &amp; Backlog Operasional</flux:heading>
                <flux:text class="text-xs text-zinc-500">
                    Mencatat keputusan ahli untuk menyesuaikan urutan prioritas operasional tanpa mengubah nilai matematis SAW.
                </flux:text>
            </div>

            <!-- Form Penambahan Expert Judgment -->
            <form wire:submit.prevent="saveExpertJudgment" class="grid grid-cols-1 md:grid-cols-4 gap-3 bg-zinc-50 p-3 rounded-lg border border-zinc-200">
                <div>
                    <label class="block text-xs font-semibold text-zinc-700 mb-1">Pilih Modul</label>
                    <select wire:model="selectedUnitId" class="w-full rounded-md border-zinc-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">-- Pilih Modul --</option>
                        @foreach($allUnits as $unit)
                            <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-zinc-700 mb-1">Urutan Operasional (1-13)</label>
                    <input type="number" wire:model="operationalOrder" min="1" max="13" class="w-full rounded-md border-zinc-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-zinc-700 mb-1">Alasan Penyesuaian Expert Judgment</label>
                    <div class="flex gap-2">
                        <input type="text" wire:model="expertReason" placeholder="Contoh: Kebutuhan regulasi mendesak..." class="w-full rounded-md border-zinc-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <flux:button type="submit" variant="primary" size="sm">Simpan</flux:button>
                    </div>
                </div>
            </form>

            <!-- Tabel Daftar Expert Judgment -->
            @if($run->expertJudgments->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm border-collapse">
                        <thead>
                            <tr class="border-b border-zinc-200 bg-zinc-50 text-zinc-700">
                                <th class="py-2 px-3 text-center">Urutan Backlog</th>
                                <th class="py-2 px-3">Modul</th>
                                <th class="py-2 px-3">Keputusan</th>
                                <th class="py-2 px-3">Alasan Expert Judgment</th>
                                <th class="py-2 px-3">Reviewer</th>
                                <th class="py-2 px-3">Waktu</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200">
                            @foreach($run->expertJudgments->sortBy('operational_order') as $ej)
                                <tr class="hover:bg-zinc-50/50">
                                    <td class="py-2 px-3 text-center font-bold">#{{ $ej->operational_order }}</td>
                                    <td class="py-2 px-3 font-semibold">{{ $ej->evaluationUnit->name }}</td>
                                    <td class="py-2 px-3 text-xs">
                                        <span class="inline-flex items-center rounded px-2 py-0.5 font-medium bg-amber-100 text-amber-800">
                                            {{ $ej->decision }}
                                        </span>
                                    </td>
                                    <td class="py-2 px-3 text-xs text-zinc-700">{{ $ej->reason }}</td>
                                    <td class="py-2 px-3 text-xs text-zinc-500">{{ $ej->reviewer->name ?? 'Admin' }}</td>
                                    <td class="py-2 px-3 text-xs text-zinc-400">{{ $ej->updated_at?->format('d M Y H:i') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <flux:text class="text-xs text-zinc-500">Belum ada penyesuaian Expert Judgment pada calculation run ini.</flux:text>
            @endif
        </flux:card>
    @endif
</div>
