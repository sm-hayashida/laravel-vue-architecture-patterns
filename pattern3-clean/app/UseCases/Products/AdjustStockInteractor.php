<?php

declare(strict_types=1);

namespace App\UseCases\Products;

use App\Entities\Enums\OperatorRole;
use App\Entities\Product;
use App\UseCases\Products\Exceptions\InventoryUseCaseException;
use App\UseCases\Products\Gateways\ProductRepositoryInterface;

final class AdjustStockInteractor
{
    public function __construct(private readonly ProductRepositoryInterface $products)
    {
    }

    /**
     * @throws InventoryUseCaseException
     */
    public function execute(AdjustStockInput $input): Product
    {
        if ($input->operatorRole !== OperatorRole::Manager) {
            throw new InventoryUseCaseException('Only managers can adjust stock directly.');
        }

        $product = $this->findProduct($input);
        $product->adjustStock($input->quantity);

        return $this->products->save($product);
    }

    private function findProduct(AdjustStockInput $input): Product
    {
        $product = $this->products->findById($input->productId);

        if ($product === null) {
            throw new InventoryUseCaseException('Product was not found.');
        }

        return $product;
    }
}
