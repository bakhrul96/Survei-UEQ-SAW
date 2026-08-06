<x-layouts::survey :title="'Survei UEQ Wong Reang'">
    <div class="mx-auto flex min-h-screen w-full max-w-2xl flex-col justify-center space-y-8 p-6">
        <header class="space-y-3 text-center">
            <p class="text-sm font-semibold uppercase tracking-wide text-indigo-700">Penelitian Tugas Akhir</p>
            <h1 class="text-3xl font-bold leading-tight text-zinc-900">Survei Pengalaman Pengguna Wong Reang Apps</h1>
            <p class="text-zinc-600">Bantu kami menentukan prioritas perbaikan layanan dengan menilai modul yang pernah Anda gunakan.</p>
        </header>

        <div class="grid gap-3 sm:grid-cols-3">
            <div class="rounded-xl border border-zinc-200 bg-white p-4 text-center shadow-sm">
                <p class="text-2xl font-bold text-indigo-700">26</p>
                <p class="mt-1 text-sm text-zinc-600">pertanyaan per modul</p>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-4 text-center shadow-sm">
                <p class="text-2xl font-bold text-indigo-700">±10</p>
                <p class="mt-1 text-sm text-zinc-600">menit pengisian</p>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-4 text-center shadow-sm">
                <p class="text-2xl font-bold text-indigo-700">Anonim</p>
                <p class="mt-1 text-sm text-zinc-600">tanpa nama &amp; NIK</p>
            </div>
        </div>

        <div class="rounded-xl border border-amber-200 bg-amber-50 p-5 text-sm leading-6 text-amber-900">
            Survei sedang tidak dibuka saat ini. Silakan kembali lagi ketika periode penelitian dibuka, atau hubungi peneliti untuk informasi jadwal.
        </div>
    </div>
</x-layouts::survey>
