<?php

return [
    'provider' => env('BILLING_PROVIDER', ''),
    'customer_boleto_provider' => env('CUSTOMER_BOLETO_PROVIDER', 'asaas'),
    'grace_days' => (int) env('BILLING_GRACE_DAYS', 3),
    'trial_days' => (int) env('BILLING_TRIAL_DAYS', 5),

    'default_plan' => [
        'nome' => 'Plano Padrão',
        'descricao' => 'Plano padrão para operação comercial e administrativa.',
        'valor_mensal' => 49.90,
        'limite_usuarios' => 5,
        'limite_produtos' => 1000,
        'permite_promissoria' => true,
        'permite_relatorios_pdf' => true,
        'permite_exportacao_xlsx' => true,
        'ativo' => true,
    ],

    'asaas' => [
        'api_key' => env('ASAAS_API_KEY', ''),
        'base_url' => env('ASAAS_BASE_URL', 'https://api.asaas.com/v3'),
        'billing_type' => env('ASAAS_BILLING_TYPE', 'UNDEFINED'),
        'subscription_cycle' => env('ASAAS_SUBSCRIPTION_CYCLE', 'MONTHLY'),
        'webhook_token' => env('ASAAS_WEBHOOK_TOKEN', ''),
        'timeout' => (int) env('ASAAS_TIMEOUT', 30),
    ],
];