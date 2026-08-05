<div class="mx-auto w-full max-w-6xl space-y-6">
    <div>
        <flux:heading size="xl">Informan teknis</flux:heading>
        <flux:text>{{ $period->name }} · Gunakan kode anonim informan, tanpa token atau identitas responden.</flux:text>
    </div>

    @if (session('status'))
        <flux:callout variant="success" icon="check-circle">{{ session('status') }}</flux:callout>
    @endif

    <form wire:submit="save" class="space-y-6">
        <flux:card class="space-y-4">
            <flux:input wire:model="anonymousCode" label="Kode anonim informan" placeholder="Contoh: TEK-01" />
            <flux:text class="text-sm">Biarkan nilai unit kosong jika belum tersedia; nilai kosong tidak akan digantikan dengan angka lain.</flux:text>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead><tr class="border-b"><th class="p-2">Modul</th><th class="p-2">Estimasi hari</th><th class="p-2">Urgensi arsitektur (1–5)</th></tr></thead>
                    <tbody>
                        @foreach ($units as $unit)
                            <tr class="border-b border-zinc-200 dark:border-zinc-700">
                                <td class="p-2"><span class="font-medium">{{ $unit->name }}</span><br><span class="text-zinc-500">{{ $unit->code }}</span></td>
                                <td class="p-2"><flux:input wire:model="assessments.{{ $unit->id }}.days" type="number" min="0.01" step="0.01" label="Estimasi hari untuk {{ $unit->name }}" /></td>
                                <td class="p-2"><flux:input wire:model="assessments.{{ $unit->id }}.urgency" type="number" min="1" max="5" step="1" label="Urgensi untuk {{ $unit->name }}" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </flux:card>

        <flux:card class="space-y-4">
            <flux:heading size="lg">Alokasi bobot kriteria</flux:heading>
            <flux:text>Setiap informan memiliki tepat satu alokasi C1, C2, dan C3 dengan total 100 poin.</flux:text>
            <div class="grid gap-4 md:grid-cols-3">
                <flux:input wire:model="weights.c1" type="number" min="0" max="100" step="1" label="C1 (poin)" />
                <flux:input wire:model="weights.c2" type="number" min="0" max="100" step="1" label="C2 (poin)" />
                <flux:input wire:model="weights.c3" type="number" min="0" max="100" step="1" label="C3 (poin)" />
            </div>
            @error('weights')<flux:callout variant="danger" icon="exclamation-triangle">Total C1, C2, dan C3 harus tepat 100 poin.</flux:callout>@enderror
        </flux:card>

        <flux:button type="submit" variant="primary">Simpan informan</flux:button>
    </form>

    <flux:card class="space-y-4">
        <flux:heading size="lg">Konsensus teknis</flux:heading>
        <div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead><tr class="border-b"><th class="p-2">Modul</th><th class="p-2">Rata-rata hari</th><th class="p-2">Rata-rata urgensi</th></tr></thead><tbody>
            @foreach ($units as $unit)
                @php($summary = $consensus->assessments[$unit->id])
                <tr class="border-b border-zinc-200 dark:border-zinc-700"><td class="p-2">{{ $unit->name }}</td><td class="p-2">{{ $summary['mean_days'] ?? 'Belum ada' }}</td><td class="p-2">{{ $summary['mean_urgency'] ?? 'Belum ada' }}</td></tr>
            @endforeach
        </tbody></table></div>
        <dl class="grid gap-3 text-sm md:grid-cols-3">
            @foreach ($consensus->weights as $criterion => $weight)
                <div><dt class="font-medium">{{ strtoupper($criterion) }}</dt><dd>{{ $weight === null ? 'Belum ada' : number_format($weight, 4) }}</dd></div>
            @endforeach
        </dl>
    </flux:card>
</div>
