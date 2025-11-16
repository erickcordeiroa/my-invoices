<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\Auth\LoginDTO;
use App\Exceptions\Auth\LoginException;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthServices
{
    public function login(LoginDTO $credentials): string
    {
        $user = User::where('email', $credentials->email)->first();

        if (!$user) {
            throw new LoginException('Usuário ou senha inválidos');
        }

        if (!Hash::check($credentials->password, $user->password)) {
            throw new LoginException('Usuário ou senha inválidos');
        }

        return $user->createAuthToken();
    }
}