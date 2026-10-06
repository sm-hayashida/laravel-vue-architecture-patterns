<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\InterfaceAdapters\Presenters\ProductPresenter;
use App\UseCases\Products\Ports\Output\ProductListOutputPort;
use App\UseCases\Products\Ports\Output\ProductOutputPort;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_output_ports_share_the_presenter_within_a_scope_and_reset_between_scopes(): void
    {
        $presenter = app(ProductPresenter::class);
        $this->assertSame($presenter, app(ProductOutputPort::class));
        $this->assertSame($presenter, app(ProductListOutputPort::class));

        app()->forgetScopedInstances();

        $this->assertNotSame($presenter, app(ProductPresenter::class));
    }

    public function test_product_requests_round_trip_through_http_and_database(): void
    {
        $this->getJson('/api/products')->assertOk()->assertExactJson(['data' => []]);

        $created = $this->postJson('/api/products', [
            'sku' => 'SKU-001',
            'name' => 'Notebook',
            'stock_quantity' => 5,
            'price_amount_in_cents' => 12345,
        ])->assertCreated()
            ->assertJsonPath('data.sku', 'SKU-001')
            ->assertJsonPath('data.stock_quantity', 5)
            ->assertJsonPath('data.price_amount_in_cents', 12345);

        $id = $created->json('data.id');
        $this->assertIsInt($id);
        $this->assertDatabaseHas('products', ['id' => $id, 'sku' => 'SKU-001', 'stock_quantity' => 5, 'price' => 123.45]);

        $this->postJson("/api/products/{$id}/stock", [
            'type' => 'in', 'quantity' => 3, 'operator_role' => 'staff',
        ])->assertOk()->assertJsonPath('data.stock_quantity', 8);

        $this->postJson("/api/products/{$id}/stock", [
            'type' => 'out', 'quantity' => 2, 'operator_role' => 'staff',
        ])->assertOk()->assertJsonPath('data.stock_quantity', 6);

        $this->postJson("/api/products/{$id}/stock", [
            'type' => 'adjustment', 'quantity' => 4, 'operator_role' => 'manager',
        ])->assertOk()->assertJsonPath('data.stock_quantity', 4);

        $this->getJson('/api/products')->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $id)
            ->assertJsonPath('data.0.stock_quantity', 4)
            ->assertJsonPath('data.0.price_amount_in_cents', 12345);
        $this->assertDatabaseHas('products', ['id' => $id, 'stock_quantity' => 4]);
    }

    public function test_invalid_operations_do_not_change_stock(): void
    {
        $id = $this->postJson('/api/products', [
            'sku' => 'SKU-002', 'name' => 'Pen', 'stock_quantity' => 2,
            'price_amount_in_cents' => 105,
        ])->assertCreated()->assertJsonPath('data.price_amount_in_cents', 105)->json('data.id');

        $this->postJson('/api/products', [
            'sku' => 'SKU-002', 'name' => 'Duplicate', 'stock_quantity' => 1,
            'price_amount_in_cents' => 100,
        ])->assertStatus(422)->assertJsonPath('message', 'Product SKU already exists.');

        $this->postJson("/api/products/{$id}/stock", [
            'type' => 'out', 'quantity' => 3, 'operator_role' => 'staff',
        ])->assertStatus(422);

        $this->postJson("/api/products/{$id}/stock", [
            'type' => 'adjustment', 'quantity' => 8, 'operator_role' => 'staff',
        ])->assertForbidden();

        $this->postJson('/api/products/9999/stock', [
            'type' => 'in', 'quantity' => 1, 'operator_role' => 'staff',
        ])->assertNotFound();

        $this->postJson("/api/products/{$id}/stock", [
            'type' => 'invalid', 'quantity' => -1, 'operator_role' => 'unknown',
        ])->assertUnprocessable();

        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseHas('products', ['id' => $id, 'stock_quantity' => 2]);
    }

    public function test_list_returns_products_in_id_order(): void
    {
        foreach (['First', 'Second'] as $index => $name) {
            $this->postJson('/api/products', [
                'sku' => 'SKU-00'.($index + 1),
                'name' => $name,
                'stock_quantity' => 0,
                'price_amount_in_cents' => 1,
            ])->assertCreated();
        }

        $this->getJson('/api/products')->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'First')
            ->assertJsonPath('data.1.name', 'Second');
    }
}
