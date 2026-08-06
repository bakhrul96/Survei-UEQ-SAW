<?php

use App\Domain\Study\PeriodStatus;
use App\Domain\Survey\SurveyTokenService;
use App\Livewire\Survey\ConsentScreener;
use App\Models\EvaluationPeriod;
use App\Models\RespondentProfile;
use Livewire\Livewire;

it('renders survey entry and consent for an unauthenticated respondent', function () {
    $period = EvaluationPeriod::factory()->create(['status' => PeriodStatus::Active, 'configuration_locked_at' => now()]);
    $period = lockStudyConfiguration($period);

    $this->get(route('survey.entry', $period))
        ->assertRedirect(route('survey.consent', $period));

    $this->get(route('survey.consent', $period))
        ->assertOk()
        ->assertSee('Informasi Penelitian')
        ->assertDontSee('Dashboard');

    $this->get(route('survey.ineligible', $period))
        ->assertOk()
        ->assertSee('Anda belum memenuhi kriteria');
});

it('renders every required research consent element', function () {
    $period = EvaluationPeriod::factory()->create(['status' => PeriodStatus::Active, 'configuration_locked_at' => now()]);
    $period->forceFill([
        'consent_text' => 'Tujuan penelitian adalah mengevaluasi pengalaman pengguna Wong Reang.',
        'consent_data_description' => 'Data yang disimpan adalah jawaban UEQ dan metadata pengisian.',
        'consent_cookie_description' => 'Cookie anonim digunakan untuk mencegah duplikasi.',
        'consent_estimated_minutes' => 10,
        'consent_withdrawal_description' => 'Anda dapat berhenti sebelum mengirim jawaban.',
        'research_contact' => 'peneliti@example.test',
    ])->save();
    $period = lockStudyConfiguration($period);

    Livewire::test(ConsentScreener::class, ['period' => $period])
        ->assertSee('Tujuan penelitian adalah mengevaluasi pengalaman pengguna Wong Reang.')
        ->assertSee('Data yang disimpan adalah jawaban UEQ dan metadata pengisian.')
        ->assertSee('Cookie anonim digunakan untuk mencegah duplikasi.')
        ->assertSee('10 menit')
        ->assertSee('Anda dapat berhenti sebelum mengirim jawaban.')
        ->assertSee('peneliti@example.test');
});

it('stores consent and allows only eligible respondents', function () {
    $period = EvaluationPeriod::factory()->create(['status' => PeriodStatus::Active, 'minimum_age' => 17, 'configuration_locked_at' => now()]);
    $period = lockStudyConfiguration($period);
    $issued = app(SurveyTokenService::class)->issue();

    Livewire::withCookie('ueq_survey_token', $issued->plainToken)
        ->test(ConsentScreener::class, ['period' => $period])
        ->set('consent', true)
        ->set('age', 20)
        ->set('isIndramayuResident', true)
        ->set('hasUsedWongReang', true)
        ->call('submit')
        ->assertRedirect(route('survey.units', $period));

    expect(RespondentProfile::firstOrFail()->eligible)->toBeTrue();
});

it('rejects screening when an active period closes after the component mounts', function () {
    $period = EvaluationPeriod::factory()->create(['status' => PeriodStatus::Active, 'configuration_locked_at' => now()]);
    $period = lockStudyConfiguration($period);
    $issued = app(SurveyTokenService::class)->issue();

    $component = Livewire::withCookie('ueq_survey_token', $issued->plainToken)
        ->test(ConsentScreener::class, ['period' => $period]);

    $period->update(['status' => PeriodStatus::Closed]);

    $component->set('consent', true)
        ->set('age', 20)
        ->set('isIndramayuResident', true)
        ->set('hasUsedWongReang', true)
        ->call('submit')
        ->assertNotFound();

    expect(RespondentProfile::count())->toBe(0);
});

it('does not store a profile when consent or age is invalid', function () {
    $period = EvaluationPeriod::factory()->create(['status' => PeriodStatus::Active, 'configuration_locked_at' => now()]);
    $period = lockStudyConfiguration($period);
    $issued = app(SurveyTokenService::class)->issue();

    Livewire::withCookie('ueq_survey_token', $issued->plainToken)
        ->test(ConsentScreener::class, ['period' => $period])
        ->set('consent', false)
        ->set('age', 16)
        ->set('isIndramayuResident', true)
        ->set('hasUsedWongReang', true)
        ->call('submit')
        ->assertHasErrors(['consent', 'age']);

    expect(RespondentProfile::count())->toBe(0);
});

