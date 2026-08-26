<?php

namespace App\Policies;

use App\Models\Alert;
use App\Models\User;

/**
 * Policy de autorização para ações sobre alertas.
 */
class AlertPolicy
{
    /**
     * Determina se o usuário pode resolver um alerta.
     */
    public function resolve(User $user, Alert $alert): bool
    {
        return $user->role === 'admin';
    }
}
