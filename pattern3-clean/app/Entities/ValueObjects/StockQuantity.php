<?php

declare(strict_types=1);

namespace App\Entities\ValueObjects;

use App\Entities\Exceptions\InventoryEntityException;

final class StockQuantity
{
    public function __construct(private readonly int $value)
    {
        if ($value < 0) {
            throw new InventoryEntityException('Stock quantity cannot be negative.');
        }
    }

    public function value(): int
    {
        return $this->value;
    }

    public function increaseBy(MovementQuantity $quantity): self
    {
        return new self($this->value + $quantity->value());
    }

    public function decreaseBy(MovementQuantity $quantity): self
    {
        return new self($this->value - $quantity->value());
    }

    public function isLessThan(MovementQuantity $quantity): bool
    {
        return $this->value < $quantity->value();
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
