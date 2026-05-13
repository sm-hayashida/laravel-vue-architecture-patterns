<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class ProductController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => Product::query()
                ->latest()
                ->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sku' => ['required', 'string', 'max:64', 'unique:products,sku'],
            'name' => ['required', 'string', 'max:255'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'price' => ['required', 'numeric', 'min:0'],
        ]);

        $product = DB::transaction(function () use ($validated): Product {
            $product = Product::create($validated);

            if ($product->stock_quantity > 0) {
                $product->stockMovements()->create([
                    'type' => Product::MOVEMENT_ADJUSTMENT,
                    'quantity' => $product->stock_quantity,
                    'reason' => 'Initial stock',
                ]);
            }

            return $product;
        });

        return response()->json([
            'data' => $product->fresh('stockMovements'),
        ], 201);
    }

    public function updateStock(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in([
                Product::MOVEMENT_IN,
                Product::MOVEMENT_OUT,
                Product::MOVEMENT_ADJUSTMENT,
            ])],
            'quantity' => ['required', 'integer', 'min:0'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'operator_role' => ['required', 'string', Rule::in(['staff', 'manager'])],
        ]);

        if ($validated['type'] === Product::MOVEMENT_ADJUSTMENT && $validated['operator_role'] !== 'manager') {
            return response()->json([
                'message' => 'Only managers can adjust stock directly.',
            ], 403);
        }

        if ($validated['type'] !== Product::MOVEMENT_ADJUSTMENT && $validated['quantity'] <= 0) {
            return response()->json([
                'message' => 'Quantity must be greater than zero.',
            ], 422);
        }

        try {
            DB::transaction(function () use ($product, $validated): void {
                $lockedProduct = Product::query()
                    ->whereKey($product->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($validated['type'] === Product::MOVEMENT_IN) {
                    $lockedProduct->increaseStock($validated['quantity'], $validated['reason'] ?? null);

                    return;
                }

                if ($validated['type'] === Product::MOVEMENT_OUT) {
                    $lockedProduct->decreaseStock($validated['quantity'], $validated['reason'] ?? null);

                    return;
                }

                $lockedProduct->adjustStock($validated['quantity'], $validated['reason'] ?? null);
            });
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'data' => $product->fresh('stockMovements'),
        ]);
    }
}
