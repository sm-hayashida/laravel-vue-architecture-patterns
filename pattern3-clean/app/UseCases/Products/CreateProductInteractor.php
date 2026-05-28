<?php

declare(strict_types=1);

namespace App\UseCases\Products;

use App\Entities\Product;
use App\UseCases\Products\Exceptions\InventoryUseCaseException;
use App\UseCases\Products\Gateways\ProductRepositoryInterface;

final class CreateProductInteractor
{
    public function __construct(private readonly ProductRepositoryInterface $products)
    {
    }

    /**
     * @throws InventoryUseCaseException
     */
    public function execute(CreateProductInput $input): Product
    {
        if ($this->products->existsBySku($input->sku)) {
            throw new InventoryUseCaseException('Product SKU already exists.');
        }

        return $this->products->save(new Product(
            id: null,
            sku: $input->sku,
            name: $input->name,
            stockQuantity: $input->initialStock,
            price: $input->price,
        ));
    }
}
