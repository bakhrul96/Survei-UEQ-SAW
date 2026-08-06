@php($flagLabels = [
    'fast_completion' => 'Durasi terlalu cepat',
    'identical_answers' => 'Jawaban identik',
])

<div class="mx-auto w-full max-w-6xl space-y-6">
    <header class="reveal space-y-1.5">
        <h1 class="display-type text-gradient text-3xl">Review kualitas respons</h1>
        <p class="max-w-prose text-zinc-600">{{ $period->name }} · Flag membantu peninjauan, bukan keputusan eksklusi otomatis.</p>
    </header>

    @if (session('status'))
        <flux:callout variant="success" icon="check-circle">{{ session('status') }}</flux:callout>
    @endif

    @if ($submissionId)
        <div class="bento-card space-y-4 border-indigo-200 p-5 sm:p-6">
            <h2 class="display-type text-xl text-zinc-900">Keputusan review</h2>
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
        </div>
    @endif

    <div class="bento-card p-5 sm:p-6">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 text-xs uppercase tracking-wide text-zinc-500">
                        <th class="p-2.5 font-semibold">Modul</th>
                        <th class="p-2.5 font-semibold">Durasi</th>
                        <th class="p-2.5 font-semibold">Flag</th>
                        <th class="p-2.5 font-semibold">Keputusan</th>
                        <th class="p-2.5 font-semibold">Reviewer</th>
                        <th class="p-2.5"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse ($responses as $response)
                        <tr class="transition hover:bg-indigo-50/40">
                            <td class="p-2.5 font-medium text-zinc-900">{{ $response->unitName }}</td>
                            <td class="p-2.5 font-mono text-xs text-zinc-500">{{ $response->durationSeconds }} detik</td>
                            <td class="p-2.5">
                                @if ($response->flags)
                                    @php($active = collect($response->flags)->filter()->keys()->map(fn (string $flag) => $flagLabels[$flag] ?? str($flag)->replace('_', ' ')->headline()))
                                    @if ($active->isEmpty())
                                        <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-800">Bersih</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800">{{ $active->join(', ') }}</span>
                                    @endif
                                @else
                                    <span class="text-xs text-zinc-400">Belum direview</span>
                                @endif
                            </td>
                            <td class="p-2.5">
                                @if ($response->decision === 'included')
                                    <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-800">Included</span>
                                @elseif ($response->decision === 'excluded')
                                    <span class="inline-flex items-center rounded-full bg-rose-100 px-2.5 py-0.5 text-xs font-semibold text-rose-800">Excluded</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-semibold text-zinc-600">Belum direview</span>
                                @endif
                            </td>
                            <td class="p-2.5 text-xs text-zinc-500">{{ $response->reviewerName ?? '-' }}</td>
                            <td class="p-2.5"><flux:button size="sm" wire:click="openReview({{ $response->submissionId }})">Review</flux:button></td>
                        </tr>
                    @empty
                        <tr><td class="p-4 text-center text-zinc-500" colspan="6">Belum ada respons terkirim pada periode ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
