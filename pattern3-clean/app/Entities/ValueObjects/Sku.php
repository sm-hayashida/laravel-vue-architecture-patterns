<?php

declare(strict_types=1);

namespace App\Entities\ValueObjects;

use App\Entities\Exceptions\InventoryEntityException;

final class Sku
{
    private const MAX_LENGTH = 64;

    public function __construct(private readonly string $value)
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            throw new InventoryEntityException('SKU must not be empty.');
        }

        if (mb_strlen($trimmed) > self::MAX_LENGTH) {
            throw new InventoryEntityException('SKU must be 64 characters or fewer.');
        }

        if (! preg_match('/^[A-Za-z0-9_-]+$/', $trimmed)) {
            throw new InventoryEntityException('SKU may contain only letters, numbers, hyphens, and underscores.');
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
