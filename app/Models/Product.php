<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'sku',
        'title',
        'slug',
        'description',
        'price',
        'cost_price',
        'stock',
        'min_stock',
        'unit',
        'brand',
        'color',
        'images',
        'is_active',
        'has_variations',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'cost_price' => 'integer',
            'stock' => 'integer',
            'min_stock' => 'integer',
            'images' => 'array',
            'is_active' => 'boolean',
            'has_variations' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function variations(): HasMany
    {
        return $this->hasMany(ProductVariation::class);
    }

    /**
     * Verifica se o estoque está abaixo do mínimo
     */
    public function isLowStock(): bool
    {
        if ($this->has_variations) {
            return $this->variations()->where('stock', '<=', 'min_stock')->exists();
        }
        
        return $this->stock <= $this->min_stock;
    }

    /**
     * Verifica se está sem estoque
     */
    public function isOutOfStock(): bool
    {
        if ($this->has_variations) {
            return $this->variations()->where('stock', '<=', 0)->count() === $this->variations()->count();
        }
        
        return $this->stock <= 0;
    }

    /**
     * Retorna o estoque total (incluindo variações)
     */
    public function getTotalStockAttribute(): int
    {
        if ($this->has_variations) {
            return $this->variations()->sum('stock');
        }
        
        return $this->stock;
    }

    /**
     * Gera slug automaticamente
     */
    public static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->title);
            }
            
            // Garante que o slug seja único
            $originalSlug = $product->slug;
            $count = 1;
            while (static::where('slug', $product->slug)->exists()) {
                $product->slug = $originalSlug . '-' . $count++;
            }
        });

        static::updating(function ($product) {
            if ($product->isDirty('title') && !$product->isDirty('slug')) {
                $product->slug = Str::slug($product->title);
                
                $originalSlug = $product->slug;
                $count = 1;
                while (static::where('slug', $product->slug)->where('id', '!=', $product->id)->exists()) {
                    $product->slug = $originalSlug . '-' . $count++;
                }
            }
        });
    }

    /**
     * Formata o preço para exibição
     */
    public function getFormattedPriceAttribute(): string
    {
        return 'R$ ' . number_format($this->price / 100, 2, ',', '.');
    }

    /**
     * Formata o preço de custo para exibição
     */
    public function getFormattedCostPriceAttribute(): string
    {
        return 'R$ ' . number_format($this->cost_price / 100, 2, ',', '.');
    }

    /**
     * Calcula a margem de lucro
     */
    public function getProfitMarginAttribute(): float
    {
        if ($this->cost_price <= 0) {
            return 0;
        }
        
        return round((($this->price - $this->cost_price) / $this->cost_price) * 100, 2);
    }
}
