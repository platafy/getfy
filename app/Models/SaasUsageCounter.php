<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaasUsageCounter extends Model
{
    protected $fillable = [
        'user_id',
        'period',
        'orders_count',
        'products_count',
    ];

    protected function casts(): array
    {
        return [
            'orders_count' => 'integer',
            'products_count' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Busca ou cria o contador do mês atual para o usuário.
     */
    public static function currentMonth(int $userId): self
    {
        return static::firstOrCreate(
            ['user_id' => $userId, 'period' => now()->format('Y-m')],
            ['orders_count' => 0, 'products_count' => 0]
        );
    }

    /**
     * Incrementa o contador de pedidos.
     */
    public static function incrementOrders(int $userId): void
    {
        $counter = static::currentMonth($userId);
        $counter->increment('orders_count');
    }

    /**
     * Incrementa o contador de produtos.
     */
    public static function incrementProducts(int $userId): void
    {
        $counter = static::currentMonth($userId);
        $counter->increment('products_count');
    }
}
