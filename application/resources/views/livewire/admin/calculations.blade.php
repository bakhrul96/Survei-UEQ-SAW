<div class="mx-auto w-full max-w-7xl space-y-6">
    <div><flux:heading size="xl">Kalkulasi UEQ dan SAW</flux:heading><flux:text>{{ $period->name }} · Preview tidak mengunci hasil.</flux:text></div>
    @if(session('status')) <flux:callout variant="success">{{ session('status') }}</flux:callout> @endif
    <flux:button wire:click="runPreview" variant="primary">Jalankan preview</flux:button>
    @if($run)
        <flux:card><flux:heading size="lg">Preview {{ $run->status }}</flux:heading><flux:text>Versi: {{ $run->algorithm_version }} · Input hash: {{ $run->input_hash }} · Included: {{ $run->included_count }} · Excluded: {{ $run->excluded_count }}</flux:text>
            @foreach($run->warnings as $warning)<flux:text class="text-amber-700">{{ $warning }}</flux:text>@endforeach
        </flux:card>
        <flux:card><flux:heading size="lg">Hasil UEQ</flux:heading><div class="overflow-x-auto"><table class="w-full text-sm"><thead><tr><th>Modul</th><th>Skala</th><th>n</th><th>Mean</th><th>Gap</th><th>Alasan</th></tr></thead><tbody>@foreach($run->ueqResults as $row)<tr><td>{{ $row->unit->name }}</td><td>{{ $row->scale }}</td><td>{{ $row->n }}</td><td>{{ $row->mean ?? '-' }}</td><td>{{ $row->gap ?? '-' }}</td><td>{{ $row->unavailable_reason ?? '-' }}</td></tr>@endforeach</tbody></table></div></flux:card>
        <flux:card><flux:heading size="lg">Peringkat SAW</flux:heading><div class="overflow-x-auto"><table class="w-full text-sm"><thead><tr><th>Modul</th><th>X1</th><th>X2</th><th>X3</th><th>Vi</th><th>Rank</th></tr></thead><tbody>@forelse($run->sawResults as $row)<tr><td>{{ $row->unit->name }}</td><td>{{ $row->x1_gap }}</td><td>{{ $row->x2_days }}</td><td>{{ $row->x3_urgency }}</td><td>{{ $row->preference_value }}</td><td>{{ $row->rank }}{{ $row->is_tied ? ' (seri)' : '' }}</td></tr>@empty<tr><td colspan="6">Belum ada peringkat SAW.</td></tr>@endforelse</tbody></table></div></flux:card>
    @endif
</div>
