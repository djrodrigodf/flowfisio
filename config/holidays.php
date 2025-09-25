<?php

return [
    // provedor Invertexto
    'invertexto' => [
        'base_url' => env('INVERTEXTO_BASE', 'https://api.invertexto.com'),
        'token' => env('INVERTEXTO_TOKEN'),
        'state' => env('HOLIDAYS_STATE', 'DF'),
        'timeout' => (int) env('INVERTEXTO_TIMEOUT', 15),
        // sem autenticação via header; a API recebe o token por query string
    ],

    // escopo padrão (Global = null) — mantém compatível com nosso morphTo
    'default_scope_type' => null,   // null | 'location' | 'room' | 'professional'
    'default_scope_id' => null,   // id correspondente quando não for null
];
