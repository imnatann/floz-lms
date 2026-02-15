<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Tenancy Configuration
    |--------------------------------------------------------------------------
    */

    // Prefix for tenant database names (e.g., floz_tenant_1, floz_tenant_2)
    'database_prefix' => env('TENANCY_DATABASE_PREFIX', 'floz_tenant_'),

    // Central database name
    'central_database' => env('TENANCY_CENTRAL_DATABASE', 'floz_central'),

    // Supported education levels
    'education_levels' => [
        'SD'  => 'Sekolah Dasar',
        'SMP' => 'Sekolah Menengah Pertama',
        'SMA' => 'Sekolah Menengah Atas',
    ],

    // Grade levels per education level
    'grade_levels' => [
        'SD'  => [1, 2, 3, 4, 5, 6],
        'SMP' => [7, 8, 9],
        'SMA' => [10, 11, 12],
    ],

    // Domain configuration
    'central_domain' => env('TENANCY_CENTRAL_DOMAIN', 'admin.floz.id'),

    // Platform admin subdomain
    'admin_subdomain' => 'admin',

];
