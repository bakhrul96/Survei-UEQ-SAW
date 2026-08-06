<?php

use App\Application\Survey\SubmitSurvey;
use App\Application\Survey\SubmitSurveyData;
use App\Domain\Quality\QualityDecision;
use App\Domain\Survey\SurveyTokenService;
use App\Livewire\Admin\Calculations;
use App\Models\EvaluationUnit;
use App\Models\QualityReview;
use App\Models\RespondentProfile;
use App\Models\SurveySession;
use App\Models\TechnicalAssessment;
use App\Models\TechnicalInformant;
use App\Models\UeqBenchmark;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(WongReangStudySeeder::class);
    $this->admin = User::factory()->create();
    $this->fixture1 = surveyFixture();
    $this->period = $this->fixture1->period;

    UeqBenchmark::query()->update([
        'source' => 'Acuan Test',
        'verified_at' => now(),
    ]);

    foreach (['Attractiveness', 'Perspicuity', 'Efficiency', 'Dependability', 'Stimulation', 'Novelty'] as $scale) {
        UeqBenchmark::query()->updateOrCreate(
            ['version' => $this->period->instrument_version, 'scale' => $scale],
            ['good_threshold' => 1.50, 'source' => 'Acuan Test', 'verified_at' => now()]
        );
    }

    $unit1 = $this->fixture1->unit;
    $unit2 = EvaluationUnit::factory()->create(['code' => 'unit-two', 'display_order' => 999]);
    $this->period = lockStudyConfiguration($this->period);

    $issued2 = app(SurveyTokenService::class)->issue();
    RespondentProfile::factory()->create([
        'evaluation_period_id' => $this->period->id,
        'anonymous_respondent_id' => $issued2->respondent->id,
        'eligible' => true,
    ]);
    $session2 = SurveySession::factory()->create([
        'evaluation_period_id' => $this->period->id,
        'anonymous_respondent_id' => $issued2->respondent->id,
    ]);

    // Varied answers so total variance > 0 for all 6 scales
    $answersA = [
        1 => 6, 2 => 5, 3 => 2, 4 => 3, 5 => 2, 6 => 6, 7 => 6, 8 => 5, 9 => 2, 10 => 2,
        11 => 6, 12 => 2, 13 => 5, 14 => 5, 15 => 5, 16 => 5, 17 => 3, 18 => 3, 19 => 3, 20 => 6,
        21 => 3, 22 => 5, 23 => 3, 24 => 2, 25 => 3, 26 => 5,
    ];
    $answersB = [
        1 => 4, 2 => 6, 3 => 4, 4 => 4, 5 => 4, 6 => 4, 7 => 4, 8 => 3, 9 => 4, 10 => 4,
        11 => 4, 12 => 4, 13 => 4, 14 => 4, 15 => 4, 16 => 4, 17 => 4, 18 => 4, 19 => 4, 20 => 4,
        21 => 4, 22 => 4, 23 => 4, 24 => 4, 25 => 4, 26 => 4,
    ];

    $data1 = new SubmitSurveyData($this->period->id, $this->fixture1->respondent->id, $this->fixture1->session->id, $unit1->id, (string) Str::uuid(), $this->period->instrument_version, CarbonImmutable::now(), $answersA);
    $data2 = new SubmitSurveyData($this->period->id, $issued2->respondent->id, $session2->id, $unit1->id, (string) Str::uuid(), $this->period->instrument_version, CarbonImmutable::now(), $answersB);
    $data3 = new SubmitSurveyData($this->period->id, $this->fixture1->respondent->id, $this->fixture1->session->id, $unit2->id, (string) Str::uuid(), $this->period->instrument_version, CarbonImmutable::now(), $answersA);
    $data4 = new SubmitSurveyData($this->period->id, $issued2->respondent->id, $session2->id, $unit2->id, (string) Str::uuid(), $this->period->instrument_version, CarbonImmutable::now(), $answersB);

    $sub1 = app(SubmitSurvey::class)->handle($data1);
    $sub2 = app(SubmitSurvey::class)->handle($data2);
    $sub3 = app(SubmitSurvey::class)->handle($data3);
    $sub4 = app(SubmitSurvey::class)->handle($data4);

    // Mark all submissions as QualityDecision::Included
    foreach ([$sub1, $sub2, $sub3, $sub4] as $submission) {
        QualityReview::query()->create([
            'survey_submission_id' => $submission->id,
            'flags' => ['fast_completion' => false, 'identical_answers' => false],
            'decision' => QualityDecision::Included,
            'reviewed_by' => $this->admin->id,
            'reviewed_at' => now(),
        ]);
    }

    // Create technical informants
    $informant1 = TechnicalInformant::query()->create(['evaluation_period_id' => $this->period->id, 'anonymous_code' => 'TI-01']);
    $informant2 = TechnicalInformant::query()->create(['evaluation_period_id' => $this->period->id, 'anonymous_code' => 'TI-02']);
    $informant1->criteriaWeight()->create(['c1_points' => 34, 'c2_points' => 33, 'c3_points' => 33]);
    $informant2->criteriaWeight()->create(['c1_points' => 34, 'c2_points' => 33, 'c3_points' => 33]);

    foreach ([$unit1, $unit2] as $unit) {
        TechnicalAssessment::query()->create(['technical_informant_id' => $informant1->id, 'evaluation_unit_id' => $unit->id, 'estimated_days' => 5, 'architecture_urgency' => 3]);
        TechnicalAssessment::query()->create(['technical_informant_id' => $informant2->id, 'evaluation_unit_id' => $unit->id, 'estimated_days' => 6, 'architecture_urgency' => 4]);
    }
});

it('protects the calculation preview page', function (): void {
    $this->get('/admin/calculations')->assertRedirect('/login');
});

it('renders the calculation controls and release three features', function (): void {
    Livewire::actingAs($this->admin)
        ->test(Calculations::class, ['periodId' => $this->period->id])
        ->assertSee('Jalankan preview')
        ->call('runPreview')
        ->assertSee('Kunci Hasil Resmi (Official)')
        ->assertSee('Analisis Sensitivitas Peringkat (S0 vs S1 vs S2)')
        ->assertSee('Expert Judgment & Backlog Operasional');
});

it('allows locking a calculation run as official', function (): void {
    Livewire::actingAs($this->admin)
        ->test(Calculations::class, ['periodId' => $this->period->id])
        ->call('runPreview')
        ->call('lockOfficial')
        ->assertSee('OFFICIAL / LOCKED');
});
