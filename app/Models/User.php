<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Exceptions\Auth\LoginException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function revokeAllTokens(): void
    {
        $this->tokens()->delete();
    }

    public function createAuthToken(): string
    {
        return $this->createToken('auth_token')->plainTextToken;
    }

    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    public function accountActivations()
    {
        return $this->hasMany(AccountActivation::class);
    }

    public function wallets(): HasMany
    {
        return $this->hasMany(Wallet::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Verifica se o usuário está ativo
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Verifica se o usuário está pendente
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Verifica se o usuário está inativo
     */
    public function isInactive(): bool
    {
        return $this->status === 'inactive';
    }

    /**
     * Verifica se o usuário pode fazer login
     * 
     * @throws LoginException
     */
    public function ensureCanLogin(): void
    {
        if ($this->isPending()) {
            throw new LoginException(
                'Usuário pendente de aprovação, acesse seu e-mail e clique no link de ativação'
            );
        }

        if ($this->isInactive()) {
            throw new LoginException(
                'Usuário inativo, contate o suporte'
            );
        }
    }
}
