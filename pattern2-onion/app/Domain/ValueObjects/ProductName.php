<?php

declare(strict_types=1);

namespace App\Domain\ValueObjects;

use App\Domain\Exceptions\InventoryDomainException;

final class ProductName
{
    private const MAX_LENGTH = 255;

    public function __construct(private readonly string $value)
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            throw new InventoryDomainException('Product name must not be empty.');
        }

        if (mb_strlen($trimmed) > self::MAX_LENGTH) {
            throw new InventoryDomainException('Product name must be 255 characters or fewer.');
        }
    }

    public function value(): string
    {
        return trim($this->value);
    }

    public function equals(self $other): bool
    {
        return $this->value() === $other->value();
    }
}
