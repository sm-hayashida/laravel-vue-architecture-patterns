<?php

declare(strict_types=1);

namespace App\UseCases\Products;

use App\Entities\Product;
use App\UseCases\Products\Exceptions\InventoryUseCaseException;
use App\UseCases\Products\Gateways\ProductRepositoryInterface;
use App\UseCases\Products\Outputs\ProductOutputData;
use App\UseCases\Products\Ports\Input\CreateProductInputPort;
use App\UseCases\Products\Ports\Output\ProductOutputPort;

final class CreateProductInteractor implements CreateProductInputPort
{
    public function __construct(
        private readonly ProductRepositoryInterface $products,
        private readonly ProductOutputPort $output,
    ) {
    }

    /**
     * @throws InventoryUseCaseException
     */
    public function execute(CreateProductInput $input): void
    {
        if ($this->products->existsBySku($input->sku)) {
            throw new InventoryUseCaseException('Product SKU already exists.');
        }

        $product = $this->products->save(new Product(
            id: null,
            sku: $input->sku,
            name: $input->name,
            stockQuantity: $input->initialStock,
            price: $input->price,
        ));

        $this->output->present(ProductOutputData::fromProduct($product));
    }
}
