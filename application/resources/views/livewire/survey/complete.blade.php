<div class="mx-auto max-w-xl space-y-6 p-4 sm:p-6">
    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-6">
        <h1 class="text-2xl font-semibold text-emerald-900">Penilaian berhasil disimpan</h1>
        <p class="mt-2 text-emerald-800">Terima kasih atas waktu dan pengalaman yang Anda bagikan.</p>
        @if ($submission->session_sequence >= 3)
            <p class="mt-4 rounded-lg bg-amber-100 p-3 text-amber-900">Anda telah menilai tiga modul pada sesi ini. Sebaiknya beristirahat sebelum melanjutkan.</p>
        @endif
    </div>

    <div class="flex flex-wrap gap-3">
        <a href="{{ route('home') }}" class="rounded-lg border border-zinc-300 px-4 py-2 font-medium text-zinc-800 focus:outline-none focus:ring-2 focus:ring-indigo-500">Akhiri</a>
        <a href="{{ route('survey.units', $period) }}" class="rounded-lg bg-indigo-600 px-4 py-2 font-medium text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">Nilai modul lain</a>
    </div>
</div>
