<div class="space-y-6">
    <div class="bento-card reveal space-y-3 border-emerald-200 bg-emerald-50/80 p-6 sm:p-8">
        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-500 text-white shadow-sm">
            <svg class="h-6 w-6" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M12.416 3.376a.75.75 0 0 1 .208 1.04l-5 7.5a.75.75 0 0 1-1.154.114l-3-3a.75.75 0 0 1 1.06-1.06l2.353 2.353 4.493-6.74a.75.75 0 0 1 1.04-.207Z" clip-rule="evenodd"/></svg>
        </div>
        <h1 class="display-type text-2xl text-emerald-950">Penilaian berhasil disimpan</h1>
        <p class="text-emerald-800">Terima kasih atas waktu dan pengalaman yang Anda bagikan.</p>
        @if ($submission->session_sequence >= 3)
            <p class="hairline rounded-xl bg-amber-50 p-3 text-sm font-medium text-amber-900">Anda telah menilai tiga modul pada sesi ini. Sebaiknya beristirahat sebelum melanjutkan.</p>
        @endif
    </div>

    <div class="flex flex-wrap gap-3">
        <a href="{{ route('home') }}" class="focus-ring min-h-11 rounded-xl border border-zinc-300 bg-white px-5 py-2.5 font-medium text-zinc-800 transition hover:border-zinc-400">Akhiri</a>
        <a href="{{ route('survey.units', $period) }}" class="focus-ring min-h-11 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-6 py-2.5 font-semibold text-white shadow-sm transition hover:from-indigo-500 hover:to-violet-500">Nilai modul lain</a>
    </div>
</div>