<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entities\Product;
use App\Domain\ValueObjects\ProductId;
use App\Domain\ValueObjects\Sku;

interface ProductRepositoryInterface
{
    public function findById(ProductId $id): ?Product;

    public function existsBySku(Sku $sku): bool;

    public function save(Product $product): Product;
}
