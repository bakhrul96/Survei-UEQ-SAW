<div class="mx-auto flex w-full max-w-2xl flex-col items-center justify-center space-y-6 py-16 text-center">
    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600">
        <svg class="h-8 w-8" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M4 4a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H4Zm0 2h12v8H4V6Z" clip-rule="evenodd" />
        </svg>
    </div>
    <div class="space-y-2">
        <h1 class="display-type text-2xl text-zinc-900">Belum ada periode</h1>
        <p class="max-w-prose text-zinc-600">Buat periode penelitian terlebih dahulu untuk mulai mengumpulkan dan menganalisis data survei.</p>
    </div>
    <flux:button :href="route('admin.study-settings')" variant="primary" icon="plus">Buat periode baru</flux:button>
</div>
