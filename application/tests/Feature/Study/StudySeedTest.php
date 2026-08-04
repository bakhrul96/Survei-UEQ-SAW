<?php

use App\Domain\Study\PeriodStatus;
use App\Models\EvaluationPeriod;
use App\Models\EvaluationUnit;
use App\Models\UeqBenchmark;
use App\Models\UeqItem;
use Database\Seeders\DatabaseSeeder;

it('seeds the exact fixed Wong Reang study through the database seeder', function () {
    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    expect(EvaluationPeriod::query()->get([
        'slug', 'name', 'status', 'minimum_age', 'minimum_per_unit', 'target_per_unit',
        'target_basis', 'consent_text', 'fast_response_seconds', 'instrument_version',
        'opens_at', 'closes_at', 'instrument_source', 'instrument_verified_at', 'configuration_locked_at',
    ])->map(fn (EvaluationPeriod $period) => [
        $period->slug,
        $period->name,
        $period->status,
        $period->minimum_age,
        $period->minimum_per_unit,
        $period->target_per_unit,
        $period->target_basis,
        $period->consent_text,
        $period->fast_response_seconds,
        $period->instrument_version,
        $period->opens_at,
        $period->closes_at,
        $period->instrument_source,
        $period->instrument_verified_at,
        $period->configuration_locked_at,
    ])->all())->toBe([
        [
            'wong-reang-2026',
            'Evaluasi Wong Reang Apps 2026',
            PeriodStatus::Draft,
            17,
            20,
            30,
            'Usulan awal minimum 20 dan target 30 evaluasi valid per modul; nilai final mengikuti Bab II dan persetujuan pembimbing.',
            'Saya telah membaca informasi penelitian, memahami bahwa partisipasi bersifat sukarela, menyetujui penyimpanan jawaban UEQ dan cookie anonim untuk mencegah pengisian ulang modul yang sama, serta dapat berhenti kapan saja sebelum mengirim jawaban.',
            120,
            'UEQ-ID-26-v1',
            null,
            null,
            null,
            null,
            null,
        ],
    ]);

    expect(EvaluationUnit::query()->orderBy('display_order')->get([
        'code', 'name', 'display_order', 'is_active',
    ])->map(
        fn (EvaluationUnit $unit) => [$unit->code, $unit->name, $unit->display_order, $unit->is_active],
    )->all())->toBe([
        ['ibadah-yu', 'Ibadah-Yu', 1, true],
        ['info-yu', 'Info-Yu', 2, true],
        ['dumas-yu', 'Dumas-Yu', 3, true],
        ['sekolah-yu', 'Sekolah-Yu', 4, true],
        ['sehat-yu', 'Sehat-Yu', 5, true],
        ['pasar-yu', 'Pasar-Yu', 6, true],
        ['adminduk-yu', 'Adminduk-Yu', 7, true],
        ['kerja-yu', 'Kerja-Yu', 8, true],
        ['renbang-yu', 'Renbang-Yu', 9, true],
        ['izin-yu', 'Izin-Yu', 10, true],
        ['pajak-yu', 'Pajak-Yu', 11, true],
        ['plesir-yu', 'Plesir-Yu', 12, true],
        ['wifi-yu', 'WiFi-Yu', 13, true],
    ]);

    expect(UeqItem::query()->orderBy('order')->get([
        'version', 'order', 'left_label', 'right_label', 'scale', 'positive_pole',
    ])->map(fn (UeqItem $item) => [
        $item->version,
        $item->order,
        $item->left_label,
        $item->right_label,
        $item->scale,
        $item->positive_pole,
    ])->all())->toBe([
        ['UEQ-ID-26-v1', 1, 'menyusahkan', 'menyenangkan', 'Attractiveness', 'right'],
        ['UEQ-ID-26-v1', 2, 'tak dapat dipahami', 'dapat dipahami', 'Perspicuity', 'right'],
        ['UEQ-ID-26-v1', 3, 'kreatif', 'monoton', 'Novelty', 'left'],
        ['UEQ-ID-26-v1', 4, 'mudah dipelajari', 'sulit dipelajari', 'Perspicuity', 'left'],
        ['UEQ-ID-26-v1', 5, 'bermanfaat', 'kurang bermanfaat', 'Stimulation', 'left'],
        ['UEQ-ID-26-v1', 6, 'membosankan', 'mengasyikkan', 'Stimulation', 'right'],
        ['UEQ-ID-26-v1', 7, 'tidak menarik', 'menarik', 'Stimulation', 'right'],
        ['UEQ-ID-26-v1', 8, 'tak dapat diprediksi', 'dapat diprediksi', 'Dependability', 'right'],
        ['UEQ-ID-26-v1', 9, 'cepat', 'lambat', 'Efficiency', 'left'],
        ['UEQ-ID-26-v1', 10, 'berdaya cipta', 'konvensional', 'Novelty', 'left'],
        ['UEQ-ID-26-v1', 11, 'menghalangi', 'mendukung', 'Dependability', 'right'],
        ['UEQ-ID-26-v1', 12, 'baik', 'buruk', 'Attractiveness', 'left'],
        ['UEQ-ID-26-v1', 13, 'rumit', 'sederhana', 'Perspicuity', 'right'],
        ['UEQ-ID-26-v1', 14, 'tidak disukai', 'menggembirakan', 'Attractiveness', 'right'],
        ['UEQ-ID-26-v1', 15, 'lazim', 'terdepan', 'Novelty', 'right'],
        ['UEQ-ID-26-v1', 16, 'tidak nyaman', 'nyaman', 'Attractiveness', 'right'],
        ['UEQ-ID-26-v1', 17, 'aman', 'tidak aman', 'Dependability', 'left'],
        ['UEQ-ID-26-v1', 18, 'memotivasi', 'tidak memotivasi', 'Stimulation', 'left'],
        ['UEQ-ID-26-v1', 19, 'memenuhi ekspektasi', 'tidak memenuhi ekspektasi', 'Dependability', 'left'],
        ['UEQ-ID-26-v1', 20, 'tidak efisien', 'efisien', 'Efficiency', 'right'],
        ['UEQ-ID-26-v1', 21, 'jelas', 'membingungkan', 'Perspicuity', 'left'],
        ['UEQ-ID-26-v1', 22, 'tidak praktis', 'praktis', 'Efficiency', 'right'],
        ['UEQ-ID-26-v1', 23, 'terorganisasi', 'berantakan', 'Efficiency', 'left'],
        ['UEQ-ID-26-v1', 24, 'atraktif', 'tidak atraktif', 'Attractiveness', 'left'],
        ['UEQ-ID-26-v1', 25, 'ramah pengguna', 'tidak ramah pengguna', 'Attractiveness', 'left'],
        ['UEQ-ID-26-v1', 26, 'konservatif', 'inovatif', 'Novelty', 'right'],
    ]);

    expect(UeqBenchmark::query()->orderBy('scale')->get([
        'version', 'scale', 'good_threshold', 'source', 'verified_at',
    ])->map(fn (UeqBenchmark $benchmark) => [
        $benchmark->version,
        $benchmark->scale,
        $benchmark->good_threshold,
        $benchmark->source,
        $benchmark->verified_at,
    ])->all())->toBe([
        ['UEQ-ID-26-v1', 'Attractiveness', '1.5800', 'Bab III TA Bakhrul Ullum 2026; verifikasi UEQ source wajib sebelum aktivasi', null],
        ['UEQ-ID-26-v1', 'Dependability', '1.4800', 'Bab III TA Bakhrul Ullum 2026; verifikasi UEQ source wajib sebelum aktivasi', null],
        ['UEQ-ID-26-v1', 'Efficiency', '1.5000', 'Bab III TA Bakhrul Ullum 2026; verifikasi UEQ source wajib sebelum aktivasi', null],
        ['UEQ-ID-26-v1', 'Novelty', '1.1200', 'Bab III TA Bakhrul Ullum 2026; verifikasi UEQ source wajib sebelum aktivasi', null],
        ['UEQ-ID-26-v1', 'Perspicuity', '1.7300', 'Bab III TA Bakhrul Ullum 2026; verifikasi UEQ source wajib sebelum aktivasi', null],
        ['UEQ-ID-26-v1', 'Stimulation', '1.3500', 'Bab III TA Bakhrul Ullum 2026; verifikasi UEQ source wajib sebelum aktivasi', null],
    ]);
});
