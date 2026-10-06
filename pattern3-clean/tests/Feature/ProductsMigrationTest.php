<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class ProductsMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_products_migration_creates_the_required_sqlite_schema(): void
    {
        $this->assertTrue(Schema::hasTable('products'));
        $this->assertEqualsCanonicalizing(
            ['id', 'sku', 'name', 'stock_quantity', 'price', 'created_at', 'updated_at'],
            Schema::getColumnListing('products'),
        );

        DB::table('products')->insert([
            'sku' => 'SKU-001',
            'name' => 'Sample Product',
        ]);

        $this->assertDatabaseHas('products', [
            'sku' => 'SKU-001',
            'name' => 'Sample Product',
            'stock_quantity' => 0,
            'price' => 0,
        ]);

        $this->expectException(QueryException::class);

        DB::table('products')->insert([
            'sku' => 'SKU-001',
            'name' => 'Duplicate SKU Product',
        ]);
    }
}
