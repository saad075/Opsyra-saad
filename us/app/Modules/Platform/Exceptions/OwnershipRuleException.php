<?php

namespace App\Modules\Platform\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

/** An action that would take control of the company away from its account owner. */
class OwnershipRuleException extends RuntimeException
{
    public function render(): JsonResponse
    {
        return response()->json(['status' => false, 'message' => $this->getMessage()], 409);
    }
}
