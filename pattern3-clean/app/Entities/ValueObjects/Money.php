<?php

declare(strict_types=1);

namespace App\Entities\ValueObjects;

use App\Entities\Exceptions\InventoryEntityException;

final class Money
{
    public function __construct(private readonly int $amountInCents)
    {
        if ($amountInCents < 0) {
            throw new InventoryEntityException('Money amount cannot be negative.');
        }
    }

    public function amountInCents(): int
    {
        return $this->amountInCents;
    }

    public function equals(self $other): bool
    {
        return $this->amountInCents === $other->amountInCents;
    }
}
