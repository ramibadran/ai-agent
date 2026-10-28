<?php

namespace App\Filament\Widgets;

use App\Models\ConversationMessage;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class AiUsageTrend extends ChartWidget
{
    protected ?string $heading = 'AI usage: last 14 days';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $start = now()->subDays(13)->startOfDay();
        $usageByDate = ConversationMessage::query()
            ->where('role', 'assistant')
            ->where('status', 'completed')
            ->where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) as usage_date, COUNT(*) as answer_count, COALESCE(SUM(cost_usd), 0) as cost')
            ->groupBy('usage_date')
            ->get()
            ->keyBy('usage_date');

        $days = collect(range(0, 13))->map(fn (int $offset): Carbon => $start->copy()->addDays($offset));

        return [
            'datasets' => [
                [
                    'label' => 'AI answers',
                    'data' => $days->map(fn (Carbon $day): int => (int) ($usageByDate[$day->toDateString()]->answer_count ?? 0))->all(),
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => '#f59e0b',
                    'yAxisID' => 'answers',
                ],
                [
                    'label' => 'Estimated cost (USD)',
                    'data' => $days->map(fn (Carbon $day): float => round((float) ($usageByDate[$day->toDateString()]->cost ?? 0), 6))->all(),
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => '#3b82f6',
                    'yAxisID' => 'cost',
                ],
            ],
            'labels' => $days->map(fn (Carbon $day): string => $day->format('M j'))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
