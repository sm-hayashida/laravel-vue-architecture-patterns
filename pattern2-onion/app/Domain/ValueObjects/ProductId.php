<?php

declare(strict_types=1);

namespace App\Domain\ValueObjects;

use App\Domain\Exceptions\InventoryDomainException;

final class ProductId
{
    public function __construct(private readonly int $value)
    {
        if ($value <= 0) {
            throw new InventoryDomainException('Product id must be greater than zero.');
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
