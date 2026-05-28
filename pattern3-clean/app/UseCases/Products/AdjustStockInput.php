<?php

declare(strict_types=1);

namespace App\UseCases\Products;

use App\Entities\Enums\OperatorRole;
use App\Entities\ValueObjects\ProductId;
use App\Entities\ValueObjects\StockQuantity;

final class AdjustStockInput
{
    public function __construct(
        public readonly ProductId $productId,
        public readonly StockQuantity $quantity,
        public readonly OperatorRole $operatorRole,
    ) {
    }
}
