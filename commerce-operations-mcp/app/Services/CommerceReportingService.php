<?php

namespace App\Services;

use App\Models\DailyProductSale;
use App\Models\InventorySnapshot;
use App\Models\Order;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

class CommerceReportingService
{
    /** @return array{currency: string, revenue: float, orders: int, average_order_value: float, refunded_revenue: float} */
    public function salesSummary(CarbonInterface $startDate, CarbonInterface $endDate): array
    {
        $orders = Order::query()
            ->whereBetween('ordered_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()]);
        $completed = (clone $orders)->where('status', Order::STATUS_COMPLETED);
        $orderCount = (clone $completed)->count();
        $revenue = (float) (clone $completed)->sum('total');

        return [
            'currency' => 'USD',
            'revenue' => round($revenue, 2),
            'orders' => $orderCount,
            'average_order_value' => $orderCount === 0 ? 0.0 : round($revenue / $orderCount, 2),
            'refunded_revenue' => round((float) (clone $orders)->where('status', Order::STATUS_REFUNDED)->sum('total'), 2),
        ];
    }

    /** @return array{current: array{currency: string, revenue: float, orders: int, average_order_value: float, refunded_revenue: float}, previous: array{currency: string, revenue: float, orders: int, average_order_value: float, refunded_revenue: float}, revenue_change_percent: float|null, order_change_percent: float|null} */
    public function compareSalesPeriods(CarbonInterface $currentStartDate, CarbonInterface $currentEndDate, CarbonInterface $previousStartDate, CarbonInterface $previousEndDate): array
    {
        $current = $this->salesSummary($currentStartDate, $currentEndDate);
        $previous = $this->salesSummary($previousStartDate, $previousEndDate);

        return [
            'current' => $current,
            'previous' => $previous,
            'revenue_change_percent' => $this->percentageChange($current['revenue'], $previous['revenue']),
            'order_change_percent' => $this->percentageChange($current['orders'], $previous['orders']),
        ];
    }

    /** @return list<array{sku: string, name: string, category: string, units_sold: int, revenue: float}> */
    public function topProducts(CarbonInterface $startDate, CarbonInterface $endDate, string $metric, int $limit): array
    {
        $metricColumn = $metric === 'units_sold' ? 'total_units_sold' : 'total_revenue';

        return DailyProductSale::query()
            ->selectRaw('product_id, SUM(units_sold) as total_units_sold, SUM(gross_revenue) as total_revenue')
            ->whereBetween('sale_date', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
            ->groupBy('product_id')
            ->orderByDesc($metricColumn)
            ->limit($limit)
            ->with('product:id,sku,name,category')
            ->get()
            ->map(fn (DailyProductSale $sale): array => [
                'sku' => $sale->product->sku,
                'name' => $sale->product->name,
                'category' => $sale->product->category,
                'units_sold' => (int) $sale->total_units_sold,
                'revenue' => round((float) $sale->total_revenue, 2),
            ])
            ->all();
    }

    /** @return array{sku: string, name: string, quantity_on_hand: int, reorder_point: int, captured_at: string}|null */
    public function productStock(string $sku): ?array
    {
        $snapshot = InventorySnapshot::query()
            ->whereHas('product', fn (Builder $query): Builder => $query->where('sku', $sku))
            ->with('product:id,sku,name')
            ->latest('captured_at')
            ->first();

        if ($snapshot === null) {
            return null;
        }

        return $this->stockResult($snapshot);
    }

    /** @return list<array{sku: string, name: string, quantity_on_hand: int, reorder_point: int, captured_at: string}> */
    public function lowStockProducts(int $threshold, int $limit): array
    {
        $latestSnapshots = InventorySnapshot::query()
            ->selectRaw('product_id, MAX(captured_at) as latest_captured_at')
            ->groupBy('product_id');

        return InventorySnapshot::query()
            ->joinSub($latestSnapshots, 'latest_snapshots', fn ($join) => $join
                ->on('inventory_snapshots.product_id', '=', 'latest_snapshots.product_id')
                ->on('inventory_snapshots.captured_at', '=', 'latest_snapshots.latest_captured_at'))
            ->where('quantity_on_hand', '<=', $threshold)
            ->with('product:id,sku,name')
            ->orderBy('quantity_on_hand')
            ->limit($limit)
            ->get()
            ->map(fn (InventorySnapshot $snapshot): array => $this->stockResult($snapshot))
            ->all();
    }

    private function percentageChange(float|int $current, float|int $previous): ?float
    {
        if ($previous == 0.0) {
            return null;
        }

        return round((($current - $previous) / $previous) * 100, 2);
    }

    /** @return array{sku: string, name: string, quantity_on_hand: int, reorder_point: int, captured_at: string} */
    private function stockResult(InventorySnapshot $snapshot): array
    {
        return [
            'sku' => $snapshot->product->sku,
            'name' => $snapshot->product->name,
            'quantity_on_hand' => $snapshot->quantity_on_hand,
            'reorder_point' => $snapshot->reorder_point,
            'captured_at' => $snapshot->captured_at->toIso8601String(),
        ];
    }
}
