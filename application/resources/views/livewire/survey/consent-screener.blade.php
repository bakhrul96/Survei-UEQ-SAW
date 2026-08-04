<div class="mx-auto w-full max-w-2xl space-y-6">
    <div>
        <flux:heading size="xl">Persetujuan partisipasi</flux:heading>
        <flux:text>{{ $period->name }}</flux:text>
    </div>

    <flux:card class="space-y-4">
        <p class="whitespace-pre-line text-sm leading-6 text-zinc-700 dark:text-zinc-300">{{ $period->consent_text }}</p>

        <form wire:submit="submit" class="space-y-5">
            <flux:checkbox wire:model="consent" label="Saya telah membaca informasi penelitian dan bersedia berpartisipasi." />
            <flux:input wire:model="age" type="number" min="17" max="100" label="Usia" />
            <flux:checkbox wire:model="isIndramayuResident" label="Saya berdomisili di Kabupaten Indramayu." />
            <flux:checkbox wire:model="hasUsedWongReang" label="Saya pernah menggunakan aplikasi Wong Reang." />

            <flux:button type="submit" variant="primary">Lanjutkan</flux:button>
        </form>
    </flux:card>
</div>
