<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\DailyProductSale;
use App\Models\InventorySnapshot;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class AtlasCommerceSeeder extends Seeder
{
    public function run(): void
    {
        $products = collect([
            ['sku' => 'ATL-AERO-X1-BLK', 'name' => 'AeroRun X1 Black', 'category' => 'Footwear', 'unit_price' => 120.00, 'quantity_on_hand' => 18, 'reorder_point' => 30, 'daily_units' => 12],
            ['sku' => 'ATL-PACK-PRO', 'name' => 'CityPack Pro', 'category' => 'Bags', 'unit_price' => 80.00, 'quantity_on_hand' => 11, 'reorder_point' => 25, 'daily_units' => 10],
            ['sku' => 'ATL-BOTTLE-TRAIL', 'name' => 'TrailBottle 750ml', 'category' => 'Accessories', 'unit_price' => 24.00, 'quantity_on_hand' => 96, 'reorder_point' => 35, 'daily_units' => 8],
            ['sku' => 'ATL-MAT-FLEX', 'name' => 'FlexMat Travel', 'category' => 'Fitness', 'unit_price' => 45.00, 'quantity_on_hand' => 54, 'reorder_point' => 20, 'daily_units' => 6],
            ['sku' => 'ATL-MUG-TRAVEL', 'name' => 'TravelMug Steel', 'category' => 'Accessories', 'unit_price' => 30.00, 'quantity_on_hand' => 72, 'reorder_point' => 25, 'daily_units' => 7],
            ['sku' => 'ATL-JACKET-SUMMIT', 'name' => 'SummitShell Jacket', 'category' => 'Outerwear', 'unit_price' => 180.00, 'quantity_on_hand' => 33, 'reorder_point' => 15, 'daily_units' => 4],
        ])->map(fn (array $attributes): Product => Product::query()->updateOrCreate(
            ['sku' => $attributes['sku']],
            collect($attributes)->only(['sku', 'name', 'category', 'unit_price'])->all() + ['is_active' => true],
        ));

        $customers = collect([
            ['name' => 'Maya Chen', 'email' => 'maya.chen@atlas-commerce.test', 'country' => 'JO'],
            ['name' => 'Leo Martin', 'email' => 'leo.martin@atlas-commerce.test', 'country' => 'AE'],
            ['name' => 'Nadia Ali', 'email' => 'nadia.ali@atlas-commerce.test', 'country' => 'SA'],
            ['name' => 'Samira Haddad', 'email' => 'samira.haddad@atlas-commerce.test', 'country' => 'GB'],
        ])->map(fn (array $attributes): Customer => Customer::query()->updateOrCreate(
            ['email' => $attributes['email']],
            $attributes + ['registered_at' => '2026-01-15 09:00:00'],
        ));

        $start = CarbonImmutable::parse('2026-09-01');
        $end = CarbonImmutable::parse('2026-10-31');

        for ($date = $start; $date->lte($end); $date = $date->addDay()) {
            $monthMultiplier = $date->month === 9 ? 1.15 : 0.88;

            foreach ($products->values() as $index => $product) {
                $units = max(1, (int) round($this->productDefinition($product->sku)['daily_units'] * $monthMultiplier) + (($date->day + $index) % 5) - 2);
                $isRefunded = $date->day === 14 && $index === 4;
                $isCancelled = $date->day === 22 && $index === 2;
                $status = $isRefunded ? Order::STATUS_REFUNDED : ($isCancelled ? Order::STATUS_CANCELLED : Order::STATUS_COMPLETED);
                $total = round($units * (float) $product->unit_price, 2);
                $order = Order::query()->updateOrCreate(
                    ['order_number' => sprintf('ATL-%s-%02d', $date->format('Ymd'), $index + 1)],
                    [
                        'customer_id' => $customers[$index % $customers->count()]->id,
                        'status' => $status,
                        'currency' => 'USD',
                        'subtotal' => $total,
                        'tax_total' => 0,
                        'total' => $total,
                        'ordered_at' => $date->setTime(10 + $index, 0),
                    ],
                );
                OrderItem::query()->updateOrCreate(
                    ['order_id' => $order->id, 'product_id' => $product->id],
                    ['quantity' => $units, 'unit_price' => $product->unit_price, 'line_total' => $total],
                );

                if ($status === Order::STATUS_COMPLETED) {
                    DailyProductSale::query()->updateOrCreate(
                        ['product_id' => $product->id, 'sale_date' => $date->toDateString()],
                        ['units_sold' => $units, 'order_count' => 1, 'gross_revenue' => $total],
                    );
                }
            }
        }

        foreach ($products as $product) {
            $definition = $this->productDefinition($product->sku);
            InventorySnapshot::query()->updateOrCreate(
                ['product_id' => $product->id, 'captured_at' => '2026-10-30 09:00:00'],
                ['quantity_on_hand' => $definition['quantity_on_hand'] + 20, 'reorder_point' => $definition['reorder_point']],
            );
            InventorySnapshot::query()->updateOrCreate(
                ['product_id' => $product->id, 'captured_at' => '2026-10-31 09:00:00'],
                ['quantity_on_hand' => $definition['quantity_on_hand'], 'reorder_point' => $definition['reorder_point']],
            );
        }
    }

    /** @return array{daily_units: int, quantity_on_hand: int, reorder_point: int} */
    private function productDefinition(string $sku): array
    {
        return collect([
            'ATL-AERO-X1-BLK' => ['daily_units' => 12, 'quantity_on_hand' => 18, 'reorder_point' => 30],
            'ATL-PACK-PRO' => ['daily_units' => 10, 'quantity_on_hand' => 11, 'reorder_point' => 25],
            'ATL-BOTTLE-TRAIL' => ['daily_units' => 8, 'quantity_on_hand' => 96, 'reorder_point' => 35],
            'ATL-MAT-FLEX' => ['daily_units' => 6, 'quantity_on_hand' => 54, 'reorder_point' => 20],
            'ATL-MUG-TRAVEL' => ['daily_units' => 7, 'quantity_on_hand' => 72, 'reorder_point' => 25],
            'ATL-JACKET-SUMMIT' => ['daily_units' => 4, 'quantity_on_hand' => 33, 'reorder_point' => 15],
        ])->get($sku);
    }
}
