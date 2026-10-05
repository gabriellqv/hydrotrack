<?php

/**
 * Testes dos cabeçalhos HTTP de segurança aplicados globalmente.
 *
 * Garantem que a API responda com as proteções básicas de
 * sniffing, clickjacking, CSP e política de referência.
 */
it('aplica cabeçalhos de segurança nas respostas da API', function () {
    $response = $this->getJson('/api/alerts');

    $response->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('Referrer-Policy', 'no-referrer')
        ->assertHeader('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

    expect($response->headers->get('Content-Security-Policy'))
        ->toContain("default-src 'none'");
});
