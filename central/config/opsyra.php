<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Countries
    |--------------------------------------------------------------------------
    |
    | One Opsyra deployment per country (docs/decisions.md). Codes are ISO
    | 3166 alpha-2; `url` is that deployment's app, used by the company-code
    | login redirect and by sign-up provisioning.
    |
    */

    'countries' => [
        'us' => ['name' => 'United States', 'currency' => 'USD', 'url' => env('OPSYRA_US_URL', 'http://localhost:5173')],
        'gb' => ['name' => 'United Kingdom', 'currency' => 'GBP', 'url' => env('OPSYRA_GB_URL')],
        'om' => ['name' => 'Oman', 'currency' => 'OMR', 'url' => env('OPSYRA_OM_URL')],
        'pk' => ['name' => 'Pakistan', 'currency' => 'PKR', 'url' => env('OPSYRA_PK_URL')],
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
