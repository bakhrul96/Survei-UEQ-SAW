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
            <flux:checkbox wire:model="consent" label="Saya telah membaca informasi penelitian dan bersedia berpartisipasi." />
            <flux:input wire:model="age" type="number" min="17" max="100" label="Usia" />
            <flux:checkbox wire:model="isIndramayuResident" label="Saya berdomisili di Kabupaten Indramayu." />
            <flux:checkbox wire:model="hasUsedWongReang" label="Saya pernah menggunakan aplikasi Wong Reang." />

            <flux:button type="submit" variant="primary">Lanjutkan</flux:button>
        </form>
    </flux:card>
</div>
