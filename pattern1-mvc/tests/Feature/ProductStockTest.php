<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductStockTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_creation_records_initial_stock_movement(): void
    {
        $response = $this->postJson('/api/products', [
            'sku' => 'SKU-001',
            'name' => 'Sample Product',
            'stock_quantity' => 10,
            'price' => 1200,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.sku', 'SKU-001')
            ->assertJsonPath('data.stock_quantity', 10);

        $this->assertDatabaseHas('products', [
            'sku' => 'SKU-001',
            'stock_quantity' => 10,
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'type' => Product::MOVEMENT_ADJUSTMENT,
            'quantity' => 10,
            'reason' => 'Initial stock',
        ]);
    }

    public function test_stock_can_be_increased_and_decreased(): void
    {
        $product = Product::create([
            'sku' => 'SKU-002',
            'name' => 'Stock Product',
            'stock_quantity' => 5,
            'price' => 500,
        ]);

        $this->postJson("/api/products/{$product->id}/stock", [
            'type' => Product::MOVEMENT_IN,
            'quantity' => 7,
            'reason' => 'Restocked',
            'operator_role' => 'staff',
        ])->assertOk()
            ->assertJsonPath('data.stock_quantity', 12);

        $this->postJson("/api/products/{$product->id}/stock", [
            'type' => Product::MOVEMENT_OUT,
            'quantity' => 3,
            'reason' => 'Shipped',
            'operator_role' => 'staff',
        ])->assertOk()
            ->assertJsonPath('data.stock_quantity', 9);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => Product::MOVEMENT_IN,
            'quantity' => 7,
            'reason' => 'Restocked',
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => Product::MOVEMENT_OUT,
            'quantity' => 3,
            'reason' => 'Shipped',
        ]);
    }

    public function test_stock_cannot_be_decreased_below_zero(): void
    {
        $product = Product::create([
            'sku' => 'SKU-003',
            'name' => 'Limited Product',
            'stock_quantity' => 2,
            'price' => 800,
        ]);

        $this->postJson("/api/products/{$product->id}/stock", [
            'type' => Product::MOVEMENT_OUT,
            'quantity' => 3,
            'operator_role' => 'staff',
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'Stock quantity cannot be negative.');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock_quantity' => 2,
        ]);
    }

    public function test_only_manager_can_adjust_stock_directly(): void
    {
        $product = Product::create([
            'sku' => 'SKU-004',
            'name' => 'Adjustable Product',
            'stock_quantity' => 4,
            'price' => 300,
        ]);

        $this->postJson("/api/products/{$product->id}/stock", [
            'type' => Product::MOVEMENT_ADJUSTMENT,
            'quantity' => 20,
            'operator_role' => 'staff',
        ])->assertForbidden()
            ->assertJsonPath('message', 'Only managers can adjust stock directly.');

        $this->postJson("/api/products/{$product->id}/stock", [
            'type' => Product::MOVEMENT_ADJUSTMENT,
            'quantity' => 20,
            'reason' => 'Inventory count',
            'operator_role' => 'manager',
        ])->assertOk()
            ->assertJsonPath('data.stock_quantity', 20);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => Product::MOVEMENT_ADJUSTMENT,
            'quantity' => 20,
            'reason' => 'Inventory count',
        ]);
    }
}
