<div class="mx-auto w-full max-w-6xl space-y-6">
    <div>
        <flux:heading size="xl">Review kualitas respons</flux:heading>
        <flux:text>{{ $period->name }} · Flag membantu peninjauan, bukan keputusan eksklusi otomatis.</flux:text>
    </div>

    @if (session('status'))
        <flux:callout variant="success" icon="check-circle">{{ session('status') }}</flux:callout>
    @endif

    @if ($submissionId)
        <flux:card class="space-y-4">
            <flux:heading size="lg">Keputusan review</flux:heading>
            <form wire:submit="saveReview" class="space-y-4">
                <flux:select wire:model="decision" label="Keputusan">
                    <option value="included">Included</option>
                    <option value="excluded">Excluded</option>
                </flux:select>
                <flux:textarea wire:model="reason" label="Alasan" rows="3" />
                @error('reason')
                    <flux:text class="text-red-700 dark:text-red-300">{{ $message }}</flux:text>
                @enderror
                <flux:button type="submit" variant="primary">Simpan keputusan</flux:button>
            </form>
        </flux:card>
    @endif

    <flux:card>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b">
                        <th class="p-2">Modul</th>
                        <th class="p-2">Durasi</th>
                        <th class="p-2">Flag</th>
                        <th class="p-2">Keputusan</th>
                        <th class="p-2">Reviewer</th>
                        <th class="p-2"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($responses as $response)
                        <tr class="border-b border-zinc-200 dark:border-zinc-700">
                            <td class="p-2">{{ $response->unitName }}</td>
                            <td class="p-2">{{ $response->durationSeconds }} detik</td>
                            <td class="p-2">
                                @if ($response->flags)
                                    {{ collect($response->flags)->filter()->keys()->map(fn (string $flag) => str($flag)->replace('_', ' ')->headline())->join(', ') ?: 'Tidak ada' }}
                                @else
                                    Belum direview
                                @endif
                            </td>
                            <td class="p-2">{{ $response->decision ? str($response->decision)->headline() : 'Belum direview' }}</td>
                            <td class="p-2">{{ $response->reviewerName ?? '-' }}</td>
                            <td class="p-2"><flux:button size="sm" wire:click="openReview({{ $response->submissionId }})">Review</flux:button></td>
                        </tr>
                    @empty
                        <tr><td class="p-4 text-center" colspan="6">Belum ada respons terkirim pada periode ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </flux:card>
</div>
