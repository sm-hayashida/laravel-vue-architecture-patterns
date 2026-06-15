<?php

declare(strict_types=1);

namespace App\UseCases\Products\Outputs;

use App\Entities\Product;

final class ProductOutputData
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $sku,
        public readonly string $name,
        public readonly int $stockQuantity,
        public readonly int $priceAmountInCents,
    ) {
    }

    public static function fromProduct(Product $product): self
    {
        return new self(
            id: $product->id()?->value(),
            sku: $product->sku()->value(),
            name: $product->name()->value(),
            stockQuantity: $product->stockQuantity()->value(),
            priceAmountInCents: $product->price()->amountInCents(),
        );
    }
}
