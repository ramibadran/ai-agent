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

#[Name('get_sales_summary')]
#[Description('Get completed-order revenue, order count, average order value, and refunded revenue for an explicit date range.')]
#[IsReadOnly]
class GetSalesSummaryTool extends CommerceTool
{
    public function handle(Request $request, CommerceReportingService $reporting): ResponseFactory
    {
        [$startDate, $endDate] = $this->dateRange($request);

        return Response::structured($reporting->salesSummary($startDate, $endDate));
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'start_date' => $schema->string()->format('date')->required()->description('Inclusive start date in YYYY-MM-DD format.'),
            'end_date' => $schema->string()->format('date')->required()->description('Inclusive end date in YYYY-MM-DD format.'),
        ];
    }
}
