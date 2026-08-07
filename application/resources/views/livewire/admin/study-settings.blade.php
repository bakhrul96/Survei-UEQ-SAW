<div class="mx-auto w-full max-w-4xl space-y-6">
    <header class="reveal space-y-1.5">
        <h1 class="display-type text-gradient text-3xl">Pengaturan Studi</h1>
        <p class="max-w-prose text-zinc-600">{{ $period->name }} · Status: <span class="font-semibold text-indigo-700">{{ str($period->status->value)->headline() }}</span></p>
    </header>

    <div class="surface-tint hairline reveal rounded-2xl p-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="min-w-0 space-y-1">
                <p class="text-sm font-semibold text-indigo-900">Tautan survei periode ini (untuk dibagikan ke responden)</p>
                <code class="block truncate font-mono text-sm text-indigo-800">{{ url('/s/wong-reang/'.$period->slug) }}</code>
            </div>
            <button type="button" data-copy="{{ url('/s/wong-reang/'.$period->slug) }}" onclick="navigator.clipboard.writeText(this.dataset.copy)" class="focus-ring min-h-11 shrink-0 rounded-xl border border-zinc-300 bg-white px-4 text-sm font-medium text-zinc-800 transition hover:border-zinc-400">Salin tautan</button>
        </div>
        <p class="mt-2 text-xs text-zinc-500">Tautan aktif untuk responden selama periode berstatus aktif.</p>
    </div>

    <div class="bento-card space-y-4 p-5 sm:p-6">
        <div class="space-y-1">
            <h2 class="display-type text-xl text-zinc-900">Buat periode baru</h2>
            <p class="text-sm text-zinc-500">Mulai sesi penelitian baru sebagai draft. Konfigurasi disalin dari periode terakhir sebagai template; lengkapi lalu aktifkan.</p>
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
                <p class="mt-2 text-xs text-zinc-500">Tautan aktif untuk responden setelah periode diaktifkan.</p>
            </div>
        @endif
    </div>

    @if (session('status'))
        <flux:callout variant="success" icon="check-circle">{{ session('status') }}</flux:callout>
    @endif

    @if ($errors->has('activation'))
        <flux:callout variant="danger" icon="exclamation-triangle">
            <flux:heading>Aktivasi belum dapat dilakukan</flux:heading>
            <flux:text>{{ $errors->first('activation') }}</flux:text>
        </flux:callout>
    @endif

    <div class="bento-card p-5 sm:p-6 {{ $issues === [] ? 'border-emerald-200 bg-emerald-50/60' : 'border-amber-200 bg-amber-50/60' }}">
        <flux:heading size="lg">Kesiapan aktivasi</flux:heading>
        @if ($issues === [])
            <flux:text class="mt-2 flex items-center gap-2 font-medium text-emerald-800">✓ Semua syarat aktivasi telah terpenuhi.</flux:text>
        @else
            <ul class="mt-3 list-disc space-y-1 ps-5 text-sm text-red-700 dark:text-red-300">
                @foreach ($issues as $issue)
                    <li>{{ $issue }}</li>
                @endforeach
            </ul>
        @endif
    </div>

    <form wire:submit="save" class="space-y-6">
        <div class="bento-card space-y-4 p-5 sm:p-6">
            <flux:heading size="lg">Konfigurasi periode</flux:heading>
            <div class="grid gap-4 md:grid-cols-2">
                <flux:input wire:model="opensAt" type="datetime-local" label="Tanggal buka" :disabled="! $isDraft" />
                <flux:input wire:model="closesAt" type="datetime-local" label="Tanggal tutup" :disabled="! $isDraft" />
                <flux:input wire:model="minimumAge" type="number" min="17" label="Usia minimum" :disabled="! $isDraft" />
                <flux:input wire:model="minimumPerUnit" type="number" min="1" label="Minimum per modul" :disabled="! $isDraft" />
                <flux:input wire:model="targetPerUnit" type="number" min="1" label="Target per modul" :disabled="! $isDraft" />
                <flux:input wire:model="instrumentSource" label="Sumber instrumen UEQ" :disabled="! $isDraft" />
            </div>
            <flux:textarea wire:model="targetBasis" label="Dasar target sampel" rows="3" :disabled="! $isDraft" />

            <div class="space-y-4 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                <flux:heading size="md">Informasi consent</flux:heading>
                <flux:textarea wire:model="consentText" label="Tujuan penelitian" rows="4" :disabled="! $isDraft" />
                <flux:textarea wire:model="consentDataDescription" label="Data yang disimpan" rows="3" :disabled="! $isDraft" />
                <flux:textarea wire:model="consentCookieDescription" label="Penggunaan cookie" rows="3" :disabled="! $isDraft" />
                <div class="grid gap-4 md:grid-cols-2">
                    <flux:input wire:model="consentEstimatedMinutes" type="number" min="1" label="Estimasi waktu (menit)" :disabled="! $isDraft" />
                    <flux:input wire:model="researchContact" label="Kontak penelitian" :disabled="! $isDraft" />
                </div>
                <flux:textarea wire:model="consentWithdrawalDescription" label="Hak berhenti" rows="3" :disabled="! $isDraft" />
            </div>

            <div class="space-y-4 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                <flux:heading size="md">Aturan kualitas data</flux:heading>
                <div class="grid gap-4 md:grid-cols-2">
                    <flux:input wire:model="fastResponseSeconds" type="number" min="1" label="Ambang respons cepat (detik)" :disabled="! $isDraft" />
                    <flux:input wire:model="qualityRulesVersion" label="Versi aturan kualitas" :disabled="! $isDraft" />
                </div>
                <flux:checkbox wire:model="identicalAnswersFlagEnabled" label="Aktifkan penanda jawaban identik" :disabled="! $isDraft" />
            </div>

            <div class="space-y-4 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                <div>
                    <flux:heading size="md">Skenario sensitivitas</flux:heading>
                    <flux:text>Bobot C1, C2, dan C3 pada setiap skenario harus berjumlah tepat 1,000000.</flux:text>
                </div>
                <div class="space-y-2">
                    <flux:heading size="sm">S1 · Dominasi UX</flux:heading>
                    <div class="grid gap-4 md:grid-cols-3">
                        <flux:input wire:model="sensitivityS1C1" type="number" min="0" max="1" step="0.000001" label="S1 C1" :disabled="! $isDraft" />
                        <flux:input wire:model="sensitivityS1C2" type="number" min="0" max="1" step="0.000001" label="S1 C2" :disabled="! $isDraft" />
                        <flux:input wire:model="sensitivityS1C3" type="number" min="0" max="1" step="0.000001" label="S1 C3" :disabled="! $isDraft" />
                    </div>
                    @error('sensitivityS1')
                        <flux:callout variant="danger" icon="exclamation-triangle">{{ $message }}</flux:callout>
                    @enderror
                </div>
                <div class="space-y-2">
                    <flux:heading size="sm">S2 · Dominasi pertimbangan teknis</flux:heading>
                    <div class="grid gap-4 md:grid-cols-3">
                        <flux:input wire:model="sensitivityS2C1" type="number" min="0" max="1" step="0.000001" label="S2 C1" :disabled="! $isDraft" />
                        <flux:input wire:model="sensitivityS2C2" type="number" min="0" max="1" step="0.000001" label="S2 C2" :disabled="! $isDraft" />
                        <flux:input wire:model="sensitivityS2C3" type="number" min="0" max="1" step="0.000001" label="S2 C3" :disabled="! $isDraft" />
                    </div>
                    @error('sensitivityS2')
                        <flux:callout variant="danger" icon="exclamation-triangle">{{ $message }}</flux:callout>
                    @enderror
                </div>
            </div>

            @if ($isDraft)
                <flux:button type="submit" variant="primary">Simpan konfigurasi</flux:button>
            @else
                <flux:callout variant="warning" icon="lock-closed">Konfigurasi terkunci sejak {{ $period->configuration_locked_at?->format('d M Y H:i') }}.</flux:callout>
            @endif
        </div>
    </form>

    <div class="bento-card space-y-4 p-5 sm:p-6">
        <div>
            <flux:heading size="lg">Bukti kesiapan operasional</flux:heading>
            <flux:text>Setiap verifikasi mencatat admin, waktu, referensi, dan catatan pemeriksaan.</flux:text>
        </div>

        @if (session('evidenceStatus'))
            <flux:callout variant="success" icon="check-circle">{{ session('evidenceStatus') }}</flux:callout>
        @endif

        <div class="space-y-5">
            @foreach ($evidenceDefinitions as $kind => $definition)
                @php($evidence = $evidenceByKind->get($kind))
                <section class="bento-card space-y-3 p-4" wire:key="evidence-{{ $kind }}">
                    <div>
                        <flux:heading size="md">{{ $definition['label'] }}</flux:heading>
                        @if ($evidence)
                            <dl class="mt-2 grid gap-2 text-sm md:grid-cols-2">
                                <div><dt class="font-medium">Referensi</dt><dd class="break-all">{{ $evidence->reference }}</dd></div>
                                <div><dt class="font-medium">Diverifikasi</dt><dd>{{ $evidence->verifier->name }} · {{ $evidence->verified_at->format('d M Y H:i') }}</dd></div>
                                <div class="md:col-span-2"><dt class="font-medium">Catatan</dt><dd class="whitespace-pre-line">{{ $evidence->notes }}</dd></div>
                            </dl>
                        @else
                            <flux:text class="mt-1">Belum diverifikasi.</flux:text>
                        @endif
                    </div>

                    @if ($isDraft)
                        <flux:input
                            wire:model="evidenceReferences.{{ $kind }}"
                            label="Referensi"
                            placeholder="{{ $definition['example'] }}"
                        />
                        <flux:textarea
                            wire:model="evidenceNotes.{{ $kind }}"
                            label="Catatan verifikasi"
                            rows="3"
                        />
                        @error("evidence.{$kind}")
                            <flux:callout variant="danger" icon="exclamation-triangle">{{ $message }}</flux:callout>
                        @enderror
                        <flux:button wire:click="recordEvidence('{{ $kind }}')" variant="filled">
                            Simpan bukti
                        </flux:button>
                    @endif
                </section>
            @endforeach
        </div>
    </div>

    <div class="bento-card space-y-3 p-5 sm:p-6">
        <flux:heading size="lg">Verifikasi</flux:heading>
        <dl class="grid gap-3 text-sm md:grid-cols-2">
            <div><dt class="font-medium">Versi instrumen</dt><dd>{{ $period->instrument_version }}</dd></div>
            <div><dt class="font-medium">Instrumen diverifikasi</dt><dd>{{ $period->instrument_verified_at?->format('d M Y H:i') ?? 'Belum diverifikasi' }}</dd></div>
            <div><dt class="font-medium">Konfigurasi dikunci</dt><dd>{{ $period->configuration_locked_at?->format('d M Y H:i') ?? 'Belum dikunci' }}</dd></div>
            <div><dt class="font-medium">Benchmark terverifikasi</dt><dd>{{ $benchmarks->whereNotNull('verified_at')->count() }} dari 6</dd></div>
        </dl>
        @if ($isDraft)
            <flux:button wire:click="verifyInstrument" variant="filled">Verifikasi instrumen</flux:button>
        @endif
        @error('instrumentVerification')
            <flux:callout variant="danger" icon="exclamation-triangle">{{ $message }}</flux:callout>
        @enderror
        <ul class="divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
            @foreach ($benchmarks as $benchmark)
                <li class="flex justify-between gap-4 py-2.5 transition hover:bg-indigo-50/40 -mx-2 px-2 rounded-lg">
                    <span>{{ $benchmark->scale }}</span>
                    <span class="flex items-center gap-2">
                        {{ $benchmark->verified_at?->format('d M Y H:i') ?? 'Belum diverifikasi' }}
                        @if ($isDraft && ! $benchmark->verified_at)
                            <flux:button size="sm" wire:click="verifyBenchmark({{ $benchmark->id }})">Verifikasi</flux:button>
                        @endif
                    </span>
                </li>
            @endforeach
        </ul>
    </div>

    @if ($isDraft)
        <flux:button wire:click="activate" wire:confirm="Aktifkan periode ini? Konfigurasi tidak dapat diubah setelah aktivasi." variant="primary">
            Aktifkan dan kunci konfigurasi
        </flux:button>
    @endif

    @if ($period->status === \App\Domain\Study\PeriodStatus::Active)
        <flux:button wire:click="close" wire:confirm="Tutup periode ini?" variant="danger">Tutup periode</flux:button>
    @endif
</div>
