<?php

/**
 * Configuração base dos testes Pest.
 *
 * Os testes usam `Tests\TestCase` e o trait `RefreshDatabase`.
 */

use Tests\TestCase;

pest()->extend(TestCase::class)
    ->in('Feature');
