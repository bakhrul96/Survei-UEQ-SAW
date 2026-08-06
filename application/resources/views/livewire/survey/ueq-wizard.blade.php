<div class="mx-auto max-w-3xl space-y-6 p-4 sm:p-6">
    <header class="space-y-2">
        <p class="text-sm font-medium text-indigo-700">Langkah {{ $step }} dari 4</p>
        <h1 class="text-2xl font-semibold text-zinc-900">{{ $unit->name }}</h1>
        <p class="text-zinc-600">Pilih angka yang paling menggambarkan pengalaman Anda. Tidak ada skor terkonversi yang ditampilkan.</p>
    </header>

    <div wire:offline class="rounded-lg bg-amber-100 p-3 text-amber-900">
        Koneksi terputus. Jawaban tetap tersimpan di perangkat; kirim setelah tersambung kembali.
    </div>

    @if ($step === 1)
        <label class="flex gap-3 rounded-lg border border-zinc-200 p-4 text-zinc-800">
            <input type="checkbox" wire:model="confirmedExperience" class="mt-1 rounded border-zinc-400 text-indigo-600 focus:ring-2 focus:ring-indigo-500">
            <span>Saya pernah menyelesaikan minimal satu proses layanan pada modul {{ $unit->name }}.</span>
        </label>
        @error('confirmedExperience') <p role="alert" class="text-sm text-red-700">{{ $message }}</p> @enderror
    @endif

    @foreach ($items as $item)
        <fieldset wire:key="ueq-item-{{ $item->order }}" class="space-y-3 rounded-xl border border-zinc-200 bg-white p-4 shadow-sm">
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

    <div class="flex items-center justify-between gap-3">
        @if ($step > 1)
            <button type="button" wire:click="previous" class="rounded-lg border border-zinc-300 px-4 py-2 font-medium text-zinc-800 focus:outline-none focus:ring-2 focus:ring-indigo-500">Kembali</button>
        @else
            <span></span>
        @endif

        @if ($step < 4)
            <button type="button" wire:click="next" class="rounded-lg bg-indigo-600 px-4 py-2 font-medium text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">Berikutnya</button>
        @else
            <button type="button" wire:click="submit" wire:loading.attr="disabled" wire:offline.attr="disabled" class="rounded-lg bg-indigo-600 px-4 py-2 font-medium text-white disabled:cursor-not-allowed disabled:opacity-50 focus:outline-none focus:ring-2 focus:ring-indigo-500">Kirim Penilaian</button>
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
