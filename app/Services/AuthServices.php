<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\Auth\ActivateDTO;
use App\DTO\Auth\LoginDTO;
use App\DTO\Auth\RegisterDTO;
use App\Exceptions\Auth\ActivateException;
use App\Exceptions\Auth\LoginException;
use App\Exceptions\Auth\RegisterException;
use App\Jobs\ActivateMailJob;
use App\Models\AccountActivation;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthServices
{
    public function login(LoginDTO $credentials): string
    {
        $user = User::where('email', $credentials->email)->first();
        
        if (!$user) {
            throw new LoginException('Usuário ou senha inválidos');
        }

        $user->ensureCanLogin();

        if (!Hash::check($credentials->password, $user->password)) {
            throw new LoginException('Usuário ou senha inválidos');
        }

        return $user->createAuthToken();
    }

    public function register(RegisterDTO $register): User
    {
        $user = User::where('email', $register->email)->first();
        if ($user) {
            throw new RegisterException('Usuário já cadastrado');
        }
        
        $user = User::create([
            'name' => $register->name,
            'email' => $register->email,
            'password' => $register->password,
        ]);

        $token = Str::random(64);
        $user->accountActivations()->create([
            'token' => hash('sha256', $token),
            'expires_at' => now()->addHours(24),
        ]);

        ActivateMailJob::dispatch($user, $token);

        return $user;
    }

    public function activate(ActivateDTO $activate): void
    {
        $hashedToken = hash('sha256', $activate->token);
        $accountActivation = AccountActivation::findByTokenHash($hashedToken);

        if (!$accountActivation) {
            throw new ActivateException('Token de ativação inválido');
        }

        $accountActivation->ensureCanActivate();

        $user = $accountActivation->user;
        $user->update([
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $accountActivation->update([
            'activated_at' => now(),
        ]);
    }

    public function logout(User $user): void
    {
        $user->revokeAllTokens();
    }
}