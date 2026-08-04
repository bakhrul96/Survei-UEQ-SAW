<?php

namespace App\Livewire\Survey;

use App\Application\Survey\StartSurveySession;
use App\Domain\Study\PeriodStatus;
use App\Domain\Survey\SurveyContext;
use App\Models\AnonymousRespondent;
use App\Models\EvaluationPeriod;
use App\Models\EvaluationUnit;
use App\Models\RespondentProfile;
use App\Models\SurveySubmission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Component;

class UnitChooser extends Component
{
    public EvaluationPeriod $period;

    /** @var Collection<int, EvaluationUnit> */
    public Collection $units;

    /** @var array<int> */
    public array $completedUnitIds = [];

    public function mount(EvaluationPeriod $period, SurveyContext $context): void
    {
        abort_unless($period->status === PeriodStatus::Active, 404);

        $this->period = $period;
        $respondent = $context->respondent();
        $this->ensureEligible($respondent);
        $this->loadUnits($respondent);
    }

    public function choose(int $unitId, SurveyContext $context, StartSurveySession $startSession): RedirectResponse
    {
        $period = EvaluationPeriod::query()->findOrFail($this->period->id);
        abort_unless($period->status === PeriodStatus::Active, 404);
        $this->period = $period;

        $respondent = $context->respondent();
        $this->ensureEligible($respondent);

        $unit = EvaluationUnit::query()->whereKey($unitId)->where('is_active', true)->first();
        abort_unless($unit instanceof EvaluationUnit, 403);

        $submitted = SurveySubmission::query()
            ->where('evaluation_period_id', $period->id)
            ->where('anonymous_respondent_id', $respondent->id)
            ->where('evaluation_unit_id', $unit->id)
            ->where('status', 'submitted')
            ->exists();
        abort_if($submitted, 403);

        $startSession->handle($period, $respondent);

        return redirect()->route('survey.wizard', ['period' => $period, 'unit' => $unit->code]);
    }

    public function render(): View
    {
        return view('livewire.survey.unit-chooser')
            ->layout('layouts.app', ['title' => 'Pilih Modul Layanan']);
    }

    private function ensureEligible(AnonymousRespondent $respondent): void
    {
        $eligible = RespondentProfile::query()
            ->where('evaluation_period_id', $this->period->id)
            ->where('anonymous_respondent_id', $respondent->id)
            ->where('eligible', true)
            ->exists();

        abort_unless($eligible, 403);
    }

    private function loadUnits(AnonymousRespondent $respondent): void
    {
        $this->units = EvaluationUnit::query()
            ->where('is_active', true)
            ->orderBy('display_order')
            ->get();
        $this->completedUnitIds = SurveySubmission::query()
            ->where('evaluation_period_id', $this->period->id)
            ->where('anonymous_respondent_id', $respondent->id)
            ->where('status', 'submitted')
            ->pluck('evaluation_unit_id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();
    }
}
