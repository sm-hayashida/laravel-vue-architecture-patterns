<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Exceptions\InventoryApplicationException;
use App\Application\Services\ProductInventoryService;
use App\Domain\Entities\Product;
use App\Domain\Enums\OperatorRole;
use App\Domain\Enums\StockMovementType;
use App\Domain\Exceptions\InventoryDomainException;
use App\Domain\ValueObjects\Money;
use App\Domain\ValueObjects\MovementQuantity;
use App\Domain\ValueObjects\ProductId;
use App\Domain\ValueObjects\ProductName;
use App\Domain\ValueObjects\Sku;
use App\Domain\ValueObjects\StockQuantity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Throwable;

final class ProductController extends Controller
{
    public function __construct(private readonly ProductInventoryService $products)
    {
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => array_map(
                fn (Product $product): array => $this->productResponse($product),
                $this->products->listProducts(),
            ),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sku' => ['required', 'string', 'max:64'],
            'name' => ['required', 'string', 'max:255'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'price_amount_in_cents' => ['required', 'integer', 'min:0'],
        ]);

        try {
            $product = $this->products->createProduct(
                sku: new Sku($validated['sku']),
                name: new ProductName($validated['name']),
                initialStock: new StockQuantity($validated['stock_quantity']),
                price: new Money($validated['price_amount_in_cents']),
            );
        } catch (InventoryApplicationException | InventoryDomainException $exception) {
            return $this->errorResponse($exception, 422);
        }

        return response()->json([
            'data' => $this->productResponse($product),
        ], 201);
    }

    public function updateStock(Request $request, int $productId): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', Rule::enum(StockMovementType::class)],
            'quantity' => ['required', 'integer', 'min:0'],
            'operator_role' => ['required', Rule::enum(OperatorRole::class)],
        ]);

        try {
            $product = $this->applyStockOperation(
                productId: new ProductId($productId),
                type: StockMovementType::from($validated['type']),
                quantity: (int) $validated['quantity'],
                operatorRole: OperatorRole::from($validated['operator_role']),
            );
        } catch (InventoryDomainException $exception) {
            $status = $exception->getMessage() === 'Only managers can adjust stock directly.' ? 403 : 422;

            return $this->errorResponse($exception, $status);
        } catch (InventoryApplicationException $exception) {
            return $this->errorResponse($exception, 404);
        }

        return response()->json([
            'data' => $this->productResponse($product),
        ]);
    }

    private function applyStockOperation(
        ProductId $productId,
        StockMovementType $type,
        int $quantity,
        OperatorRole $operatorRole,
    ): Product {
        return match ($type) {
            StockMovementType::In => $this->products->increaseStock(
                productId: $productId,
                quantity: new MovementQuantity($quantity),
            ),
            StockMovementType::Out => $this->products->decreaseStock(
                productId: $productId,
                quantity: new MovementQuantity($quantity),
            ),
            StockMovementType::Adjustment => $this->products->adjustStock(
                productId: $productId,
                quantity: new StockQuantity($quantity),
                operatorRole: $operatorRole,
            ),
        };
    }

    private function productResponse(Product $product): array
    {
        return [
            'id' => $product->id()?->value(),
            'sku' => $product->sku()->value(),
            'name' => $product->name()->value(),
            'stock_quantity' => $product->stockQuantity()->value(),
            'price_amount_in_cents' => $product->price()->amountInCents(),
        ];
    }

    private function errorResponse(Throwable $exception, int $status): JsonResponse
    {
        return response()->json([
            'message' => $exception->getMessage(),
        ], $status);
    }
}
