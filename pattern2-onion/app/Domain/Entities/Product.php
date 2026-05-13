<?php

declare(strict_types=1);

namespace App\Domain\Entities;

use App\Domain\Exceptions\InventoryDomainException;
use App\Domain\ValueObjects\Money;
use App\Domain\ValueObjects\MovementQuantity;
use App\Domain\ValueObjects\ProductId;
use App\Domain\ValueObjects\ProductName;
use App\Domain\ValueObjects\Sku;
use App\Domain\ValueObjects\StockQuantity;

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

    public function rename(ProductName $name): void
    {
        $this->name = $name;
    }

    public function changeSku(Sku $sku): void
    {
        $this->sku = $sku;
    }

    public function changePrice(Money $price): void
    {
        $this->price = $price;
    }

    public function increaseStock(MovementQuantity $quantity): void
    {
        $this->stockQuantity = $this->stockQuantity->increaseBy($quantity);
    }

    public function decreaseStock(MovementQuantity $quantity): void
    {
        if ($this->stockQuantity->isLessThan($quantity)) {
            throw new InventoryDomainException('Stock quantity cannot be negative.');
        }

        $this->stockQuantity = $this->stockQuantity->decreaseBy($quantity);
    }

    public function adjustStock(StockQuantity $quantity): void
    {
        $this->stockQuantity = $quantity;
    }
}
