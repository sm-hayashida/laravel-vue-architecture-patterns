<?php

declare(strict_types=1);

namespace App\UseCases\Products\Ports\Input;

use App\Entities\Exceptions\InventoryEntityException;
use App\UseCases\Products\DecreaseStockInput;
use App\UseCases\Products\Exceptions\InventoryUseCaseException;

interface DecreaseStockInputPort
{
    /**
     * @throws InventoryEntityException
     * @throws InventoryUseCaseException
     */
    public function execute(DecreaseStockInput $input): void;
}
