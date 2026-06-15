<?php

declare(strict_types=1);

namespace App\UseCases\Products;

use App\Entities\Enums\OperatorRole;
use App\Entities\Product;
use App\UseCases\Products\Exceptions\InventoryUseCaseException;
use App\UseCases\Products\Gateways\ProductRepositoryInterface;
use App\UseCases\Products\Outputs\ProductOutputData;
use App\UseCases\Products\Ports\Input\AdjustStockInputPort;
use App\UseCases\Products\Ports\Output\ProductOutputPort;

final class AdjustStockInteractor implements AdjustStockInputPort
{
    public function __construct(
        private readonly ProductRepositoryInterface $products,
        private readonly ProductOutputPort $output,
    ) {
    }

    /**
     * @throws InventoryUseCaseException
     */
    public function execute(AdjustStockInput $input): void
    {
        if ($input->operatorRole !== OperatorRole::Manager) {
            throw new InventoryUseCaseException('Only managers can adjust stock directly.');
        }

        $product = $this->findProduct($input);
        $product->adjustStock($input->quantity);

        $product = $this->products->save($product);

        $this->output->present(ProductOutputData::fromProduct($product));
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
