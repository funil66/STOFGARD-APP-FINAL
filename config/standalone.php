<?php

return [
    'enabled' => (bool) env('APP_STANDALONE_MODE', false),

    'host' => env('APP_STANDALONE_HOST', ''),

    'disable_super_admin' => (bool) env('DISABLE_SUPER_ADMIN_PANEL', false),

    'disable_public_company_registration' => (bool) env('DISABLE_PUBLIC_COMPANY_REGISTRATION', false),

    'disable_saas_billing' => (bool) env('DISABLE_SAAS_BILLING', false),

    'disable_tenant_provisioning' => (bool) env('DISABLE_TENANT_PROVISIONING', false),

    'register_tenant_domain_routes' => (bool) env('REGISTER_TENANT_DOMAIN_ROUTES', true),
];