<?php

declare(strict_types=1);

namespace App\UseCases\Products\Gateways;

use App\Entities\Product;
use App\Entities\ValueObjects\ProductId;
use App\Entities\ValueObjects\Sku;

interface ProductRepositoryInterface
{
    /** @return list<Product> */
    public function findAll(): array;

    public function findById(ProductId $id): ?Product;

    public function existsBySku(Sku $sku): bool;

    public function save(Product $product): Product;
}
