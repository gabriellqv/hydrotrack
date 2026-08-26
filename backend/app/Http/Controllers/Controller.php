<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Controlador base abstrato da aplicação.
 *
 * Todos os controladores do HydroTrack devem estender esta classe
 * para herdar middlewares globais e métodos auxiliares compartilhados.
 */
abstract class Controller
{
    use AuthorizesRequests;
}
