<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\InventorySnapshot;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommerceReportApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['commerce.api_key' => 'test-commerce-key']);
    }

    public function test_reporting_routes_require_the_service_api_key(): void
    {
        $this->getJson('/api/v1/reports/sales-summary?start_date=2026-10-01&end_date=2026-10-31')
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Unauthorized.');
    }

    public function test_sales_summary_returns_completed_order_metrics(): void
    {
        $customer = Customer::query()->create([
            'name' => 'Maya Chen', 'email' => 'maya@example.test', 'country' => 'JO', 'registered_at' => '2026-01-01',
        ]);
        Order::query()->create([
            'order_number' => 'ATL-API-1001', 'customer_id' => $customer->id, 'status' => Order::STATUS_COMPLETED,
            'currency' => 'USD', 'subtotal' => 120.00, 'tax_total' => 0, 'total' => 120.00, 'ordered_at' => '2026-10-10',
        ]);

        $this->withHeader('X-Commerce-Api-Key', 'test-commerce-key')
            ->getJson('/api/v1/reports/sales-summary?start_date=2026-10-01&end_date=2026-10-31')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Success')
            ->assertJsonPath('data.revenue', 120)
            ->assertJsonPath('data.orders', 1);
    }

    public function test_stock_routes_return_current_inventory_and_validate_query_input(): void
    {
        $product = Product::query()->create([
            'sku' => 'ATL-AERO-X1-BLK', 'name' => 'AeroRun X1 Black', 'category' => 'Footwear', 'unit_price' => 120.00, 'is_active' => true,
        ]);
        InventorySnapshot::query()->create([
            'product_id' => $product->id, 'quantity_on_hand' => 18, 'reorder_point' => 30, 'captured_at' => '2026-10-31 09:00:00',
        ]);

        $headers = ['X-Commerce-Api-Key' => 'test-commerce-key'];

        $this->withHeaders($headers)
            ->getJson('/api/v1/reports/products/ATL-AERO-X1-BLK/stock')
            ->assertOk()
            ->assertJsonPath('data.quantity_on_hand', 18);

        $this->withHeaders($headers)
            ->getJson('/api/v1/reports/inventory/low-stock?threshold=not-a-number')
            ->assertStatus(412)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message.0', 'The threshold field must be an integer.');
    }
}
