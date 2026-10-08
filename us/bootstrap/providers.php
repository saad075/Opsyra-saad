<?php

use App\Modules\Attendance\AttendanceServiceProvider;
use App\Modules\Auth\AuthServiceProvider;
use App\Modules\Crm\CrmServiceProvider;
use App\Modules\Hr\HrServiceProvider;
use App\Modules\Payroll\PayrollServiceProvider;
use App\Modules\Platform\PlatformServiceProvider;
use App\Modules\Rbac\RbacServiceProvider;
use App\Modules\Setup\SetupServiceProvider;
use App\Modules\Tenancy\TenancyServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\ModulesServiceProvider;

return [
    AppServiceProvider::class,
    TenancyServiceProvider::class,
    PlatformServiceProvider::class,
    RbacServiceProvider::class,
    AuthServiceProvider::class,
    HrServiceProvider::class,
    PayrollServiceProvider::class,
    AttendanceServiceProvider::class,
    CrmServiceProvider::class,
    SetupServiceProvider::class,
    ModulesServiceProvider::class,
];
