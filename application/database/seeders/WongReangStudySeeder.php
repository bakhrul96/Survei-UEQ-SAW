<?php

namespace Database\Seeders;

use App\Domain\Study\PeriodStatus;
use App\Models\EvaluationPeriod;
use App\Models\EvaluationUnit;
use App\Models\UeqBenchmark;
use App\Models\UeqItem;
use Illuminate\Database\Seeder;

class WongReangStudySeeder extends Seeder
{
    public function run(): void
    {
        $period = EvaluationPeriod::query()->firstOrCreate(
            ['slug' => 'wong-reang-2026'],
            [
                'name' => 'Evaluasi Wong Reang Apps 2026',
                'status' => PeriodStatus::Draft,
                'minimum_age' => 17,
                'minimum_per_unit' => 20,
                'target_per_unit' => 30,
                'target_basis' => 'Usulan awal minimum 20 dan target 30 evaluasi valid per modul; nilai final mengikuti Bab II dan persetujuan pembimbing.',
                'consent_text' => 'Penelitian ini mengevaluasi pengalaman pengguna pada modul Wong Reang Apps untuk menentukan prioritas perbaikan layanan.',
                'consent_data_description' => 'Jawaban UEQ mentah, waktu pengisian, urutan modul, dan profil kelayakan tanpa nama disimpan untuk analisis penelitian.',
                'consent_cookie_description' => 'Cookie anonim digunakan untuk mencegah modul yang sama dinilai dua kali.',
                'consent_estimated_minutes' => 10,
                'consent_withdrawal_description' => 'Partisipasi bersifat sukarela dan dapat dihentikan sebelum jawaban dikirim.',
                'research_contact' => 'peneliti@example.test',
                'fast_response_seconds' => 120,
                'quality_rules_version' => 'quality-rules-v1',
                'identical_answers_flag_enabled' => true,
                'instrument_version' => 'UEQ-ID-26-v1',
                'sensitivity_s1_c1' => 0.600000,
                'sensitivity_s1_c2' => 0.200000,
                'sensitivity_s1_c3' => 0.200000,
                'sensitivity_s2_c1' => 0.200000,
                'sensitivity_s2_c2' => 0.400000,
                'sensitivity_s2_c3' => 0.400000,
            ],
        );

        $units = [
            ['ibadah-yu', 'Ibadah-Yu'], ['info-yu', 'Info-Yu'],
            ['dumas-yu', 'Dumas-Yu'], ['sekolah-yu', 'Sekolah-Yu'],
            ['sehat-yu', 'Sehat-Yu'], ['pasar-yu', 'Pasar-Yu'],
            ['adminduk-yu', 'Adminduk-Yu'], ['kerja-yu', 'Kerja-Yu'],
            ['renbang-yu', 'Renbang-Yu'], ['izin-yu', 'Izin-Yu'],
            ['pajak-yu', 'Pajak-Yu'], ['plesir-yu', 'Plesir-Yu'],
            ['wifi-yu', 'WiFi-Yu'],
        ];

        foreach ($units as $index => [$code, $name]) {
            EvaluationUnit::query()->firstOrCreate(
                ['code' => $code],
                ['name' => $name, 'display_order' => $index + 1, 'is_active' => true],
            );
        }

        $items = [
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
        ];

        foreach ($items as [$order, $leftLabel, $rightLabel, $scale, $positivePole]) {
            UeqItem::query()->firstOrCreate(
                ['version' => $period->instrument_version, 'order' => $order],
                [
                    'left_label' => $leftLabel,
                    'right_label' => $rightLabel,
                    'scale' => $scale,
                    'positive_pole' => $positivePole,
                ],
            );
        }

        $benchmarks = [
            'Attractiveness' => 1.58,
            'Perspicuity' => 1.73,
            'Efficiency' => 1.50,
            'Dependability' => 1.48,
            'Stimulation' => 1.35,
            'Novelty' => 1.12,
        ];

        foreach ($benchmarks as $scale => $goodThreshold) {
            UeqBenchmark::query()->firstOrCreate(
                ['version' => $period->instrument_version, 'scale' => $scale],
                [
                    'good_threshold' => $goodThreshold,
                    'source' => 'Bab III TA Bakhrul Ullum 2026; verifikasi UEQ source wajib sebelum aktivasi',
                    'verified_at' => null,
                ],
            );
        }
    }
}
