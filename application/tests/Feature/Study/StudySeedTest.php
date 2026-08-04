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

    expect(EvaluationPeriod::query()->get(['slug', 'name', 'status'])->map(fn (EvaluationPeriod $period) => [
        $period->slug,
        $period->name,
        $period->status,
    ])->all())->toBe([
        ['wong-reang-2026', 'Evaluasi Wong Reang Apps 2026', PeriodStatus::Draft],
    ]);

    expect(EvaluationUnit::query()->orderBy('display_order')->get(['code', 'name', 'display_order'])->map(
        fn (EvaluationUnit $unit) => [$unit->code, $unit->name, $unit->display_order],
    )->all())->toBe([
        ['ibadah-yu', 'Ibadah-Yu', 1],
        ['info-yu', 'Info-Yu', 2],
        ['dumas-yu', 'Dumas-Yu', 3],
        ['sekolah-yu', 'Sekolah-Yu', 4],
        ['sehat-yu', 'Sehat-Yu', 5],
        ['pasar-yu', 'Pasar-Yu', 6],
        ['adminduk-yu', 'Adminduk-Yu', 7],
        ['kerja-yu', 'Kerja-Yu', 8],
        ['renbang-yu', 'Renbang-Yu', 9],
        ['izin-yu', 'Izin-Yu', 10],
        ['pajak-yu', 'Pajak-Yu', 11],
        ['plesir-yu', 'Plesir-Yu', 12],
        ['wifi-yu', 'WiFi-Yu', 13],
    ]);

    expect(UeqItem::query()->orderBy('order')->get([
        'order', 'left_label', 'right_label', 'scale', 'positive_pole',
    ])->map(fn (UeqItem $item) => [
        $item->order,
        $item->left_label,
        $item->right_label,
        $item->scale,
        $item->positive_pole,
    ])->all())->toBe([
        [1, 'menyusahkan', 'menyenangkan', 'Attractiveness', 'right'],
        [2, 'tak dapat dipahami', 'dapat dipahami', 'Perspicuity', 'right'],
        [3, 'kreatif', 'monoton', 'Novelty', 'left'],
        [4, 'mudah dipelajari', 'sulit dipelajari', 'Perspicuity', 'left'],
        [5, 'bermanfaat', 'kurang bermanfaat', 'Stimulation', 'left'],
        [6, 'membosankan', 'mengasyikkan', 'Stimulation', 'right'],
        [7, 'tidak menarik', 'menarik', 'Stimulation', 'right'],
        [8, 'tak dapat diprediksi', 'dapat diprediksi', 'Dependability', 'right'],
        [9, 'cepat', 'lambat', 'Efficiency', 'left'],
        [10, 'berdaya cipta', 'konvensional', 'Novelty', 'left'],
        [11, 'menghalangi', 'mendukung', 'Dependability', 'right'],
        [12, 'baik', 'buruk', 'Attractiveness', 'left'],
        [13, 'rumit', 'sederhana', 'Perspicuity', 'right'],
        [14, 'tidak disukai', 'menggembirakan', 'Attractiveness', 'right'],
        [15, 'lazim', 'terdepan', 'Novelty', 'right'],
        [16, 'tidak nyaman', 'nyaman', 'Attractiveness', 'right'],
        [17, 'aman', 'tidak aman', 'Dependability', 'left'],
        [18, 'memotivasi', 'tidak memotivasi', 'Stimulation', 'left'],
        [19, 'memenuhi ekspektasi', 'tidak memenuhi ekspektasi', 'Dependability', 'left'],
        [20, 'tidak efisien', 'efisien', 'Efficiency', 'right'],
        [21, 'jelas', 'membingungkan', 'Perspicuity', 'left'],
        [22, 'tidak praktis', 'praktis', 'Efficiency', 'right'],
        [23, 'terorganisasi', 'berantakan', 'Efficiency', 'left'],
        [24, 'atraktif', 'tidak atraktif', 'Attractiveness', 'left'],
        [25, 'ramah pengguna', 'tidak ramah pengguna', 'Attractiveness', 'left'],
        [26, 'konservatif', 'inovatif', 'Novelty', 'right'],
    ]);

    expect(UeqBenchmark::query()->orderBy('scale')->get([
        'scale', 'good_threshold', 'source', 'verified_at',
    ])->map(fn (UeqBenchmark $benchmark) => [
        $benchmark->scale,
        $benchmark->good_threshold,
        $benchmark->source,
        $benchmark->verified_at,
    ])->all())->toBe([
        ['Attractiveness', '1.5800', 'Bab III TA Bakhrul Ullum 2026; verifikasi UEQ source wajib sebelum aktivasi', null],
        ['Dependability', '1.4800', 'Bab III TA Bakhrul Ullum 2026; verifikasi UEQ source wajib sebelum aktivasi', null],
        ['Efficiency', '1.5000', 'Bab III TA Bakhrul Ullum 2026; verifikasi UEQ source wajib sebelum aktivasi', null],
        ['Novelty', '1.1200', 'Bab III TA Bakhrul Ullum 2026; verifikasi UEQ source wajib sebelum aktivasi', null],
        ['Perspicuity', '1.7300', 'Bab III TA Bakhrul Ullum 2026; verifikasi UEQ source wajib sebelum aktivasi', null],
        ['Stimulation', '1.3500', 'Bab III TA Bakhrul Ullum 2026; verifikasi UEQ source wajib sebelum aktivasi', null],
    ]);
});
