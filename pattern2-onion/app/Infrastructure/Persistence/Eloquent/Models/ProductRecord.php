<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductRecord extends Model
{
    protected $table = 'products';

    protected $fillable = [
        'sku',
        'name',
        'stock_quantity',
        'price',
    ];

    protected $casts = [
        'stock_quantity' => 'integer',
        'price' => 'decimal:2',
    ];

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovementRecord::class, 'product_id');
    }
}
