<?php

// Sobrescreve apenas o necessario; as demais chaves vem do default do pacote (mergeConfigFrom).
return [
    // Minutos de validade do token; expirado -> 401. Padrao 30 dias (SANCTUM_TOKEN_EXPIRATION).
    'expiration' => (int) env('SANCTUM_TOKEN_EXPIRATION', 60 * 24 * 30),
];
