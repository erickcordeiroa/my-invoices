<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\Auth\ActivateDTO;
use App\DTO\Auth\ForgotDTO;
use App\DTO\Auth\LoginDTO;
use App\DTO\Auth\RegisterDTO;
use App\DTO\Auth\ResetPasswordDTO;
use App\Exceptions\Auth\ActivateException;
use App\Exceptions\Auth\ForgotPasswordException;
use App\Exceptions\Auth\LoginException;
use App\Exceptions\Auth\RegisterException;
use App\Exceptions\Auth\ResetPasswordException;
use App\Jobs\ActivateMailJob;
use App\Jobs\ResetPasswordMailJob;
use App\Models\AccountActivation;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
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

        if (!$user->wallets()->exists()) {
            $user->wallets()->create([
                'name' => 'Carteira Principal',
                'balance' => 0,
            ]);
        }
    }

    public function forgotPassword(ForgotDTO $forgot): void
    {
        $user = User::where('email', $forgot->email)->first();
        if (!$user) {
            throw new ForgotPasswordException('E-mail inválido ou não encontrado');
        }

        $token = Password::createToken($user);
        if (!$token) {
            throw new ForgotPasswordException('Erro ao criar token de redefinição de senha');
        }

        ResetPasswordMailJob::dispatch($user, $token);
    }

    public function resetPassword(ResetPasswordDTO $reset): void
    {
        $user = User::where('email', $reset->email)->first();
        if (!$user) {
            throw new ResetPasswordException('E-mail inválido ou não encontrado');
        }
        
        $status = Password::reset(
            [
               'email' => $reset->email,
               'password' => $reset->password,
               'token' => $reset->token,
            ],
            function (User $user, string $password) {
                $user->update(['password' => $password]);
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw new ResetPasswordException('Token de redefinição de senha inválido');
        }
            
    }

    public function logout(User $user): void
    {
        $user->revokeAllTokens();
    }
}