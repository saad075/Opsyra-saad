<?php

namespace App\Modules\Payroll\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Hr\Models\UserUpdateLog;
use App\Modules\Payroll\Models\StaffBankAccount;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule as ValidationRule;

/**
 * Adapted from SLMS360's SalaryController::addupdateAccount()/accountIndex().
 * There, any logged-in user could overwrite any colleague's bank details —
 * i.e. redirect their salary — and the numbers were stored in plain text.
 * Here only HRMS-SalaryManagement can change or read full details, sensitive
 * fields are encrypted at rest, every change is logged (who/when, never the
 * values), and staff see their own details masked.
 */
class StaffBankAccountController extends Controller
{
    public function show(Request $request)
    {
        $this->authorize('manage-salaries');

        $validated = $request->validate([
            'user_id' => ['required', 'integer', ValidationRule::exists('users', 'id')->where('tenant_id', $request->user()->tenant_id)],
        ]);

        $account = StaffBankAccount::where('user_id', $validated['user_id'])->first();

        return response()->json([
            'status' => true,
            'message' => $account ? 'Bank details' : 'No bank details on file.',
            'data' => $account?->full(),
        ]);
    }

    public function save(Request $request)
    {
        $this->authorize('manage-salaries');

        $validated = $request->validate([
            'user_id' => ['required', 'integer', ValidationRule::exists('users', 'id')->where('tenant_id', $request->user()->tenant_id)],
            'account_holder_name' => ['required', 'string', 'max:255'],
            'bank_name' => ['required', 'string', 'max:255'],
            'bank_country' => ['required', 'string', 'size:2'],
            'account_number' => ['nullable', 'required_without:iban', 'string', 'max:64'],
            'iban' => ['nullable', 'string', 'max:64'],
            'routing_number' => ['nullable', 'string', 'max:64'],
            'tax_id' => ['nullable', 'string', 'max:64'],
            'bank_code' => ['nullable', 'string', 'max:64'],
            'swift_bic' => ['nullable', 'string', 'max:16'],
            'account_currency' => ['nullable', 'string', 'size:3'],
        ]);

        $existed = StaffBankAccount::where('user_id', $validated['user_id'])->exists();

        StaffBankAccount::updateOrCreate(
            ['user_id' => $validated['user_id']],
            collect($validated)->except('user_id')->all() + ['updated_by' => $request->user()->id],
        );

        // Bank changes are the classic payroll-fraud move, so they're
        // always traceable — but the log never holds the numbers themselves.
        UserUpdateLog::create([
            'user_id' => $validated['user_id'],
            'updated_by' => $request->user()->id,
            'field_name' => $existed ? 'bank_account_updated' : 'bank_account_added',
            'changed_at' => now(),
        ]);

        return response()->json(['status' => true, 'message' => 'Bank details saved.']);
    }

    public function mine(Request $request)
    {
        $account = StaffBankAccount::where('user_id', $request->user()->id)->first();

        return response()->json([
            'status' => true,
            'message' => $account ? 'My bank details' : 'No bank details on file.',
            'data' => $account?->masked(),
        ]);
    }
}
