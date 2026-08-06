<div class="mx-auto w-full max-w-6xl space-y-7">
    @php
        $stats = [
            ['label' => 'Responden unik', 'value' => $data->uniqueRespondents, 'tone' => 'indigo'],
            ['label' => 'Responden memenuhi syarat', 'value' => $data->eligibleRespondents, 'tone' => 'sky'],
            ['label' => 'Evaluasi modul terkirim', 'value' => $data->totalEvaluations, 'tone' => 'violet'],
            ['label' => 'Target evaluasi modul', 'value' => $data->units->count() * $period->target_per_unit, 'tone' => 'zinc'],
            ['label' => 'Evaluasi ber-flag kualitas', 'value' => $data->flaggedEvaluations, 'tone' => 'amber'],
            ['label' => 'Evaluasi excluded', 'value' => $data->excludedEvaluations, 'tone' => 'rose'],
            ['label' => 'Menunggu review kualitas', 'value' => $data->pendingReviewEvaluations, 'tone' => 'sky'],
        ];
        $tones = [
            'indigo' => 'text-indigo-700',
            'sky' => 'text-sky-700',
            'violet' => 'text-violet-700',
            'zinc' => 'text-zinc-800',
            'amber' => 'text-amber-700',
            'rose' => 'text-rose-700',
        ];
        $statusTone = fn (string $status): string => match ($status) {
            'target_reached' => 'bg-emerald-100 text-emerald-800',
            'minimal_reached' => 'bg-sky-100 text-sky-800',
            default => 'bg-amber-100 text-amber-800',
        };
    @endphp

    <header class="reveal space-y-3">
        <div class="space-y-1.5">
            <h1 class="display-type text-gradient text-3xl">Dashboard progres</h1>
            <p class="max-w-prose text-zinc-600">{{ $period->name }} · Rilis 1 - 3 Sistem Penelitian UEQ-SAW Wong Reang Apps.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <flux:button :href="route('admin.responses')" icon="chat-bubble-left-right">Respons</flux:button>
            <flux:button :href="route('admin.technical-assessments')" icon="users">Informan</flux:button>
            <flux:button :href="route('admin.calculations')" icon="calculator">Kalkulasi</flux:button>
            <flux:button :href="route('admin.reports')" icon="document-chart-bar" variant="primary">Laporan Bab IV</flux:button>
        </div>
    </header>

    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($stats as $index => $stat)
            <div class="bento-card reveal reveal-delay-{{ ($index % 3) + 1 }} p-5">
                <p class="text-sm font-medium text-zinc-500">{{ $stat['label'] }}</p>
                <p class="display-type mt-1.5 text-4xl {{ $tones[$stat['tone']] }}">{{ $stat['value'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="bento-card space-y-4 p-5 sm:p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="space-y-1">
                <h2 class="display-type text-xl text-zinc-900">Progres per modul</h2>
                <p class="text-sm text-zinc-500">Nilai valid sama dengan evaluasi terkirim.</p>
            </div>
            <div class="flex gap-2"><flux:button :href="route('admin.exports.raw.csv', $period)">CSV</flux:button><flux:button :href="route('admin.exports.raw.xlsx', $period)" variant="primary">XLSX</flux:button></div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 text-xs uppercase tracking-wide text-zinc-500">
                        <th class="p-2.5 font-semibold">Kode</th>
                        <th class="p-2.5 font-semibold">Modul</th>
                        <th class="p-2.5 text-center font-semibold">Valid</th>
                        <th class="p-2.5 text-center font-semibold">Minimum</th>
                        <th class="p-2.5 text-center font-semibold">Target</th>
                        <th class="p-2.5 font-semibold">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @foreach ($data->units as $unit)
                        <tr class="transition hover:bg-indigo-50/40">
                            <td class="p-2.5 font-mono text-xs text-zinc-500">{{ $unit->code }}</td>
                            <td class="p-2.5 font-medium text-zinc-900">{{ $unit->name }}</td>
                            <td class="p-2.5 text-center font-bold text-zinc-900">{{ $unit->valid }}</td>
                            <td class="p-2.5 text-center text-zinc-500">{{ $unit->minimum }}</td>
                            <td class="p-2.5 text-center text-zinc-500">{{ $unit->target }}</td>
                            <td class="p-2.5"><span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $statusTone($unit->status) }}">{{ str($unit->status)->replace('_', ' ')->headline() }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
