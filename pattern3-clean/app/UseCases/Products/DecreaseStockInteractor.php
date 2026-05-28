<?php

declare(strict_types=1);

namespace App\UseCases\Products;

use App\Entities\Exceptions\InventoryEntityException;
use App\Entities\Product;
use App\UseCases\Products\Exceptions\InventoryUseCaseException;
use App\UseCases\Products\Gateways\ProductRepositoryInterface;

final class DecreaseStockInteractor
{
    public function __construct(private readonly ProductRepositoryInterface $products)
    {
    }

    /**
     * @throws InventoryEntityException
     * @throws InventoryUseCaseException
     */
    public function execute(DecreaseStockInput $input): Product
    {
        $product = $this->findProduct($input);
        $product->decreaseStock($input->quantity);

        return $this->products->save($product);
    }

    private function findProduct(DecreaseStockInput $input): Product
    {
        $product = $this->products->findById($input->productId);

        if ($product === null) {
            throw new InventoryUseCaseException('Product was not found.');
        }

        return $product;
    }
}
