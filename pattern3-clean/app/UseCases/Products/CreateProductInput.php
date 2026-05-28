<?php

declare(strict_types=1);

namespace App\UseCases\Products;

use App\Entities\ValueObjects\Money;
use App\Entities\ValueObjects\ProductName;
use App\Entities\ValueObjects\Sku;
use App\Entities\ValueObjects\StockQuantity;

final class CreateProductInput
{
    public function __construct(
        public readonly Sku $sku,
        public readonly ProductName $name,
        public readonly StockQuantity $initialStock,
        public readonly Money $price,
    ) {
    }
}
