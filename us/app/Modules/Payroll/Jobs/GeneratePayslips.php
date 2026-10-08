<?php

namespace App\Modules\Payroll\Jobs;

use App\Models\User;
use App\Modules\Payroll\Exceptions\PayrollException;
use App\Modules\Payroll\Models\PayRun;
use App\Modules\Payroll\Models\Payslip;
use App\Modules\Payroll\Services\PayslipGenerator;
use App\Modules\Tenancy\Concerns\TenantAwareJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Throwable;

/**
 * Replaces SLMS360 running `Artisan::call('staff:post-salary')` inside the
 * HTTP request with set_time_limit(0). Each person is generated in their own
 * transaction; a failure is recorded against the run (who, and why) instead
 * of being swallowed into the log — and nobody is emailed "salary
 * generated" for a payslip that failed, as SLMS360 did.
 */
class GeneratePayslips implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, TenantAwareJob;

    /**
     * @param  list<int>|null  $userIds  null = everyone eligible without a payslip yet
     */
    public function __construct(public int $payRunId, public ?array $userIds = null)
    {
        $this->captureTenantContext();
    }

    public function handle(PayslipGenerator $generator): void
    {
        $run = PayRun::findOrFail($this->payRunId);
        $run->update(['status' => 'processing']);

        $alreadyDone = Payslip::where('pay_run_id', $run->id)->pluck('user_id');

        $staff = $generator->eligibleStaff($run)
            ->when($this->userIds !== null, fn ($c) => $c->whereIn('id', $this->userIds))
            ->reject(fn (User $user) => $alreadyDone->contains($user->id));

        // Keep earlier failures for people not retried in this pass.
        $errors = collect($run->errors ?? [])->keyBy('user_id')->except($staff->pluck('id')->all());

        foreach ($staff as $user) {
            try {
                $generator->generate($run, $user);
            } catch (PayrollException $e) {
                $errors[$user->id] = ['user_id' => $user->id, 'name' => $user->name, 'message' => $e->getMessage()];
            } catch (Throwable $e) {
                report($e);
                $errors[$user->id] = ['user_id' => $user->id, 'name' => $user->name, 'message' => 'Unexpected error — see the application log.'];
            }
        }

        $run->update([
            'status' => 'completed',
            'payslips_count' => Payslip::where('pay_run_id', $run->id)->count(),
            'errors' => $errors->values()->all() ?: null,
            'completed_at' => now(),
        ]);
    }
}
