<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Countries
    |--------------------------------------------------------------------------
    |
    | One Opsyra deployment per country (docs/decisions.md). Codes are ISO
    | 3166 alpha-2. `url` is that deployment's app (company-code login
    | redirect); `api_url` + `secret` are its signed internal API, used for
    | sign-up provisioning — the secret must equal that deployment's
    | OPSYRA_CENTRAL_SECRET. A country with no api_url/secret can't take
    | sign-ups yet.
    |
    */

    'countries' => [
        'us' => ['name' => 'United States', 'currency' => 'USD', 'url' => env('OPSYRA_US_URL', 'http://localhost:5173'), 'api_url' => env('OPSYRA_US_API_URL', 'http://localhost:8000'), 'secret' => env('OPSYRA_US_SECRET')],
        'gb' => ['name' => 'United Kingdom', 'currency' => 'GBP', 'url' => env('OPSYRA_GB_URL'), 'api_url' => env('OPSYRA_GB_API_URL'), 'secret' => env('OPSYRA_GB_SECRET')],
        'om' => ['name' => 'Oman', 'currency' => 'OMR', 'url' => env('OPSYRA_OM_URL'), 'api_url' => env('OPSYRA_OM_API_URL'), 'secret' => env('OPSYRA_OM_SECRET')],
        'pk' => ['name' => 'Pakistan', 'currency' => 'PKR', 'url' => env('OPSYRA_PK_URL'), 'api_url' => env('OPSYRA_PK_API_URL'), 'secret' => env('OPSYRA_PK_SECRET')],
    ],

    /*
    |--------------------------------------------------------------------------
    | Plan features
    |--------------------------------------------------------------------------
    |
    | What a plan can unlock. Codes are stable (they'll be pushed to country
    | deployments for enforcement); labels are what customers read.
    | `available` = false marks features on the roadmap, not yet built.
    |
    */

    'features' => [
        'people' => ['label' => 'People, branches and departments', 'available' => true],
        'hiring_approvals' => ['label' => 'Hiring and leaving approvals', 'available' => true],
        'checklists' => ['label' => 'Onboarding and offboarding checklists', 'available' => true],
        'payroll' => ['label' => 'Payroll and payslips', 'available' => true],
        'pay_approvals' => ['label' => 'Two-level pay approval', 'available' => true],
        'login_history' => ['label' => 'Login history', 'available' => true],
        'attendance' => ['label' => 'Attendance and leave', 'available' => false],
        'dashboards' => ['label' => 'HR dashboards', 'available' => false],
        'id_cards' => ['label' => 'Staff ID cards', 'available' => false],
        'bulk_import' => ['label' => 'Bulk import and export', 'available' => false],
    ],

];
