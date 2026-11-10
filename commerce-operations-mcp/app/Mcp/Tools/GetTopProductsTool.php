<?php

namespace App\Mcp\Tools;

use App\Services\CommerceReportingService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('get_top_products')]
#[Description('Rank Atlas Commerce products by revenue or units sold for an explicit date range.')]
#[IsReadOnly]
class GetTopProductsTool extends CommerceTool
{
    public function handle(Request $request, CommerceReportingService $reporting): ResponseFactory
    {
        [$startDate, $endDate] = $this->dateRange($request);
        $validated = $request->validate([
            'metric' => ['sometimes', 'in:revenue,units_sold'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:25'],
        ]);

        return Response::structured([
            'products' => $reporting->topProducts(
                $startDate,
                $endDate,
                $validated['metric'] ?? 'revenue',
                $validated['limit'] ?? 5,
            ),
        ]);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'start_date' => $schema->string()->format('date')->required(),
            'end_date' => $schema->string()->format('date')->required(),
            'metric' => $schema->string()->enum(['revenue', 'units_sold'])->default('revenue'),
            'limit' => $schema->integer()->min(1)->max(25)->default(5),
        ];
    }
}
