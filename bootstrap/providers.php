<?php

use App\Modules\Hr\HrServiceProvider;
use App\Modules\Payroll\PayrollServiceProvider;
use App\Modules\Rbac\RbacServiceProvider;
use App\Modules\Tenancy\TenancyServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\ModulesServiceProvider;

return [
    AppServiceProvider::class,
    TenancyServiceProvider::class,
    RbacServiceProvider::class,
    HrServiceProvider::class,
    PayrollServiceProvider::class,
    ModulesServiceProvider::class,
];
