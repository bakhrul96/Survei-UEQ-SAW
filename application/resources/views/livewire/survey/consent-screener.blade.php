<div class="mx-auto w-full max-w-2xl space-y-6">
    <header class="space-y-5">
        <div class="reveal space-y-3">
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-indigo-600">Penelitian Tugas Akhir</p>
            <h1 class="display-type text-gradient text-3xl sm:text-4xl">Informasi Penelitian</h1>
            <p class="max-w-prose text-base leading-relaxed text-zinc-600">{{ $period->name }} — bantu tentukan prioritas perbaikan layanan dengan menilai modul yang pernah Anda gunakan.</p>
        </div>

        <div class="grid grid-cols-3 gap-2.5">
            <div class="bento-card reveal reveal-delay-1 px-3 py-3 text-center">
                <p class="text-lg font-bold text-indigo-700">Anonim</p>
                <p class="mt-0.5 text-xs text-zinc-500">Tanpa nama &amp; NIK</p>
            </div>
            <div class="bento-card reveal reveal-delay-2 px-3 py-3 text-center">
                <p class="text-lg font-bold text-indigo-700">±{{ $period->consent_estimated_minutes }} mnt</p>
                <p class="mt-0.5 text-xs text-zinc-500">estimasi pengisian</p>
            </div>
            <div class="bento-card reveal reveal-delay-3 px-3 py-3 text-center">
                <p class="text-lg font-bold text-indigo-700">26 pertanyaan</p>
                <p class="mt-0.5 text-xs text-zinc-500">per modul</p>
            </div>
        </div>

        <p class="surface-tint hairline reveal reveal-delay-3 rounded-2xl px-4 py-3 text-sm font-medium text-indigo-900">Hanya modul yang pernah Anda gunakan yang dinilai — jawaban tersimpan sementara di perangkat bila koneksi terputus.</p>
    </header>

    <flux:card class="space-y-4">
        <div class="space-y-4 text-sm leading-6 text-zinc-700 dark:text-zinc-300">
            <section>
                <h2 class="font-semibold text-zinc-900 dark:text-white">Tujuan penelitian</h2>
                <p class="whitespace-pre-line">{{ $period->consent_text }}</p>
            </section>
            <section>
                <h2 class="font-semibold text-zinc-900 dark:text-white">Data yang disimpan</h2>
                <p class="whitespace-pre-line">{{ $period->consent_data_description }}</p>
            </section>
            <section>
                <h2 class="font-semibold text-zinc-900 dark:text-white">Penggunaan cookie</h2>
                <p class="whitespace-pre-line">{{ $period->consent_cookie_description }}</p>
            </section>
            <p><span class="font-semibold text-zinc-900 dark:text-white">Estimasi waktu:</span> {{ $period->consent_estimated_minutes }} menit.</p>
            <section>
                <h2 class="font-semibold text-zinc-900 dark:text-white">Hak berhenti</h2>
                <p class="whitespace-pre-line">{{ $period->consent_withdrawal_description }}</p>
            </section>
            <p><span class="font-semibold text-zinc-900 dark:text-white">Kontak penelitian:</span> {{ $period->research_contact }}</p>
        </div>

        <form wire:submit="submit" class="space-y-5">
            @php
                $checkboxCard = 'flex min-h-11 cursor-pointer items-start gap-3 rounded-xl border p-4 text-left transition focus-within:ring-2 focus-within:ring-indigo-500';
                $checkboxBox = 'mt-0.5 h-6 w-6 shrink-0 rounded border-zinc-400 text-indigo-600 focus:ring-2 focus:ring-indigo-500';
            @endphp

            <label class="{{ $checkboxCard }} {{ $consent ? 'border-indigo-500 bg-indigo-50' : 'border-zinc-300 bg-white hover:border-indigo-400' }}">
                <input type="checkbox" wire:model.live="consent" class="{{ $checkboxBox }}">
                <span class="text-sm font-medium text-zinc-900">Saya telah membaca informasi penelitian dan bersedia berpartisipasi.</span>
            </label>
            @error('consent') <p role="alert" class="text-sm font-medium text-red-600">{{ $message }}</p> @enderror

            <flux:input wire:model="age" type="number" min="17" max="100" label="Usia" />

            <label class="{{ $checkboxCard }} {{ $isIndramayuResident ? 'border-indigo-500 bg-indigo-50' : 'border-zinc-300 bg-white hover:border-indigo-400' }}">
                <input type="checkbox" wire:model.live="isIndramayuResident" class="{{ $checkboxBox }}">
                <span class="text-sm font-medium text-zinc-900">Saya berdomisili di Kabupaten Indramayu.</span>
            </label>
            @error('isIndramayuResident') <p role="alert" class="text-sm font-medium text-red-600">{{ $message }}</p> @enderror

            <label class="{{ $checkboxCard }} {{ $hasUsedWongReang ? 'border-indigo-500 bg-indigo-50' : 'border-zinc-300 bg-white hover:border-indigo-400' }}">
                <input type="checkbox" wire:model.live="hasUsedWongReang" class="{{ $checkboxBox }}">
                <span class="text-sm font-medium text-zinc-900">Saya pernah menggunakan aplikasi Wong Reang.</span>
            </label>
            @error('hasUsedWongReang') <p role="alert" class="text-sm font-medium text-red-600">{{ $message }}</p> @enderror

            <flux:button type="submit" variant="primary" class="min-h-11 w-full text-base">Lanjutkan</flux:button>
        </form>
    </flux:card>
</div>
