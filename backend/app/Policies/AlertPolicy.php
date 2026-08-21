<?php

namespace App\Policies;

use App\Models\Alert;
use App\Models\User;

/**
 * Policy de autorizacao para acoes sobre alertas.
 */
class AlertPolicy
{
    /**
     * Determina se o usuario pode resolver um alerta.
     */
    public function resolve(User $user, Alert $alert): bool
    {
        return $user->role === 'admin';
    }
}
