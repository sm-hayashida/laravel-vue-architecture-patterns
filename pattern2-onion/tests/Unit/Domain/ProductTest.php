<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Entities\Product;
use App\Domain\Enums\OperatorRole;
use App\Domain\Exceptions\InventoryDomainException;
use App\Domain\Services\StockAdjustmentPolicy;
use App\Domain\ValueObjects\Money;
use App\Domain\ValueObjects\MovementQuantity;
use App\Domain\ValueObjects\ProductId;
use App\Domain\ValueObjects\ProductName;
use App\Domain\ValueObjects\Sku;
use App\Domain\ValueObjects\StockQuantity;
use PHPUnit\Framework\TestCase;

final class ProductTest extends TestCase
{
    public function test_product_stock_can_be_increased_decreased_and_adjusted(): void
    {
        $product = $this->product(stockQuantity: 10);

        $product->increaseStock(new MovementQuantity(5));
        $this->assertSame(15, $product->stockQuantity()->value());

        $product->decreaseStock(new MovementQuantity(4));
        $this->assertSame(11, $product->stockQuantity()->value());

        $product->adjustStock(new StockQuantity(2));
        $this->assertSame(2, $product->stockQuantity()->value());
    }

    public function test_product_rejects_decrease_below_zero(): void
    {
        $product = $this->product(stockQuantity: 3);

        $this->expectException(InventoryDomainException::class);
        $this->expectExceptionMessage('Stock quantity cannot be negative.');

        $product->decreaseStock(new MovementQuantity(4));
    }

    public function test_stock_adjustment_policy_allows_only_managers(): void
    {
        $policy = new StockAdjustmentPolicy();

        $this->assertTrue($policy->canAdjust(OperatorRole::Manager));
        $this->assertFalse($policy->canAdjust(OperatorRole::Staff));

        $this->expectException(InventoryDomainException::class);
        $this->expectExceptionMessage('Only managers can adjust stock directly.');

        $policy->assertCanAdjust(OperatorRole::Staff);
    }

    public function test_value_objects_reject_invalid_values(): void
    {
        $this->expectException(InventoryDomainException::class);

        new MovementQuantity(0);
    }

    private function product(int $stockQuantity): Product
    {
        return new Product(
            id: new ProductId(1),
            sku: new Sku('SKU-001'),
            name: new ProductName('Sample Product'),
            stockQuantity: new StockQuantity($stockQuantity),
            price: new Money(1200),
        );
    }
}
