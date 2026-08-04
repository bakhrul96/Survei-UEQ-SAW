<div class="mx-auto w-full max-w-6xl space-y-6">
    <div>
        <flux:heading size="xl">Dashboard progres</flux:heading>
        <flux:text>{{ $period->name }} · Rilis 1 menampilkan respons yang telah dikirim.</flux:text>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <flux:card><flux:text>Responden unik</flux:text><flux:heading size="xl">{{ $data->uniqueRespondents }}</flux:heading></flux:card>
        <flux:card><flux:text>Responden memenuhi syarat</flux:text><flux:heading size="xl">{{ $data->eligibleRespondents }}</flux:heading></flux:card>
        <flux:card><flux:text>Evaluasi modul terkirim</flux:text><flux:heading size="xl">{{ $data->totalEvaluations }}</flux:heading></flux:card>
        <flux:card><flux:text>Target evaluasi modul</flux:text><flux:heading size="xl">{{ $data->units->count() * $period->target_per_unit }}</flux:heading></flux:card>
    </div>

    <flux:card class="space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div><flux:heading size="lg">Progres per modul</flux:heading><flux:text>Nilai valid sama dengan evaluasi terkirim pada Rilis 1.</flux:text></div>
            <div class="flex gap-2"><flux:button :href="route('admin.exports.raw.csv', $period)">CSV</flux:button><flux:button :href="route('admin.exports.raw.xlsx', $period)" variant="primary">XLSX</flux:button></div>
        </div>
        <div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead><tr class="border-b"><th class="p-2">Kode</th><th class="p-2">Modul</th><th class="p-2">Valid</th><th class="p-2">Minimum</th><th class="p-2">Target</th><th class="p-2">Status</th></tr></thead><tbody>
            @foreach ($data->units as $unit)
                <tr class="border-b border-zinc-200 dark:border-zinc-700"><td class="p-2">{{ $unit->code }}</td><td class="p-2">{{ $unit->name }}</td><td class="p-2">{{ $unit->valid }}</td><td class="p-2">{{ $unit->minimum }}</td><td class="p-2">{{ $unit->target }}</td><td class="p-2">{{ str($unit->status)->replace('_', ' ')->headline() }}</td></tr>
            @endforeach
        </tbody></table></div>
    </flux:card>
</div>
