<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\Exceptions\InventoryApplicationException;
use App\Domain\Entities\Product;
use App\Domain\Enums\OperatorRole;
use App\Domain\Repositories\ProductRepositoryInterface;
use App\Domain\Services\StockAdjustmentPolicy;
use App\Domain\ValueObjects\Money;
use App\Domain\ValueObjects\MovementQuantity;
use App\Domain\ValueObjects\ProductId;
use App\Domain\ValueObjects\ProductName;
use App\Domain\ValueObjects\Sku;
use App\Domain\ValueObjects\StockQuantity;

final class ProductInventoryService
{
    public function __construct(
        private readonly ProductRepositoryInterface $products,
        private readonly StockAdjustmentPolicy $stockAdjustmentPolicy,
    ) {
    }

    /**
     * @return Product[]
     */
    public function listProducts(): array
    {
        return $this->products->findAll();
    }

    public function createProduct(
        Sku $sku,
        ProductName $name,
        StockQuantity $initialStock,
        Money $price,
    ): Product {
        if ($this->products->existsBySku($sku)) {
            throw new InventoryApplicationException('Product SKU already exists.');
        }

        return $this->products->save(new Product(
            id: null,
            sku: $sku,
            name: $name,
            stockQuantity: $initialStock,
            price: $price,
        ));
    }

    public function increaseStock(ProductId $productId, MovementQuantity $quantity): Product
    {
        $product = $this->findProduct($productId);
        $product->increaseStock($quantity);

        return $this->products->save($product);
    }

    public function decreaseStock(ProductId $productId, MovementQuantity $quantity): Product
    {
        $product = $this->findProduct($productId);
        $product->decreaseStock($quantity);

        return $this->products->save($product);
    }

    public function adjustStock(
        ProductId $productId,
        StockQuantity $quantity,
        OperatorRole $operatorRole,
    ): Product {
        $this->stockAdjustmentPolicy->assertCanAdjust($operatorRole);

        $product = $this->findProduct($productId);
        $product->adjustStock($quantity);

        return $this->products->save($product);
    }

    private function findProduct(ProductId $productId): Product
    {
        $product = $this->products->findById($productId);

        if ($product === null) {
            throw new InventoryApplicationException('Product was not found.');
        }

        return $product;
    }
}
