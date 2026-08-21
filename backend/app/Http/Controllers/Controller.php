<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Controlador base abstrato da aplicacao.
 *
 * Todos os controladores do HydroTrack devem estender esta classe
 * para herdar middlewares globais e metodos auxiliares compartilhados.
 */
abstract class Controller
{
    use AuthorizesRequests;
}
