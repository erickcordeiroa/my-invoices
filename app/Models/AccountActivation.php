<?php

namespace App\Models;

use App\Exceptions\Auth\ActivateException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountActivation extends Model
{
    protected $fillable = [
        'user_id',
        'token',
        'expires_at',
        'activated_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'activated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isValid(): bool
    {
        return $this->expires_at->isFuture() && $this->activated_at === null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isActivated(): bool
    {
        return $this->activated_at !== null;
    }

    /**
     * Busca um token de ativação pelo hash do token
     */
    public static function findByTokenHash(string $hashedToken): ?self
    {
        return self::where('token', $hashedToken)
            ->whereNull('activated_at')
            ->first();
    }

    /**
     * Verifica se o token pode ser usado para ativação
     * 
     * @throws ActivateException
     */
    public function ensureCanActivate(): void
    {
        if ($this->isExpired()) {
            throw new ActivateException('Token de ativação expirado. Solicite um novo link de ativação');
        }

        if ($this->isActivated()) {
            throw new ActivateException('Token de ativação já foi utilizado');
        }

        $user = $this->user;
        if (!$user) {
            throw new ActivateException('Usuário não encontrado');
        }

        if ($user->isActive()) {
            throw new ActivateException('Conta já está ativa');
        }
    }
}
