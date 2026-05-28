<?php

declare(strict_types=1);

namespace App\UseCases\Products;

use App\Entities\ValueObjects\MovementQuantity;
use App\Entities\ValueObjects\ProductId;

final class DecreaseStockInput
{
    public function __construct(
        public readonly ProductId $productId,
        public readonly MovementQuantity $quantity,
    ) {
    }
}
