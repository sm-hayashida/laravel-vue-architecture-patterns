<?php

declare(strict_types=1);

namespace App\UseCases\Products\Ports\Input;

use App\UseCases\Products\Exceptions\InventoryUseCaseException;
use App\UseCases\Products\IncreaseStockInput;

interface IncreaseStockInputPort
{
    /**
     * @throws InventoryUseCaseException
     */
    public function execute(IncreaseStockInput $input): void;
}
