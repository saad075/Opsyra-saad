<?php

namespace App\Modules\Payroll\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

/**
 * A payroll action that can't be done in the record's current state —
 * e.g. regenerating an approved payslip, or a pay run for a period that
 * overlaps an existing one.
 */
class PayrollException extends RuntimeException
{
    public function render(): JsonResponse
    {
        return response()->json(['status' => false, 'message' => $this->getMessage()], 409);
    }
}
