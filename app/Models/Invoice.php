<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'wallet_id',
        'category_id',
        'invoice_of',
        'description',
        'type',
        'amount',
        'currency',
        'due_at',
        'paid_at',
        'repeat_when',
        'period',
        'enrollments',
        'enrollments_of',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    protected function casts(): array
    {
        return [
            'due_at' => 'date',
            'paid_at' => 'date',
            'enrollments' => 'integer',
            'enrollments_of' => 'integer',
            'invoice_of' => 'integer',
            'amount' => 'integer',
        ];
    }
}
