<?php

namespace App\Modules\Setup\Services;

use App\Modules\Setup\Contracts\SetupStepProvider;
use App\Modules\Setup\Models\SetupStepState;
use App\Modules\Setup\Support\SetupStep;

/**
 * Builds the wizard's status for the current tenant from every registered
 * SetupStepProvider. Completion is always computed — a data step checks the
 * real records, a review step checks the admin's confirmation — so it
 * can't drift from reality however the data was entered.
 */
class SetupProgress
{
    /**
     * @return list<SetupStep>
     */
    public function steps(): array
    {
        $steps = [];

        foreach (app()->tagged(SetupStepProvider::TAG) as $provider) {
            array_push($steps, ...$provider->steps());
        }

        $groupOrder = array_flip(array_keys(SetupStep::GROUPS));

        usort($steps, fn (SetupStep $a, SetupStep $b) => [$groupOrder[$a->group] ?? 99, $a->order] <=> [$groupOrder[$b->group] ?? 99, $b->order]);

        return $steps;
    }

    public function find(string $key): ?SetupStep
    {
        return collect($this->steps())->firstWhere('key', $key);
    }

    public function status(): array
    {
        $states = SetupStepState::pluck('state', 'step_key');

        $steps = collect($this->steps())->map(function (SetupStep $step) use ($states) {
            $state = $states[$step->key] ?? null;

            [$done, $detail] = $step->kind === 'review'
                ? [$state === 'reviewed', null]
                : array_values(($step->check)() + ['detail' => null]);

            return [
                'key' => $step->key,
                'group' => $step->group,
                'group_label' => SetupStep::GROUPS[$step->group] ?? $step->group,
                'title' => $step->title,
                'description' => $step->description,
                'kind' => $step->kind,
                'required' => $step->required,
                'status' => $done ? 'done' : ($state === 'skipped' ? 'skipped' : 'pending'),
                'detail' => $detail,
            ];
        });

        $next = $steps->first(fn ($s) => $s['status'] === 'pending' && $s['required'])
            ?? $steps->first(fn ($s) => $s['status'] === 'pending');

        $remainingRequired = $steps->filter(fn ($s) => $s['required'] && $s['status'] !== 'done')->count();

        return [
            'steps' => $steps->values()->all(),
            'completed_count' => $steps->where('status', 'done')->count(),
            'total_count' => $steps->count(),
            'next_step' => $next['key'] ?? null,
            // Skipping doesn't count as finishing: a skipped required step
            // still needs doing before setup is complete.
            'is_complete' => $remainingRequired === 0,
        ];
    }
}
