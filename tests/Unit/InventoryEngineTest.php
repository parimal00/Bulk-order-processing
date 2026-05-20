<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Services\Fmcg\InventoryEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryEngineTest extends TestCase
{
    use RefreshDatabase;

    private InventoryEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new InventoryEngine();
    }

    public function test_full_allocation_when_stock_is_sufficient(): void
    {
        $product = Product::factory()->create([
            'sku' => 'TEST-SKU-1',
            'stock' => 100,
        ]);

        $result = $this->engine->allocate($product, 40);

        $this->assertEquals(40, $result['allocated_qty']);
        $this->assertEquals(0, $result['backorder_qty']);
        $this->assertEquals('allocated', $result['status']);

        // Verify stock is decremented
        $this->assertEquals(60, $product->fresh()->stock);
    }

    public function test_partial_allocation_when_stock_is_insufficient_but_greater_than_zero(): void
    {
        $product = Product::factory()->create([
            'sku' => 'TEST-SKU-2',
            'stock' => 15,
        ]);

        $result = $this->engine->allocate($product, 50);

        $this->assertEquals(15, $result['allocated_qty']);
        $this->assertEquals(35, $result['backorder_qty']);
        $this->assertEquals('partially_fulfilled', $result['status']);

        // Verify stock is fully drained
        $this->assertEquals(0, $product->fresh()->stock);
    }

    public function test_backorder_split_when_stock_is_zero(): void
    {
        $product = Product::factory()->create([
            'sku' => 'TEST-SKU-3',
            'stock' => 0,
        ]);

        $result = $this->engine->allocate($product, 25);

        $this->assertEquals(0, $result['allocated_qty']);
        $this->assertEquals(25, $result['backorder_qty']);
        $this->assertEquals('backordered', $result['status']);

        // Verify stock remains zero
        $this->assertEquals(0, $product->fresh()->stock);
    }
}
