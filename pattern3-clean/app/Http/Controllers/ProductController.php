<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Entities\Enums\OperatorRole;
use App\Entities\Exceptions\InventoryEntityException;
use App\Entities\ValueObjects\Money;
use App\Entities\ValueObjects\MovementQuantity;
use App\Entities\ValueObjects\ProductId;
use App\Entities\ValueObjects\ProductName;
use App\Entities\ValueObjects\Sku;
use App\Entities\ValueObjects\StockQuantity;
use App\InterfaceAdapters\Presenters\ProductPresenter;
use App\UseCases\Products\AdjustStockInput;
use App\UseCases\Products\CreateProductInput;
use App\UseCases\Products\DecreaseStockInput;
use App\UseCases\Products\Exceptions\InventoryUseCaseException;
use App\UseCases\Products\IncreaseStockInput;
use App\UseCases\Products\Ports\Input\AdjustStockInputPort;
use App\UseCases\Products\Ports\Input\CreateProductInputPort;
use App\UseCases\Products\Ports\Input\DecreaseStockInputPort;
use App\UseCases\Products\Ports\Input\IncreaseStockInputPort;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Throwable;

final class ProductController extends Controller
{
    public function __construct(
        private readonly CreateProductInputPort $createProduct,
        private readonly IncreaseStockInputPort $increaseStock,
        private readonly DecreaseStockInputPort $decreaseStock,
        private readonly AdjustStockInputPort $adjustStock,
        private readonly ProductPresenter $presenter,
    ) {
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
            $this->createProduct->execute(new CreateProductInput(
                sku: new Sku($validated['sku']),
                name: new ProductName($validated['name']),
                initialStock: new StockQuantity((int) $validated['stock_quantity']),
                price: new Money((int) $validated['price_amount_in_cents']),
            ));
        } catch (InventoryEntityException | InventoryUseCaseException $exception) {
            return $this->errorResponse($exception, 422);
        }

        return $this->presenter->jsonResponse(201);
    }

    public function updateStock(Request $request, int $productId): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(['in', 'out', 'adjustment'])],
            'quantity' => ['required', 'integer', 'min:0'],
            'operator_role' => ['required', Rule::enum(OperatorRole::class)],
        ]);

        try {
            $this->executeStockOperation(
                type: $validated['type'],
                productId: new ProductId($productId),
                quantity: (int) $validated['quantity'],
                operatorRole: OperatorRole::from($validated['operator_role']),
            );
        } catch (InventoryEntityException $exception) {
            return $this->errorResponse($exception, 422);
        } catch (InventoryUseCaseException $exception) {
            $status = $exception->getMessage() === 'Only managers can adjust stock directly.' ? 403 : 404;

            return $this->errorResponse($exception, $status);
        }

        return $this->presenter->jsonResponse();
    }

    /**
     * @throws InventoryEntityException
     * @throws InventoryUseCaseException
     */
    private function executeStockOperation(
        string $type,
        ProductId $productId,
        int $quantity,
        OperatorRole $operatorRole,
    ): void {
        match ($type) {
            'in' => $this->increaseStock->execute(new IncreaseStockInput(
                productId: $productId,
                quantity: new MovementQuantity($quantity),
            )),
            'out' => $this->decreaseStock->execute(new DecreaseStockInput(
                productId: $productId,
                quantity: new MovementQuantity($quantity),
            )),
            'adjustment' => $this->adjustStock->execute(new AdjustStockInput(
                productId: $productId,
                quantity: new StockQuantity($quantity),
                operatorRole: $operatorRole,
            )),
        };
    }

    private function errorResponse(Throwable $exception, int $status): JsonResponse
    {
        return response()->json([
            'message' => $exception->getMessage(),
        ], $status);
    }
}
