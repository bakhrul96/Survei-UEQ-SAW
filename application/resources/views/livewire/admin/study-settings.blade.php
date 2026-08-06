<div class="mx-auto w-full max-w-4xl space-y-6">
    <div>
        <flux:heading size="xl">Pengaturan Studi</flux:heading>
        <flux:text>{{ $period->name }} · Status: {{ $period->status->value }}</flux:text>
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

    <flux:card>
        <flux:heading size="lg">Kesiapan aktivasi</flux:heading>
        @if ($issues === [])
            <flux:text class="mt-2">Semua syarat aktivasi telah terpenuhi.</flux:text>
        @else
            <ul class="mt-3 list-disc space-y-1 ps-5 text-sm text-red-700 dark:text-red-300">
                @foreach ($issues as $issue)
                    <li>{{ $issue }}</li>
                @endforeach
            </ul>
        @endif
    </flux:card>

    <form wire:submit="save" class="space-y-6">
        <flux:card class="space-y-4">
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

            @if ($isDraft)
                <flux:button type="submit" variant="primary">Simpan konfigurasi</flux:button>
            @else
                <flux:callout variant="warning" icon="lock-closed">Konfigurasi terkunci sejak {{ $period->configuration_locked_at?->format('d M Y H:i') }}.</flux:callout>
            @endif
        </flux:card>
    </form>

    <flux:card class="space-y-3">
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
                <li class="flex justify-between gap-4 py-2">
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
    </flux:card>

    @if ($isDraft)
        <flux:button wire:click="activate" wire:confirm="Aktifkan periode ini? Konfigurasi tidak dapat diubah setelah aktivasi." variant="primary">
            Aktifkan dan kunci konfigurasi
        </flux:button>
    @endif

    @if ($period->status === \App\Domain\Study\PeriodStatus::Active)
        <flux:button wire:click="close" wire:confirm="Tutup periode ini?" variant="danger">Tutup periode</flux:button>
    @endif
</div>
