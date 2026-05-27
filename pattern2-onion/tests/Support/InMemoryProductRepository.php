<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domain\Entities\Product;
use App\Domain\Repositories\ProductRepositoryInterface;
use App\Domain\ValueObjects\ProductId;
use App\Domain\ValueObjects\Sku;

final class InMemoryProductRepository implements ProductRepositoryInterface
{
    /**
     * @var array<int, Product>
     */
    private array $products = [];

    public int $saveCount = 0;

    private int $nextId = 1;

    public function __construct(Product ...$products)
    {
        foreach ($products as $product) {
            $id = $product->id();

            if ($id === null) {
                continue;
            }

            $this->products[$id->value()] = $product;
            $this->nextId = max($this->nextId, $id->value() + 1);
        }
    }

    public function findAll(): array
    {
        return array_values($this->products);
    }

    public function findById(ProductId $id): ?Product
    {
        return $this->products[$id->value()] ?? null;
    }

    public function existsBySku(Sku $sku): bool
    {
        foreach ($this->products as $product) {
            if ($product->sku()->equals($sku)) {
                return true;
            }
        }

        return false;
    }

    public function save(Product $product): Product
    {
        $this->saveCount++;

        if ($product->id() === null) {
            $product = new Product(
                id: new ProductId($this->nextId++),
                sku: $product->sku(),
                name: $product->name(),
                stockQuantity: $product->stockQuantity(),
                price: $product->price(),
            );
        }

        $this->products[$product->id()->value()] = $product;

        return $product;
    }
}
