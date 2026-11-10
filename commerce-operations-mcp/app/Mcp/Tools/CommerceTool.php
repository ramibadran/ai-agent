<?php

namespace App\Mcp\Tools;

use Carbon\CarbonImmutable;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Tool;

abstract class CommerceTool extends Tool
{
    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    protected function dateRange(Request $request): array
    {
        $validated = $request->validate([
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ]);

        return [
            CarbonImmutable::parse($validated['start_date']),
            CarbonImmutable::parse($validated['end_date']),
        ];
    }
}
