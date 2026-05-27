<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Entities\Product;
use App\Domain\Repositories\ProductRepositoryInterface;
use App\Domain\ValueObjects\Money;
use App\Domain\ValueObjects\ProductId;
use App\Domain\ValueObjects\ProductName;
use App\Domain\ValueObjects\Sku;
use App\Domain\ValueObjects\StockQuantity;
use App\Infrastructure\Persistence\Eloquent\Models\ProductRecord;

final class EloquentProductRepository implements ProductRepositoryInterface
{
    public function findAll(): array
    {
        return ProductRecord::query()
            ->latest()
            ->get()
            ->map(fn (ProductRecord $record): Product => $this->toDomain($record))
            ->all();
    }

    public function findById(ProductId $id): ?Product
    {
        $record = ProductRecord::query()->find($id->value());

        if ($record === null) {
            return null;
        }

        return $this->toDomain($record);
    }

    public function existsBySku(Sku $sku): bool
    {
        return ProductRecord::query()
            ->where('sku', $sku->value())
            ->exists();
    }

    public function save(Product $product): Product
    {
        $record = new ProductRecord();

        if ($product->id() !== null) {
            $record = ProductRecord::query()->find($product->id()->value()) ?? new ProductRecord();
            $record->setAttribute($record->getKeyName(), $product->id()->value());
        }

        $record->fill([
            'sku' => $product->sku()->value(),
            'name' => $product->name()->value(),
            'stock_quantity' => $product->stockQuantity()->value(),
            'price' => $this->centsToDecimalString($product->price()),
        ]);

        $record->save();

        return $this->toDomain($record);
    }

    private function toDomain(ProductRecord $record): Product
    {
        return new Product(
            id: new ProductId((int) $record->getKey()),
            sku: new Sku((string) $record->sku),
            name: new ProductName((string) $record->name),
            stockQuantity: new StockQuantity((int) $record->stock_quantity),
            price: new Money($this->decimalStringToCents((string) $record->price)),
        );
    }

    private function centsToDecimalString(Money $money): string
    {
        $cents = $money->amountInCents();
        $whole = intdiv($cents, 100);
        $fraction = $cents % 100;

        return sprintf('%d.%02d', $whole, $fraction);
    }

    private function decimalStringToCents(string $decimal): int
    {
        $normalized = trim($decimal);
        [$whole, $fraction] = array_pad(explode('.', $normalized, 2), 2, '0');
        $fraction = substr(str_pad($fraction, 2, '0'), 0, 2);

        return ((int) $whole * 100) + (int) $fraction;
    }
}
