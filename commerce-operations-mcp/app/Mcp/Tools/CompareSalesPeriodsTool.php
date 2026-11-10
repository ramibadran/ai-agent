<?php

namespace App\Mcp\Tools;

use App\Services\CommerceReportingService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('compare_sales_periods')]
#[Description('Compare completed-order sales across two explicit date ranges. Supply all four dates; the tool does not guess relative periods.')]
#[IsReadOnly]
class CompareSalesPeriodsTool extends CommerceTool
{
    public function handle(Request $request, CommerceReportingService $reporting): ResponseFactory
    {
        $validated = $request->validate([
            'current_start_date' => ['required', 'date_format:Y-m-d'],
            'current_end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:current_start_date'],
            'previous_start_date' => ['required', 'date_format:Y-m-d'],
            'previous_end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:previous_start_date'],
        ]);

        return Response::structured($reporting->compareSalesPeriods(
            CarbonImmutable::parse($validated['current_start_date']),
            CarbonImmutable::parse($validated['current_end_date']),
            CarbonImmutable::parse($validated['previous_start_date']),
            CarbonImmutable::parse($validated['previous_end_date']),
        ));
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'current_start_date' => $schema->string()->format('date')->required(),
            'current_end_date' => $schema->string()->format('date')->required(),
            'previous_start_date' => $schema->string()->format('date')->required(),
            'previous_end_date' => $schema->string()->format('date')->required(),
        ];
    }
}
