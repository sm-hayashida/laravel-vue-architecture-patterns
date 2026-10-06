<?php

declare(strict_types=1);

namespace App\UseCases\Products\Ports\Input;

interface ListProductsInputPort
{
    public function execute(): void;
}
