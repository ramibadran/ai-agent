<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\DailyProductSale;
use App\Models\InventorySnapshot;
use App\Models\Order;
use App\Models\Product;
use App\Services\CommerceReportingService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommerceReportingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_calculates_completed_sales_and_refunds_for_a_period(): void
    {
        $customer = Customer::query()->create([
            'name' => 'Maya Chen', 'email' => 'maya@example.test', 'country' => 'JO', 'registered_at' => '2026-01-01',
        ]);
        $this->createOrder($customer, 'ATL-1001', Order::STATUS_COMPLETED, '2026-10-03', 120.00);
        $this->createOrder($customer, 'ATL-1002', Order::STATUS_COMPLETED, '2026-10-12', 80.00);
        $this->createOrder($customer, 'ATL-1003', Order::STATUS_REFUNDED, '2026-10-18', 25.00);
        $this->createOrder($customer, 'ATL-1004', Order::STATUS_CANCELLED, '2026-10-21', 50.00);

        $summary = app(CommerceReportingService::class)->salesSummary(
            CarbonImmutable::parse('2026-10-01'),
            CarbonImmutable::parse('2026-10-31'),
        );

        $this->assertSame(200.0, $summary['revenue']);
        $this->assertSame(2, $summary['orders']);
        $this->assertSame(100.0, $summary['average_order_value']);
        $this->assertSame(25.0, $summary['refunded_revenue']);
    }

    public function test_it_compares_two_sales_periods(): void
    {
        $customer = Customer::query()->create([
            'name' => 'Maya Chen', 'email' => 'maya@example.test', 'country' => 'JO', 'registered_at' => '2026-01-01',
        ]);
        $this->createOrder($customer, 'ATL-2001', Order::STATUS_COMPLETED, '2026-09-10', 100.00);
        $this->createOrder($customer, 'ATL-2002', Order::STATUS_COMPLETED, '2026-10-10', 80.00);

        $comparison = app(CommerceReportingService::class)->compareSalesPeriods(
            CarbonImmutable::parse('2026-10-01'), CarbonImmutable::parse('2026-10-31'),
            CarbonImmutable::parse('2026-09-01'), CarbonImmutable::parse('2026-09-30'),
        );

        $this->assertSame(-20.0, $comparison['revenue_change_percent']);
        $this->assertSame(0.0, $comparison['order_change_percent']);
    }

    public function test_it_ranks_products_by_revenue_or_units_sold(): void
    {
        $aero = $this->product('ATL-AERO-X1-BLK', 'AeroRun X1 Black', 120.00);
        $pack = $this->product('ATL-PACK-PRO', 'CityPack Pro', 80.00);
        DailyProductSale::query()->create(['product_id' => $aero->id, 'sale_date' => '2026-10-01', 'units_sold' => 4, 'order_count' => 3, 'gross_revenue' => 480.00]);
        DailyProductSale::query()->create(['product_id' => $pack->id, 'sale_date' => '2026-10-01', 'units_sold' => 8, 'order_count' => 6, 'gross_revenue' => 640.00]);
        $this->assertDatabaseCount('daily_product_sales', 2);

        $service = app(CommerceReportingService::class);

        $this->assertSame('ATL-PACK-PRO', $service->topProducts(CarbonImmutable::parse('2026-10-01'), CarbonImmutable::parse('2026-10-01'), 'revenue', 5)[0]['sku']);
        $this->assertSame('ATL-PACK-PRO', $service->topProducts(CarbonImmutable::parse('2026-10-01'), CarbonImmutable::parse('2026-10-01'), 'units_sold', 5)[0]['sku']);
    }

    public function test_it_returns_only_the_latest_stock_snapshot_for_each_product(): void
    {
        $aero = $this->product('ATL-AERO-X1-BLK', 'AeroRun X1 Black', 120.00);
        $pack = $this->product('ATL-PACK-PRO', 'CityPack Pro', 80.00);
        InventorySnapshot::query()->create(['product_id' => $aero->id, 'quantity_on_hand' => 5, 'reorder_point' => 20, 'captured_at' => '2026-10-01 09:00:00']);
        InventorySnapshot::query()->create(['product_id' => $aero->id, 'quantity_on_hand' => 45, 'reorder_point' => 20, 'captured_at' => '2026-10-02 09:00:00']);
        InventorySnapshot::query()->create(['product_id' => $pack->id, 'quantity_on_hand' => 11, 'reorder_point' => 20, 'captured_at' => '2026-10-02 09:00:00']);

        $service = app(CommerceReportingService::class);
        $stock = $service->productStock('ATL-AERO-X1-BLK');
        $lowStock = $service->lowStockProducts(20, 10);

        $this->assertSame(45, $stock['quantity_on_hand']);
        $this->assertSame(['ATL-PACK-PRO'], array_column($lowStock, 'sku'));
    }

    private function product(string $sku, string $name, float $unitPrice): Product
    {
        return Product::query()->create([
            'sku' => $sku, 'name' => $name, 'category' => 'Outdoor', 'unit_price' => $unitPrice, 'is_active' => true,
        ]);
    }

    private function createOrder(Customer $customer, string $orderNumber, string $status, string $orderedAt, float $total): Order
    {
        return Order::query()->create([
            'order_number' => $orderNumber,
            'customer_id' => $customer->id,
            'status' => $status,
            'currency' => 'USD',
            'subtotal' => $total,
            'tax_total' => 0,
            'total' => $total,
            'ordered_at' => $orderedAt,
        ]);
    }
}
