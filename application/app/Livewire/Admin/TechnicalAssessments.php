<?php

namespace App\Livewire\Admin;

use App\Domain\Technical\SaveTechnicalAssessment;
use App\Domain\Technical\TechnicalConsensus;
use App\Models\EvaluationPeriod;
use App\Models\EvaluationUnit;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use InvalidArgumentException;
use Livewire\Component;

class TechnicalAssessments extends Component
{
    public int $periodId;

    public string $anonymousCode = '';

    /** @var array<int, array{days: string|float|int|null, urgency: string|int|null}> */
    public array $assessments = [];

    /** @var array{c1: string|int, c2: string|int, c3: string|int} */
    public array $weights = ['c1' => '', 'c2' => '', 'c3' => ''];

    public function mount(): void
    {
        $this->periodId = EvaluationPeriod::query()->firstOrFail()->id;
        $this->assessments = $this->units()->mapWithKeys(fn (EvaluationUnit $unit) => [$unit->id => [
            'days' => null,
            'urgency' => null,
        ]])->all();
    }

    public function save(SaveTechnicalAssessment $saver): void
    {
        $this->validate($this->rules());

        $weights = $this->validatedWeights($this->weights);

        if ($weights === null) {
            return;
        }

        try {
            $saver->handle(
                $this->period(),
                trim($this->anonymousCode),
                $this->providedAssessments(),
                $weights,
            );
        } catch (InvalidArgumentException $exception) {
            $this->addError('assessments', $exception->getMessage());

            return;
        }

        session()->flash('status', 'Penilaian informan teknis disimpan.');
    }

    public function saveWeights(): void
    {
        $this->validate(['weights' => ['required', 'array'], ...$this->weightRules()]);
        $this->validatedWeights($this->weights);
    }

    public function render(TechnicalConsensus $consensus): View
    {
        $period = $this->period();

        return view('livewire.admin.technical-assessments', [
            'period' => $period,
            'units' => $this->units(),
            'consensus' => $consensus->for($period),
        ])->layout('layouts.app', ['title' => 'Penilaian Informan Teknis']);
    }

    /** @return array<string, list<string>> */
    private function rules(): array
    {
        return [
            'anonymousCode' => ['required', 'string', 'max:100'],
            'assessments' => ['required', 'array'],
            'assessments.*.days' => ['nullable', 'numeric', 'gt:0', 'required_with:assessments.*.urgency'],
            'assessments.*.urgency' => ['nullable', 'integer', 'between:1,5', 'required_with:assessments.*.days'],
            'weights' => ['required', 'array'],
            ...$this->weightRules(),
        ];
    }

    /** @return array<string, list<string>> */
    private function weightRules(): array
    {
        return [
            'weights.c1' => ['required', 'integer', 'between:0,100'],
            'weights.c2' => ['required', 'integer', 'between:0,100'],
            'weights.c3' => ['required', 'integer', 'between:0,100'],
        ];
    }

    /**
     * @param  array{c1: int|string, c2: int|string, c3: int|string}  $weights
     * @return array{c1: int, c2: int, c3: int}|null
     */
    private function validatedWeights(array $weights): ?array
    {
        if (array_sum($weights) !== 100) {
            $this->addError('weights', 'total');

            return null;
        }

        return ['c1' => (int) $weights['c1'], 'c2' => (int) $weights['c2'], 'c3' => (int) $weights['c3']];
    }

    /** @return array<int, array{days: float, urgency: int}> */
    private function providedAssessments(): array
    {
        return collect($this->assessments)
            ->filter(fn (array $assessment) => $assessment['days'] !== null && $assessment['urgency'] !== null)
            ->map(fn (array $assessment) => ['days' => (float) $assessment['days'], 'urgency' => (int) $assessment['urgency']])
            ->all();
    }

    /** @return Collection<int, EvaluationUnit> */
    private function units(): Collection
    {
        return EvaluationUnit::query()->forWongReang()->orderBy('display_order')->get();
    }

    private function period(): EvaluationPeriod
    {
        return EvaluationPeriod::query()->findOrFail($this->periodId);
    }
}
