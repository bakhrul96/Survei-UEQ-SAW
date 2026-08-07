<div class="mx-auto w-full max-w-2xl space-y-6">
    <header class="reveal space-y-1.5 text-center">
        <h1 class="display-type text-gradient text-3xl">Pengaturan Studi</h1>
        <p class="max-w-prose mx-auto text-zinc-600">Belum ada periode penelitian. Buat yang pertama untuk memulai.</p>
    </header>

    <div class="bento-card space-y-4 p-5 sm:p-6">
        <div class="space-y-1">
            <h2 class="display-type text-xl text-zinc-900">Buat periode baru</h2>
            <p class="text-sm text-zinc-500">Mulai sesi penelitian baru sebagai draft. Lengkapi konfigurasi lalu aktifkan.</p>
        </div>
        <form wire:submit="createPeriod" class="grid gap-4 md:grid-cols-2">
            <flux:input wire:model="newPeriodName" label="Nama periode" placeholder="Contoh: Evaluasi Wong Reang Apps 2027" />
            <flux:input wire:model="newPeriodSlug" label="Slug (opsional)" placeholder="otomatis dari nama" />
            <flux:input wire:model="newPeriodOpensAt" type="datetime-local" label="Tanggal buka" />
            <flux:input wire:model="newPeriodClosesAt" type="datetime-local" label="Tanggal tutup" />
            <div class="md:col-span-2">
                <flux:button type="submit" variant="primary" icon="plus">Buat periode draft</flux:button>
            </div>
        </form>

        @if ($createdPeriodSlug)
            <div class="surface-tint hairline mt-4 rounded-2xl p-4">
                <p class="text-sm font-semibold text-indigo-900">Tautan untuk dibagikan ke responden:</p>
                <div class="mt-2 flex items-center gap-2">
                    <code class="min-w-0 flex-1 truncate rounded-lg border border-indigo-200 bg-white px-3 py-2 font-mono text-sm text-indigo-800">{{ url('/s/wong-reang/'.$createdPeriodSlug) }}</code>
                    <button type="button" data-copy="{{ url('/s/wong-reang/'.$createdPeriodSlug) }}" onclick="navigator.clipboard.writeText(this.dataset.copy)" class="focus-ring min-h-11 shrink-0 rounded-xl border border-zinc-300 bg-white px-4 text-sm font-medium text-zinc-800 transition hover:border-zinc-400">Salin</button>
                </div>
                <p class="mt-2 text-xs text-zinc-500">Aktif setelah periode diaktifkan.</p>
            </div>
        @endif
    </div>
</div>
