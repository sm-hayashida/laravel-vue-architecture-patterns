<?php

declare(strict_types=1);

namespace App\UseCases\Products\Ports\Input;

use App\UseCases\Products\AdjustStockInput;
use App\UseCases\Products\Exceptions\InventoryUseCaseException;

interface AdjustStockInputPort
{
    /**
     * @throws InventoryUseCaseException
     */
    public function execute(AdjustStockInput $input): void;
}
