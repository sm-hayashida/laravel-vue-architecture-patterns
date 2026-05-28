<?php

declare(strict_types=1);

namespace App\Entities\ValueObjects;

use App\Entities\Exceptions\InventoryEntityException;

final class ProductId
{
    public function __construct(private readonly int $value)
    {
        if ($value <= 0) {
            throw new InventoryEntityException('Product id must be greater than zero.');
        }
    }

    public function value(): int
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
