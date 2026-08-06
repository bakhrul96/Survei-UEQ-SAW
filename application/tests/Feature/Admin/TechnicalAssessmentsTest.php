<?php

use App\Livewire\Admin\TechnicalAssessments;
use App\Models\CriteriaWeight;
use App\Models\EvaluationPeriod;
use App\Models\EvaluationUnit;
use App\Models\TechnicalAssessment;
use App\Models\TechnicalInformant;
use App\Models\User;
use Livewire\Livewire;
use Tests\Support\ReleaseTwoFixture;

it('requires authentication to open technical assessments', function () {
    $this->get('/admin/technical-assessments')->assertRedirect('/login');
});

it('rejects a technical allocation that does not total one hundred points', function () {
    EvaluationPeriod::factory()->create();
    technicalUnits();
    $admin = User::factory()->create();

    Livewire::actingAs($admin)
        ->test(TechnicalAssessments::class)
        ->set('weights', ['c1' => 50, 'c2' => 20, 'c3' => 20])
        ->call('saveWeights')
        ->assertHasErrors(['weights' => 'total']);
});

it('saves one anonymous technical informant with all of their unit assessments and allocation', function () {
    $period = EvaluationPeriod::factory()->create();
    $units = technicalUnits();
    $admin = User::factory()->create();
    $assessments = $units->mapWithKeys(fn (EvaluationUnit $unit) => [$unit->id => [
        'days' => 2.5,
        'urgency' => 3,
    ]])->all();

    Livewire::actingAs($admin)
        ->test(TechnicalAssessments::class)
        ->set('anonymousCode', 'TEK-01')
        ->set('assessments', $assessments)
        ->set('weights', ['c1' => 50, 'c2' => 30, 'c3' => 20])
        ->call('save')
        ->assertHasNoErrors();

    $informant = TechnicalInformant::query()->where('evaluation_period_id', $period->id)->where('anonymous_code', 'TEK-01')->firstOrFail();

    expect(TechnicalAssessment::query()->where('technical_informant_id', $informant->id)->count())->toBe(13)
        ->and(CriteriaWeight::query()->where('technical_informant_id', $informant->id)->firstOrFail()->only(['c1_points', 'c2_points', 'c3_points']))
        ->toBe(['c1_points' => 50, 'c2_points' => 30, 'c3_points' => 20]);
});

it('validates every supplied technical assessment before it is saved', function () {
    EvaluationPeriod::factory()->create();
    $units = technicalUnits();
    $admin = User::factory()->create();
    $assessments = $units->mapWithKeys(fn (EvaluationUnit $unit) => [$unit->id => [
        'days' => 1,
        'urgency' => 3,
    ]])->all();
    $assessments[$units->first()->id] = ['days' => 0, 'urgency' => 6];

    Livewire::actingAs($admin)
        ->test(TechnicalAssessments::class)
        ->set('anonymousCode', 'TEK-01')
        ->set('assessments', $assessments)
        ->set('weights', ['c1' => 50, 'c2' => 30, 'c3' => 20])
        ->call('save')
        ->assertHasErrors([
            'assessments.'.$units->first()->id.'.days' => 'gt',
            'assessments.'.$units->first()->id.'.urgency' => 'between',
        ]);
});

it('updates an existing anonymous code instead of creating a second informant', function () {
    $period = EvaluationPeriod::factory()->create();
    $units = technicalUnits();
    $admin = User::factory()->create();
    $assessments = $units->mapWithKeys(fn (EvaluationUnit $unit) => [$unit->id => ['days' => 1, 'urgency' => 1]])->all();

    Livewire::actingAs($admin)
        ->test(TechnicalAssessments::class)
        ->set('anonymousCode', 'TEK-01')
        ->set('assessments', $assessments)
        ->set('weights', ['c1' => 40, 'c2' => 30, 'c3' => 30])
        ->call('save');

    Livewire::actingAs($admin)
        ->test(TechnicalAssessments::class)
        ->set('anonymousCode', 'TEK-01')
        ->set('assessments', $assessments)
        ->set('weights', ['c1' => 50, 'c2' => 30, 'c3' => 20])
        ->call('save')
        ->assertHasNoErrors();

    expect(TechnicalInformant::query()->where('evaluation_period_id', $period->id)->where('anonymous_code', 'TEK-01')->count())->toBe(1);
});

it('rejects assessment keys outside the fixed Wong Reang units', function () {
    EvaluationPeriod::factory()->create();
    $units = technicalUnits();
    $extra = EvaluationUnit::factory()->create(['display_order' => 14, 'is_active' => true]);
    $admin = User::factory()->create();
    $assessments = $units->mapWithKeys(fn (EvaluationUnit $unit) => [$unit->id => ['days' => 1, 'urgency' => 1]])->all();
    $assessments[$extra->id] = ['days' => 1, 'urgency' => 1];

    Livewire::actingAs($admin)
        ->test(TechnicalAssessments::class)
        ->set('anonymousCode', 'TEK-01')
        ->set('assessments', $assessments)
        ->set('weights', ['c1' => 50, 'c2' => 30, 'c3' => 20])
        ->call('save')
        ->assertHasErrors('assessments');
});

it('renders informant completeness and per-unit consensus evidence', function () {
    $period = EvaluationPeriod::factory()->create();
    technicalUnits();
    $admin = User::factory()->create();
    ReleaseTwoFixture::seedInformants($period, $admin, 3);

    Livewire::actingAs($admin)
        ->test(TechnicalAssessments::class)
        ->assertSee('3 informan')
        ->assertSee('Lengkap')
        ->assertSee('SD hari')
        ->assertSee('SD urgensi')
        ->assertDontSee('anonymous_respondent_id')
        ->assertDontSee('token_hash')
        ->assertDontSee('raw_score')
        ->assertDontSee('user_agent');
});

function technicalUnits()
{
    return collect(EvaluationUnit::WONG_REANG_CODES)->values()->map(fn (string $code, int $index) => EvaluationUnit::factory()->create([
        'code' => $code,
        'name' => str($code)->replace('-yu', '')->headline()->append('-Yu')->toString(),
        'display_order' => $index + 1,
    ]));
}
