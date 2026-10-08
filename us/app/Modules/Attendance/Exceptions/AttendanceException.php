<?php

namespace App\Modules\Attendance\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

/** An attendance action that can't be done right now — e.g. clocking in twice. */
class AttendanceException extends RuntimeException
{
    public function render(): JsonResponse
    {
        return response()->json(['status' => false, 'message' => $this->getMessage()], 409);
    }
}
