<?php

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
    HrServiceProvider::class,
    PayrollServiceProvider::class,
    SetupServiceProvider::class,
    ModulesServiceProvider::class,
];
