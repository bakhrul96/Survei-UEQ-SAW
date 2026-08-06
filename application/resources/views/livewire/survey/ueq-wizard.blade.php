<div class="space-y-6">
    @php($stepPct = (int) round(($step / 4) * 100))
    <header class="reveal space-y-4">
        <div class="space-y-2">
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-indigo-600">Langkah {{ $step }} dari 4</p>
            <h1 class="display-type text-3xl text-zinc-900">{{ $unit->name }}</h1>
            <p class="max-w-prose text-zinc-600">Pilih angka yang paling menggambarkan pengalaman Anda. Tidak ada skor terkonversi yang ditampilkan.</p>
        </div>
        <div class="h-1.5 overflow-hidden rounded-full bg-zinc-200" role="progressbar" aria-valuenow="{{ $stepPct }}" aria-valuemin="0" aria-valuemax="100" aria-label="Progres langkah pengisian">
            <div class="h-full rounded-full bg-gradient-to-r from-indigo-600 to-violet-500 transition-all duration-500" style="width: {{ $stepPct }}%"></div>
        </div>
    </header>

    <div wire:offline class="hairline rounded-2xl bg-amber-50 p-3.5 text-sm font-medium text-amber-900">
        Koneksi terputus. Jawaban tetap tersimpan di perangkat; kirim setelah tersambung kembali.
    </div>

    @if ($step === 1)
        <label class="bento-card focus-ring flex min-h-11 cursor-pointer items-start gap-3 p-4 {{ $confirmedExperience ? 'border-indigo-400 bg-indigo-50' : '' }}">
            <input type="checkbox" wire:model.live="confirmedExperience" class="mt-0.5 h-6 w-6 shrink-0 rounded border-zinc-400 text-indigo-600 focus:ring-2 focus:ring-indigo-500">
            <span class="text-sm font-medium text-zinc-900">Saya pernah menyelesaikan minimal satu proses layanan pada modul {{ $unit->name }}.</span>
        </label>
        @error('confirmedExperience') <p role="alert" class="text-sm text-red-700">{{ $message }}</p> @enderror
    @endif

    @foreach ($items as $item)
        <fieldset wire:key="ueq-item-{{ $item->order }}" class="bento-card space-y-3.5 p-4 sm:p-5">
            <legend class="sr-only">Item {{ $item->order }}</legend>
            <div class="flex items-start justify-between gap-4">
                <span class="max-w-[45%] rounded-md bg-zinc-100 px-2.5 py-1.5 text-left text-sm font-semibold leading-snug text-zinc-900">{{ $item->left_label }}</span>
                <span class="max-w-[45%] rounded-md bg-zinc-100 px-2.5 py-1.5 text-right text-sm font-semibold leading-snug text-zinc-900">{{ $item->right_label }}</span>
            </div>
            <div class="grid grid-cols-7 gap-2" role="radiogroup" aria-label="Item {{ $item->order }}">
                @foreach (range(1, 7) as $value)
                    <label for="ueq-item-{{ $item->order }}-value-{{ $value }}" class="flex min-h-11 cursor-pointer items-center justify-center rounded-lg border border-zinc-300 bg-white text-base font-semibold text-zinc-900 transition has-[:checked]:border-indigo-600 has-[:checked]:bg-indigo-600 has-[:checked]:text-white has-[:checked]:shadow focus-within:ring-2 focus-within:ring-indigo-500">
                        <input id="ueq-item-{{ $item->order }}-value-{{ $value }}" name="ueq-item-{{ $item->order }}" type="radio" wire:model="answers.{{ $item->order }}" value="{{ $value }}" aria-label="Item {{ $item->order }} nilai {{ $value }}" class="sr-only">
                        <span aria-hidden="true">{{ $value }}</span>
                    </label>
                @endforeach
            </div>
            @error('answers.'.$item->order) <p role="alert" class="text-sm text-red-700">{{ $message }}</p> @enderror
        </fieldset>
    @endforeach

    <div class="flex items-center justify-between gap-3 pt-2">
        @if ($step > 1)
            <button type="button" wire:click="previous" class="focus-ring min-h-11 rounded-xl border border-zinc-300 bg-white px-5 font-medium text-zinc-800 transition hover:border-zinc-400">Kembali</button>
        @else
            <span></span>
        @endif

        @if ($step < 4)
            <button type="button" wire:click="next" class="focus-ring min-h-11 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-6 font-semibold text-white shadow-sm transition hover:from-indigo-500 hover:to-violet-500">Berikutnya</button>
        @else
            <button type="button" wire:click="submit" wire:loading.attr="disabled" wire:offline.attr="disabled" class="focus-ring min-h-11 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-6 font-semibold text-white shadow-sm transition hover:from-indigo-500 hover:to-violet-500 disabled:cursor-not-allowed disabled:opacity-50">Kirim Penilaian</button>
        @endif
    </div>

    @script
    <script>
        const draftKey = @js($this->draftKey);
        const stored = localStorage.getItem(draftKey);
        if (stored) {
            const draft = JSON.parse(stored);
            $wire.answers = draft.answers ?? {};
            $wire.step = draft.step ?? 1;
            $wire.confirmedExperience = draft.confirmedExperience ?? false;
        }
        const saveDraft = () => localStorage.setItem(draftKey, JSON.stringify({
            answers: $wire.answers,
            step: $wire.step,
            confirmedExperience: $wire.confirmedExperience,
        }));
        $wire.$watch('answers', saveDraft);
        $wire.$watch('step', saveDraft);
        $wire.$watch('confirmedExperience', saveDraft);
        $wire.on('survey-submitted', ({ key }) => localStorage.removeItem(key));
    </script>
    @endscript
</div>
