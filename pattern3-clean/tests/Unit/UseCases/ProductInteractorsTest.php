<?php

declare(strict_types=1);

namespace Tests\Unit\UseCases;

use App\Entities\Enums\OperatorRole;
use App\Entities\Exceptions\InventoryEntityException;
use App\Entities\Product;
use App\Entities\ValueObjects\Money;
use App\Entities\ValueObjects\MovementQuantity;
use App\Entities\ValueObjects\ProductId;
use App\Entities\ValueObjects\ProductName;
use App\Entities\ValueObjects\Sku;
use App\Entities\ValueObjects\StockQuantity;
use App\UseCases\Products\AdjustStockInput;
use App\UseCases\Products\AdjustStockInteractor;
use App\UseCases\Products\CreateProductInput;
use App\UseCases\Products\CreateProductInteractor;
use App\UseCases\Products\DecreaseStockInput;
use App\UseCases\Products\DecreaseStockInteractor;
use App\UseCases\Products\Exceptions\InventoryUseCaseException;
use App\UseCases\Products\Gateways\ProductRepositoryInterface;
use App\UseCases\Products\IncreaseStockInput;
use App\UseCases\Products\IncreaseStockInteractor;
use App\UseCases\Products\Outputs\ProductOutputData;
use App\UseCases\Products\Ports\Output\ProductOutputPort;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ProductInteractorsTest extends TestCase
{
    public function test_create_product_presents_output_data_without_http_or_database(): void
    {
        $products = $this->productRepository();
        $output = $this->productOutput();

        $products->expects($this->once())
            ->method('existsBySku')
            ->with($this->callback(static fn (Sku $sku): bool => $sku->value() === 'SKU-001'))
            ->willReturn(false);

        $products->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Product $product): bool {
                $this->assertNull($product->id());
                $this->assertSame('SKU-001', $product->sku()->value());
                $this->assertSame('Sample Product', $product->name()->value());
                $this->assertSame(10, $product->stockQuantity()->value());
                $this->assertSame(1200, $product->price()->amountInCents());

                return true;
            }))
            ->willReturn($this->product(id: 1, sku: 'SKU-001', name: 'Sample Product', stockQuantity: 10, price: 1200));

        $output->expects($this->once())
            ->method('present')
            ->with($this->callback(function (ProductOutputData $data): bool {
                $this->assertProductOutput(
                    data: $data,
                    id: 1,
                    sku: 'SKU-001',
                    name: 'Sample Product',
                    stockQuantity: 10,
                    price: 1200,
                );

                return true;
            }));

        $interactor = new CreateProductInteractor($products, $output);

        $interactor->execute(new CreateProductInput(
            sku: new Sku('SKU-001'),
            name: new ProductName('Sample Product'),
            initialStock: new StockQuantity(10),
            price: new Money(1200),
        ));
    }

    public function test_duplicate_sku_is_rejected_before_save_or_output(): void
    {
        $products = $this->productRepository();
        $output = $this->productOutput();

        $products->expects($this->once())
            ->method('existsBySku')
            ->with($this->callback(static fn (Sku $sku): bool => $sku->value() === 'SKU-001'))
            ->willReturn(true);
        $products->expects($this->never())->method('save');
        $output->expects($this->never())->method('present');

        $interactor = new CreateProductInteractor($products, $output);

        $this->expectException(InventoryUseCaseException::class);
        $this->expectExceptionMessage('Product SKU already exists.');

        $interactor->execute(new CreateProductInput(
            sku: new Sku('SKU-001'),
            name: new ProductName('Duplicate Product'),
            initialStock: new StockQuantity(1),
            price: new Money(500),
        ));
    }

    public function test_increase_stock_saves_and_presents_updated_product(): void
    {
        $products = $this->productRepository();
        $output = $this->productOutput();

        $products->expects($this->once())
            ->method('findById')
            ->with($this->callback(static fn (ProductId $id): bool => $id->value() === 1))
            ->willReturn($this->product(id: 1, stockQuantity: 10));

        $products->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Product $product): bool {
                $this->assertSame(15, $product->stockQuantity()->value());

                return true;
            }))
            ->willReturnCallback(static fn (Product $product): Product => $product);

        $output->expects($this->once())
            ->method('present')
            ->with($this->callback(function (ProductOutputData $data): bool {
                $this->assertProductOutput($data, id: 1, sku: 'SKU-001', name: 'Sample Product', stockQuantity: 15, price: 1200);

                return true;
            }));

        $interactor = new IncreaseStockInteractor($products, $output);

        $interactor->execute(new IncreaseStockInput(
            productId: new ProductId(1),
            quantity: new MovementQuantity(5),
        ));
    }

    public function test_decrease_stock_saves_and_presents_updated_product(): void
    {
        $products = $this->productRepository();
        $output = $this->productOutput();

        $products->expects($this->once())
            ->method('findById')
            ->with($this->callback(static fn (ProductId $id): bool => $id->value() === 1))
            ->willReturn($this->product(id: 1, stockQuantity: 10));

        $products->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Product $product): bool {
                $this->assertSame(7, $product->stockQuantity()->value());

                return true;
            }))
            ->willReturnCallback(static fn (Product $product): Product => $product);

        $output->expects($this->once())
            ->method('present')
            ->with($this->callback(function (ProductOutputData $data): bool {
                $this->assertProductOutput($data, id: 1, sku: 'SKU-001', name: 'Sample Product', stockQuantity: 7, price: 1200);

                return true;
            }));

        $interactor = new DecreaseStockInteractor($products, $output);

        $interactor->execute(new DecreaseStockInput(
            productId: new ProductId(1),
            quantity: new MovementQuantity(3),
        ));
    }

    public function test_missing_product_is_rejected_before_save_or_output(): void
    {
        $products = $this->productRepository();
        $output = $this->productOutput();

        $products->expects($this->once())
            ->method('findById')
            ->with($this->callback(static fn (ProductId $id): bool => $id->value() === 999))
            ->willReturn(null);
        $products->expects($this->never())->method('save');
        $output->expects($this->never())->method('present');

        $interactor = new IncreaseStockInteractor($products, $output);

        $this->expectException(InventoryUseCaseException::class);
        $this->expectExceptionMessage('Product was not found.');

        $interactor->execute(new IncreaseStockInput(
            productId: new ProductId(999),
            quantity: new MovementQuantity(1),
        ));
    }

    public function test_decrease_below_zero_is_rejected_before_save_or_output(): void
    {
        $products = $this->productRepository();
        $output = $this->productOutput();

        $products->expects($this->once())
            ->method('findById')
            ->with($this->callback(static fn (ProductId $id): bool => $id->value() === 1))
            ->willReturn($this->product(id: 1, stockQuantity: 2));
        $products->expects($this->never())->method('save');
        $output->expects($this->never())->method('present');

        $interactor = new DecreaseStockInteractor($products, $output);

        $this->expectException(InventoryEntityException::class);
        $this->expectExceptionMessage('Stock quantity cannot be negative.');

        $interactor->execute(new DecreaseStockInput(
            productId: new ProductId(1),
            quantity: new MovementQuantity(3),
        ));
    }

    public function test_manager_can_adjust_stock_and_present_output(): void
    {
        $products = $this->productRepository();
        $output = $this->productOutput();

        $products->expects($this->once())
            ->method('findById')
            ->with($this->callback(static fn (ProductId $id): bool => $id->value() === 1))
            ->willReturn($this->product(id: 1, stockQuantity: 4));

        $products->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Product $product): bool {
                $this->assertSame(20, $product->stockQuantity()->value());

                return true;
            }))
            ->willReturnCallback(static fn (Product $product): Product => $product);

        $output->expects($this->once())
            ->method('present')
            ->with($this->callback(function (ProductOutputData $data): bool {
                $this->assertProductOutput($data, id: 1, sku: 'SKU-001', name: 'Sample Product', stockQuantity: 20, price: 1200);

                return true;
            }));

        $interactor = new AdjustStockInteractor($products, $output);

        $interactor->execute(new AdjustStockInput(
            productId: new ProductId(1),
            quantity: new StockQuantity(20),
            operatorRole: OperatorRole::Manager,
        ));
    }

    public function test_staff_adjustment_is_rejected_before_repository_or_output_work(): void
    {
        $products = $this->productRepository();
        $output = $this->productOutput();

        $products->expects($this->never())->method('findById');
        $products->expects($this->never())->method('existsBySku');
        $products->expects($this->never())->method('save');
        $output->expects($this->never())->method('present');

        $interactor = new AdjustStockInteractor($products, $output);

        $this->expectException(InventoryUseCaseException::class);
        $this->expectExceptionMessage('Only managers can adjust stock directly.');

        $interactor->execute(new AdjustStockInput(
            productId: new ProductId(1),
            quantity: new StockQuantity(20),
            operatorRole: OperatorRole::Staff,
        ));
    }

    private function productRepository(): ProductRepositoryInterface&MockObject
    {
        return $this->createMock(ProductRepositoryInterface::class);
    }

    private function productOutput(): ProductOutputPort&MockObject
    {
        return $this->createMock(ProductOutputPort::class);
    }

    private function product(
        int $id,
        string $sku = 'SKU-001',
        string $name = 'Sample Product',
        int $stockQuantity = 10,
        int $price = 1200,
    ): Product {
        return new Product(
            id: new ProductId($id),
            sku: new Sku($sku),
            name: new ProductName($name),
            stockQuantity: new StockQuantity($stockQuantity),
            price: new Money($price),
        );
    }

    private function assertProductOutput(
        ProductOutputData $data,
        int $id,
        string $sku,
        string $name,
        int $stockQuantity,
        int $price,
    ): void {
        $this->assertSame($id, $data->id);
        $this->assertSame($sku, $data->sku);
        $this->assertSame($name, $data->name);
        $this->assertSame($stockQuantity, $data->stockQuantity);
        $this->assertSame($price, $data->priceAmountInCents);
    }
}
