<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaasPlan extends Model
{
    protected $fillable = [
        'name',
        'description',
        'price',
        'interval',
        'max_products',
        'max_orders_per_month',
        'max_students',
        'is_free',
        'is_active',
        'is_highlighted',
        'features',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'max_products' => 'integer',
            'max_orders_per_month' => 'integer',
            'max_students' => 'integer',
            'is_free' => 'boolean',
            'is_active' => 'boolean',
            'is_highlighted' => 'boolean',
            'features' => 'array',
            'sort_order' => 'integer',
        ];
    }

    public const INTERVAL_MONTHLY = 'monthly';
    public const INTERVAL_QUARTERLY = 'quarterly';
    public const INTERVAL_SEMI_ANNUAL = 'semi_annual';
    public const INTERVAL_ANNUAL = 'annual';

    public static function intervalLabels(): array
    {
        return [
            self::INTERVAL_MONTHLY => 'Mensal',
            self::INTERVAL_QUARTERLY => 'Trimestral',
            self::INTERVAL_SEMI_ANNUAL => 'Semestral',
            self::INTERVAL_ANNUAL => 'Anual',
        ];
    }

    public function intervalLabel(): string
    {
        return self::intervalLabels()[$this->interval] ?? $this->interval;
    }

    /**
     * Retorna a duração em dias do intervalo.
     */
    public function intervalDays(): int
    {
        return match ($this->interval) {
            self::INTERVAL_MONTHLY => 30,
            self::INTERVAL_QUARTERLY => 90,
            self::INTERVAL_SEMI_ANNUAL => 180,
            self::INTERVAL_ANNUAL => 365,
            default => 30,
        };
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(SaasSubscription::class);
    }

    public function activeSubscriptions(): HasMany
    {
        return $this->subscriptions()->where('status', 'active')->where('expires_at', '>=', now()->toDateString());
    }

    /**
     * Scope para planos ativos e ordenados.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('price');
    }

    /**
     * Scope para buscar o plano free.
     */
    public function scopeFree($query)
    {
        return $query->where('is_free', true)->where('is_active', true);
    }
}
