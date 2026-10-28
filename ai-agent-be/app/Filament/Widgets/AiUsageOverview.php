<?php

namespace App\Filament\Widgets;

use App\Models\Conversation;
use App\Models\ConversationMessage;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AiUsageOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $assistantMessages = ConversationMessage::query()->where('role', 'assistant');
        $answers = (clone $assistantMessages)->where('status', 'completed');
        $totalTokens = (int) (clone $answers)->sum('total_tokens');
        $totalCost = (float) (clone $answers)->sum('cost_usd');
        $averageLatency = (int) round((float) (clone $answers)->whereNotNull('latency_ms')->avg('latency_ms'));

        return [
            Stat::make('Conversations', Conversation::query()->count())
                ->description('Saved user chat sessions'),
            Stat::make('AI answers', $answers->count())
                ->description((clone $assistantMessages)->where('status', 'failed')->count().' failed answers'),
            Stat::make('Tokens used', number_format($totalTokens))
                ->description('Prompt and completion tokens'),
            Stat::make('Estimated cost', '$'.number_format($totalCost, 4))
                ->description('Based on configured model pricing'),
            Stat::make('Average latency', $averageLatency.' ms')
                ->description('Completed AI answer generation'),
        ];
    }
}
