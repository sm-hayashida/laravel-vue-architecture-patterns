<?php

declare(strict_types=1);

namespace App\UseCases\Products;

use App\Entities\Exceptions\InventoryEntityException;
use App\Entities\Product;
use App\UseCases\Products\Exceptions\InventoryUseCaseException;
use App\UseCases\Products\Gateways\ProductRepositoryInterface;
use App\UseCases\Products\Outputs\ProductOutputData;
use App\UseCases\Products\Ports\Input\DecreaseStockInputPort;
use App\UseCases\Products\Ports\Output\ProductOutputPort;

final class DecreaseStockInteractor implements DecreaseStockInputPort
{
    public function __construct(
        private readonly ProductRepositoryInterface $products,
        private readonly ProductOutputPort $output,
    ) {
    }

    /**
     * @throws InventoryEntityException
     * @throws InventoryUseCaseException
     */
    public function execute(DecreaseStockInput $input): void
    {
        $product = $this->findProduct($input);
        $product->decreaseStock($input->quantity);

        $product = $this->products->save($product);

        $this->output->present(ProductOutputData::fromProduct($product));
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
