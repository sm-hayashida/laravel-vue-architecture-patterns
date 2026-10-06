<?php

declare(strict_types=1);

namespace App\UseCases\Products\Ports\Output;

use App\UseCases\Products\Outputs\ProductOutputData;

interface ProductListOutputPort
{
    /** @param list<ProductOutputData> $products */
    public function presentList(array $products): void;
}
