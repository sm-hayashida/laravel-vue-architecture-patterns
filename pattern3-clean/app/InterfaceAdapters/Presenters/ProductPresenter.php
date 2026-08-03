<?php

declare(strict_types=1);

namespace App\InterfaceAdapters\Presenters;

use App\UseCases\Products\Outputs\ProductOutputData;
use App\UseCases\Products\Ports\Output\ProductOutputPort;
use Illuminate\Http\JsonResponse;

final class ProductPresenter implements ProductOutputPort
{
    /**
     * @var array{id: int|null, sku: string, name: string, stock_quantity: int, price_amount_in_cents: int}|null
     */
    private ?array $product = null;

    public function present(ProductOutputData $output): void
    {
        $this->product = [
            'id' => $output->id,
            'sku' => $output->sku,
            'name' => $output->name,
            'stock_quantity' => $output->stockQuantity,
            'price_amount_in_cents' => $output->priceAmountInCents,
        ];
    }

    public function jsonResponse(int $status = 200): JsonResponse
    {
        return response()->json([
            'data' => $this->product,
        ], $status);
    }
}
