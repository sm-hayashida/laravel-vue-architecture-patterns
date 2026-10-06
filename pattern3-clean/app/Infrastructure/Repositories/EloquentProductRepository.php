<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Entities\Product;
use App\Entities\ValueObjects\Money;
use App\Entities\ValueObjects\ProductId;
use App\Entities\ValueObjects\ProductName;
use App\Entities\ValueObjects\Sku;
use App\Entities\ValueObjects\StockQuantity;
use App\Infrastructure\Persistence\Eloquent\Models\ProductRecord;
use App\UseCases\Products\Gateways\ProductRepositoryInterface;

final class EloquentProductRepository implements ProductRepositoryInterface
{
    public function findAll(): array
    {
        return ProductRecord::query()
            ->orderBy('id')
            ->get()
            ->map(fn (ProductRecord $record): Product => $this->toEntity($record))
            ->all();
    }

    public function findById(ProductId $id): ?Product
    {
        $record = ProductRecord::query()->find($id->value());

        return $record === null ? null : $this->toEntity($record);
    }

    public function existsBySku(Sku $sku): bool
    {
        return ProductRecord::query()->where('sku', $sku->value())->exists();
    }

    public function save(Product $product): Product
    {
        $record = $product->id() === null
            ? new ProductRecord()
            : ProductRecord::query()->findOrFail($product->id()->value());

        $cents = $product->price()->amountInCents();
        $record->fill([
            'sku' => $product->sku()->value(),
            'name' => $product->name()->value(),
            'stock_quantity' => $product->stockQuantity()->value(),
            'price' => sprintf('%d.%02d', intdiv($cents, 100), $cents % 100),
        ]);
        $record->save();

        return $this->toEntity($record);
    }

    private function toEntity(ProductRecord $record): Product
    {
        [$whole, $fraction] = explode('.', (string) $record->price);

        return new Product(
            id: new ProductId((int) $record->getKey()),
            sku: new Sku((string) $record->sku),
            name: new ProductName((string) $record->name),
            stockQuantity: new StockQuantity((int) $record->stock_quantity),
            price: new Money(((int) $whole * 100) + (int) str_pad($fraction, 2, '0')),
        );
    }
}
