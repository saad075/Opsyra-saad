<?php

namespace App\Modules\Setup\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Setup\Models\SetupStepState;
use App\Modules\Setup\Services\SetupProgress;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Adapted from SLMS360's Client/SetupWizardController::status() (the only
 * endpoint it had), extended with skip/come-back and "reviewed" for
 * pre-filled defaults. Each step's own screens keep their own permissions;
 * this controller only reports progress and records the admin's choices.
 */
class SetupController extends Controller
{
    public function __construct(private SetupProgress $progress) {}

    public function status()
    {
        $this->authorize('run-setup');

        return response()->json(['status' => true, 'message' => 'Setup progress', 'data' => $this->progress->status()]);
    }

    public function skip(Request $request)
    {
        return $this->record($request, 'skipped');
    }

    public function markReviewed(Request $request)
    {
        return $this->record($request, 'reviewed', mustBeReviewStep: true);
    }

    /**
     * Brings a skipped step back, or withdraws a review confirmation.
     */
    public function reset(Request $request)
    {
        $this->authorize('run-setup');

        $step = $this->validStep($request);
        SetupStepState::where('step_key', $step->key)->delete();

        return response()->json(['status' => true, 'message' => 'Step reopened.', 'data' => $this->progress->status()]);
    }

    private function record(Request $request, string $state, bool $mustBeReviewStep = false)
    {
        $this->authorize('run-setup');

        $step = $this->validStep($request);

        if ($mustBeReviewStep && $step->kind !== 'review') {
            throw ValidationException::withMessages(['key' => 'This step is completed by entering its data, not by marking it reviewed.']);
        }

        SetupStepState::updateOrCreate(['step_key' => $step->key], ['state' => $state, 'updated_by' => $request->user()->id]);

        return response()->json(['status' => true, 'message' => 'Step updated.', 'data' => $this->progress->status()]);
    }

    private function validStep(Request $request)
    {
        $request->validate(['key' => ['required', 'string']]);

        return $this->progress->find($request->key)
            ?? throw ValidationException::withMessages(['key' => 'Unknown setup step.']);
    }
}
