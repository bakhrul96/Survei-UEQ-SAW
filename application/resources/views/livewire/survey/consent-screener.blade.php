<div class="mx-auto w-full max-w-2xl space-y-6">
    <div>
        <flux:heading size="xl">Informasi Penelitian</flux:heading>
        <flux:text>{{ $period->name }}</flux:text>
    </div>

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
