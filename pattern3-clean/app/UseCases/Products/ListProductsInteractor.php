<?php

declare(strict_types=1);

namespace App\UseCases\Products;

use App\UseCases\Products\Gateways\ProductRepositoryInterface;
use App\UseCases\Products\Outputs\ProductOutputData;
use App\UseCases\Products\Ports\Input\ListProductsInputPort;
use App\UseCases\Products\Ports\Output\ProductListOutputPort;

final class ListProductsInteractor implements ListProductsInputPort
{
    public function __construct(
        private readonly ProductRepositoryInterface $products,
        private readonly ProductListOutputPort $output,
    ) {
    }

    public function execute(): void
    {
        $outputs = array_map(
            static fn ($product): ProductOutputData => ProductOutputData::fromProduct($product),
            $this->products->findAll(),
        );

        $this->output->presentList($outputs);
    }
}
