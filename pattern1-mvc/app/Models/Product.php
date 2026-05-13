<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;

class Product extends Model
{
    use HasFactory;

    public const MOVEMENT_IN = 'in';

    public const MOVEMENT_OUT = 'out';

    public const MOVEMENT_ADJUSTMENT = 'adjustment';

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
        return $this->hasMany(StockMovement::class);
    }

    public function increaseStock(int $quantity, ?string $reason = null): void
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Quantity must be greater than zero.');
        }

        $this->stock_quantity += $quantity;
        $this->save();

        $this->stockMovements()->create([
            'type' => self::MOVEMENT_IN,
            'quantity' => $quantity,
            'reason' => $reason,
        ]);
    }

    public function decreaseStock(int $quantity, ?string $reason = null): void
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Quantity must be greater than zero.');
        }

        if ($this->stock_quantity < $quantity) {
            throw new InvalidArgumentException('Stock quantity cannot be negative.');
        }

        $this->stock_quantity -= $quantity;
        $this->save();

        $this->stockMovements()->create([
            'type' => self::MOVEMENT_OUT,
            'quantity' => $quantity,
            'reason' => $reason,
        ]);
    }

    public function adjustStock(int $quantity, ?string $reason = null): void
    {
        if ($quantity < 0) {
            throw new InvalidArgumentException('Stock quantity cannot be negative.');
        }

        $this->stock_quantity = $quantity;
        $this->save();

        $this->stockMovements()->create([
            'type' => self::MOVEMENT_ADJUSTMENT,
            'quantity' => $quantity,
            'reason' => $reason,
        ]);
    }
}
