<?php

declare(strict_types=1);

namespace App\Domain\Services;

use App\Domain\Enums\OperatorRole;
use App\Domain\Exceptions\InventoryDomainException;

final class StockAdjustmentPolicy
{
    public function canAdjust(OperatorRole $operatorRole): bool
    {
        return $operatorRole === OperatorRole::Manager;
    }

    public function assertCanAdjust(OperatorRole $operatorRole): void
    {
        if (! $this->canAdjust($operatorRole)) {
            throw new InventoryDomainException('Only managers can adjust stock directly.');
        }
    }
}