it('stores an ineligible profile and redirects to the ineligible page', function () {
    $period = EvaluationPeriod::factory()->create(['status' => PeriodStatus::Active, 'configuration_locked_at' => now()]);
    $period = lockStudyConfiguration($period);
    $issued = app(SurveyTokenService::class)->issue();

    Livewire::withCookie('ueq_survey_token', $issued->plainToken)
        ->test(ConsentScreener::class, ['period' => $period])
        ->set('consent', true)
        ->set('age', 20)
        ->set('isIndramayuResident', false)
        ->set('hasUsedWongReang', true)
        ->call('submit')
        ->assertRedirect(route('survey.ineligible', $period));

    expect(RespondentProfile::firstOrFail()->eligible)->toBeFalse();
});

it('stores eligibility only once when an ineligible respondent resubmits the screener', function () {
    $period = EvaluationPeriod::factory()->create(['status' => PeriodStatus::Active, 'configuration_locked_at' => now()]);
    $period = lockStudyConfiguration($period);
    $issued = app(SurveyTokenService::class)->issue();

    Livewire::withCookie('ueq_survey_token', $issued->plainToken)
        ->test(ConsentScreener::class, ['period' => $period])
        ->set('consent', true)
        ->set('age', 20)
        ->set('isIndramayuResident', false)
        ->set('hasUsedWongReang', true)
        ->call('submit')
        ->assertRedirect(route('survey.ineligible', $period));

    $original = RespondentProfile::firstOrFail()->getAttributes();

    Livewire::withCookie('ueq_survey_token', $issued->plainToken)
        ->test(ConsentScreener::class, ['period' => $period])
        ->set('consent', true)
        ->set('age', 30)
        ->set('isIndramayuResident', true)
        ->set('hasUsedWongReang', true)
        ->call('submit')
        ->assertRedirect(route('survey.ineligible', $period));

    expect(RespondentProfile::count())->toBe(1)
        ->and(RespondentProfile::firstOrFail()->getAttributes())->toBe($original)
        ->and(RespondentProfile::firstOrFail()->eligible)->toBeFalse();
});

it('does not let an eligible respondent replace the original screener result', function () {
    $period = EvaluationPeriod::factory()->create(['status' => PeriodStatus::Active, 'configuration_locked_at' => now()]);
    $period = lockStudyConfiguration($period);
    $issued = app(SurveyTokenService::class)->issue();

    Livewire::withCookie('ueq_survey_token', $issued->plainToken)
        ->test(ConsentScreener::class, ['period' => $period])
        ->set('consent', true)
        ->set('age', 20)
        ->set('isIndramayuResident', true)
        ->set('hasUsedWongReang', true)
        ->call('submit')
        ->assertRedirect(route('survey.units', $period));

    Livewire::withCookie('ueq_survey_token', $issued->plainToken)
        ->test(ConsentScreener::class, ['period' => $period])
        ->set('consent', true)
        ->set('age', 20)
        ->set('isIndramayuResident', false)
        ->set('hasUsedWongReang', false)
        ->call('submit')
        ->assertRedirect(route('survey.units', $period));

    $profile = RespondentProfile::firstOrFail();

    expect($profile->eligible)->toBeTrue()
        ->and($profile->is_indramayu_resident)->toBeTrue()
        ->and($profile->has_used_wong_reang)->toBeTrue();
});

it('renders large easy-to-tap consent checkboxes bound to their models', function () {
    $period = EvaluationPeriod::factory()->create(['status' => PeriodStatus::Active, 'configuration_locked_at' => now()]);
    $period = lockStudyConfiguration($period);

    Livewire::test(ConsentScreener::class, ['period' => $period])
        // Ketiga persetujuan memakai kartu tap-target besar (min-h-11) dengan checkbox nyata yang terikat model.
        ->assertSee('min-h-11', escape: false)
        ->assertSee('wire:model.live="consent"', escape: false)
        ->assertSee('wire:model.live="isIndramayuResident"', escape: false)
        ->assertSee('wire:model.live="hasUsedWongReang"', escape: false);
});

it('presents the consent page as a reassuring landing with trust badges', function () {
    $period = EvaluationPeriod::factory()->create(['status' => PeriodStatus::Active, 'configuration_locked_at' => now()]);
    $period = lockStudyConfiguration($period);

    Livewire::test(ConsentScreener::class, ['period' => $period])
        ->assertSee('Anonim')
        ->assertSee('26 pertanyaan')
        ->assertSee('Tanpa nama')
        ->assertSee('Hanya modul yang pernah Anda gunakan');
});
