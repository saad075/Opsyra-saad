<?php

namespace App\Modules\Setup\Contracts;

use App\Modules\Setup\Support\SetupStep;

/**
 * How a module adds its own steps to the Setup wizard. Implement it and
 * register with $this->app->tag(YourSteps::class, SetupStepProvider::TAG)
 * in the module's service provider. The rule (docs/status.md): any module
 * that adds per-company configuration registers steps for it here.
 */
interface SetupStepProvider
{
    public const TAG = 'setup.step_providers';

    /**
     * @return list<SetupStep>
     */
    public function steps(): array;
}
