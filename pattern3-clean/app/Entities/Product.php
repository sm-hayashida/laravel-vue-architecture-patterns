<?php

declare(strict_types=1);

namespace App\Entities;

use App\Entities\Exceptions\InventoryEntityException;
use App\Entities\ValueObjects\Money;
use App\Entities\ValueObjects\MovementQuantity;
use App\Entities\ValueObjects\ProductId;
use App\Entities\ValueObjects\ProductName;
use App\Entities\ValueObjects\Sku;
use App\Entities\ValueObjects\StockQuantity;

final class Product
{
    public function __construct(
        private readonly ?ProductId $id,
        private Sku $sku,
        private ProductName $name,
        private StockQuantity $stockQuantity,
        private Money $price,
    ) {
    }

    public function id(): ?ProductId
    {
        return $this->id;
    }

    public function sku(): Sku
    {
        return $this->sku;
    }

    public function name(): ProductName
    {
        return $this->name;
    }

    public function stockQuantity(): StockQuantity
    {
        return $this->stockQuantity;
    }

    public function price(): Money
    {
        return $this->price;
    }

    public function increaseStock(MovementQuantity $quantity): void
    {
        $this->stockQuantity = $this->stockQuantity->increaseBy($quantity);
    }

    /**
     * @throws InventoryEntityException
     */
    public function decreaseStock(MovementQuantity $quantity): void
    {
        if ($this->stockQuantity->isLessThan($quantity)) {
            throw new InventoryEntityException('Stock quantity cannot be negative.');
        }

        $this->stockQuantity = $this->stockQuantity->decreaseBy($quantity);
    }

    public function adjustStock(StockQuantity $quantity): void
    {
        $this->stockQuantity = $quantity;
    }
}
