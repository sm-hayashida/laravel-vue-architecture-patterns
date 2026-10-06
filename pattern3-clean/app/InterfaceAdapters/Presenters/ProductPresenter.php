<?php

declare(strict_types=1);

namespace App\InterfaceAdapters\Presenters;

use App\UseCases\Products\Outputs\ProductOutputData;
use App\UseCases\Products\Ports\Output\ProductListOutputPort;
use App\UseCases\Products\Ports\Output\ProductOutputPort;
use Illuminate\Http\JsonResponse;

final class ProductPresenter implements ProductOutputPort, ProductListOutputPort
{
    private array $data = [];

    public function present(ProductOutputData $output): void
    {
        $this->data = $this->formatProduct($output);
    }

    public function presentList(array $products): void
    {
        $this->data = array_map($this->formatProduct(...), $products);
    }

    private function formatProduct(ProductOutputData $output): array
    {
        return [
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
            'data' => $this->data,
        ], $status);
    }
}
