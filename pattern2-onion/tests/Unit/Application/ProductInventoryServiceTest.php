<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\Exceptions\InventoryApplicationException;
use App\Application\Services\ProductInventoryService;
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
use Tests\Support\InMemoryProductRepository;

final class ProductInventoryServiceTest extends TestCase
{
    public function test_product_can_be_created_without_http_or_database(): void
    {
        $repository = new InMemoryProductRepository();
        $service = $this->service($repository);

        $product = $service->createProduct(
            sku: new Sku('SKU-001'),
            name: new ProductName('Sample Product'),
            initialStock: new StockQuantity(10),
            price: new Money(1200),
        );

        $this->assertSame(1, $product->id()?->value());
        $this->assertSame('SKU-001', $product->sku()->value());
        $this->assertSame(10, $product->stockQuantity()->value());
        $this->assertSame(1, $repository->saveCount);
    }

    public function test_duplicate_sku_is_rejected_before_saving(): void
    {
        $repository = new InMemoryProductRepository($this->product(id: 1, sku: 'SKU-001', stockQuantity: 10));
        $service = $this->service($repository);

        $this->expectException(InventoryApplicationException::class);
        $this->expectExceptionMessage('Product SKU already exists.');

        try {
            $service->createProduct(
                sku: new Sku('SKU-001'),
                name: new ProductName('Duplicate Product'),
                initialStock: new StockQuantity(1),
                price: new Money(500),
            );
        } finally {
            $this->assertSame(0, $repository->saveCount);
        }
    }

    public function test_stock_operations_delegate_rules_to_domain_and_save_changed_product(): void
    {
        $repository = new InMemoryProductRepository($this->product(id: 1, sku: 'SKU-001', stockQuantity: 10));
        $service = $this->service($repository);

        $increased = $service->increaseStock(new ProductId(1), new MovementQuantity(5));
        $this->assertSame(15, $increased->stockQuantity()->value());

        $decreased = $service->decreaseStock(new ProductId(1), new MovementQuantity(3));
        $this->assertSame(12, $decreased->stockQuantity()->value());

        $adjusted = $service->adjustStock(new ProductId(1), new StockQuantity(20), OperatorRole::Manager);
        $this->assertSame(20, $adjusted->stockQuantity()->value());
        $this->assertSame(3, $repository->saveCount);
    }

    public function test_staff_cannot_adjust_stock_and_product_is_not_saved(): void
    {
        $repository = new InMemoryProductRepository($this->product(id: 1, sku: 'SKU-001', stockQuantity: 10));
        $service = $this->service($repository);

        $this->expectException(InventoryDomainException::class);
        $this->expectExceptionMessage('Only managers can adjust stock directly.');

        try {
            $service->adjustStock(new ProductId(1), new StockQuantity(20), OperatorRole::Staff);
        } finally {
            $product = $repository->findById(new ProductId(1));

            $this->assertSame(10, $product?->stockQuantity()->value());
            $this->assertSame(0, $repository->saveCount);
        }
    }

    public function test_missing_product_is_an_application_failure(): void
    {
        $service = $this->service(new InMemoryProductRepository());

        $this->expectException(InventoryApplicationException::class);
        $this->expectExceptionMessage('Product was not found.');

        $service->increaseStock(new ProductId(999), new MovementQuantity(1));
    }

    private function service(InMemoryProductRepository $repository): ProductInventoryService
    {
        return new ProductInventoryService(
            products: $repository,
            stockAdjustmentPolicy: new StockAdjustmentPolicy(),
        );
    }

    private function product(int $id, string $sku, int $stockQuantity): Product
    {
        return new Product(
            id: new ProductId($id),
            sku: new Sku($sku),
            name: new ProductName('Sample Product'),
            stockQuantity: new StockQuantity($stockQuantity),
            price: new Money(1200),
        );
    }
}
