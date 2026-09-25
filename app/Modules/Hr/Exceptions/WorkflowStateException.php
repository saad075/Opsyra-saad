<?php

namespace App\Modules\Hr\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

/**
 * The request is well-formed but the record isn't in a state that allows the
 * action — e.g. approving a request that was already reviewed, or offboarding
 * someone who already has an exit pending. 409 rather than SLMS360's 404,
 * which conflated "not found" with "already handled".
 */
class WorkflowStateException extends RuntimeException
{
    public function render(): JsonResponse
    {
        return response()->json(['status' => false, 'message' => $this->getMessage()], 409);
    }
}
