<?php

/**
 * Testes para o endpoint de health check da API.
 *
 * Garantem que o endpoint /health consegue verificar a conexao com o banco.
 */
it('retorna 200 quando o banco de dados esta acessivel', function () {
    $response = $this->getJson('/api/health');

    $response->assertStatus(200)
        ->assertJson([
            'status' => 'ok',
            'database' => true,
        ]);
});
