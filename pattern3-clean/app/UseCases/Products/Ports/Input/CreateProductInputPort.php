<?php

declare(strict_types=1);

namespace App\UseCases\Products\Ports\Input;

use App\UseCases\Products\CreateProductInput;
use App\UseCases\Products\Exceptions\InventoryUseCaseException;

interface CreateProductInputPort
{
    /**
     * @throws InventoryUseCaseException
     */
    public function execute(CreateProductInput $input): void;
}
