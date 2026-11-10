<?php

namespace Tests\Feature;

use App\Mcp\Servers\AtlasCommerceServer;
use App\Mcp\Tools\CompareSalesPeriodsTool;
use App\Mcp\Tools\FindLowStockProductsTool;
use App\Mcp\Tools\GetProductStockTool;
use App\Mcp\Tools\GetSalesSummaryTool;
use App\Mcp\Tools\GetTopProductsTool;
use App\Models\Customer;
use App\Models\InventorySnapshot;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AtlasCommerceMcpServerTest extends TestCase
{
    use RefreshDatabase;

    public function test_server_discovers_only_the_approved_read_only_tools(): void
    {
        AtlasCommerceServer::tools()->assertRegistered([
            GetSalesSummaryTool::class,
            CompareSalesPeriodsTool::class,
            GetTopProductsTool::class,
            GetProductStockTool::class,
            FindLowStockProductsTool::class,
        ]);
    }

    public function test_sales_summary_tool_returns_structured_facts(): void
    {
        $customer = Customer::query()->create([
            'name' => 'Maya Chen', 'email' => 'maya@example.test', 'country' => 'JO', 'registered_at' => '2026-01-01',
        ]);
        Order::query()->create([
            'order_number' => 'ATL-MCP-1001', 'customer_id' => $customer->id, 'status' => Order::STATUS_COMPLETED,
            'currency' => 'USD', 'subtotal' => 240.00, 'tax_total' => 0, 'total' => 240.00, 'ordered_at' => '2026-10-10',
        ]);

        AtlasCommerceServer::tool(GetSalesSummaryTool::class, [
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
        ])->assertOk()->assertStructuredContent([
            'currency' => 'USD',
            'revenue' => 240,
            'orders' => 1,
            'average_order_value' => 240,
            'refunded_revenue' => 0,
        ]);
    }

    public function test_stock_tool_returns_facts_and_a_safe_not_found_error(): void
    {
        $product = Product::query()->create([
            'sku' => 'ATL-AERO-X1-BLK', 'name' => 'AeroRun X1 Black', 'category' => 'Footwear', 'unit_price' => 120.00, 'is_active' => true,
        ]);
        InventorySnapshot::query()->create([
            'product_id' => $product->id, 'quantity_on_hand' => 18, 'reorder_point' => 30, 'captured_at' => '2026-10-31 09:00:00',
        ]);

        AtlasCommerceServer::tool(GetProductStockTool::class, ['sku' => 'ATL-AERO-X1-BLK'])
            ->assertOk()
            ->assertStructuredContent(fn ($json) => $json->where('sku', 'ATL-AERO-X1-BLK')->where('quantity_on_hand', 18)->etc());

        AtlasCommerceServer::tool(GetProductStockTool::class, ['sku' => 'UNKNOWN-SKU'])
            ->assertHasErrors(['Product stock was not found']);
    }

    public function test_tool_validation_rejects_an_invalid_date_range(): void
    {
        AtlasCommerceServer::tool(GetSalesSummaryTool::class, [
            'start_date' => 'not-a-date',
            'end_date' => '2026-10-31',
        ])->assertHasErrors(['start date']);
    }
}
