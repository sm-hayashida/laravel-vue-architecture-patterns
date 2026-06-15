<?php

declare(strict_types=1);

namespace App\UseCases\Products\Ports\Output;

use App\UseCases\Products\Outputs\ProductOutputData;

interface ProductOutputPort
{
    public function present(ProductOutputData $output): void;
}
