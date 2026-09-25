<?php

namespace App\Modules\Hr\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

/**
 * Offboarding someone who still actively manages people requires choosing
 * who those reports move to. SLMS360 returned this as a 200 with
 * status:false; it's a validation failure, so it's a 422 here — same
 * requires_manager_reassignment/direct_reports_count keys for the UI, plus a
 * standard errors bag.
 */
class ManagerReassignmentRequired extends RuntimeException
{
    public function __construct(public readonly int $directReportsCount)
    {
        parent::__construct(
            "This staff member has {$directReportsCount} direct report(s). Please select a new manager to reassign them to."
        );
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'status' => false,
            'requires_manager_reassignment' => true,
            'direct_reports_count' => $this->directReportsCount,
            'message' => $this->getMessage(),
            'errors' => ['new_manager_id' => [$this->getMessage()]],
        ], 422);
    }
}
