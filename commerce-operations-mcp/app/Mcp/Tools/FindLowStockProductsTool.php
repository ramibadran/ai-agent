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

#[Name('find_low_stock_products')]
#[Description('List active products whose latest inventory quantity is at or below the requested threshold.')]
#[IsReadOnly]
class FindLowStockProductsTool extends CommerceTool
{
    public function handle(Request $request, CommerceReportingService $reporting): Response|ResponseFactory
    {
        $validated = $request->validate([
            'threshold' => ['sometimes', 'integer', 'min:0', 'max:100000'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        return Response::structured([
            'products' => $reporting->lowStockProducts(
                $validated['threshold'] ?? 20,
                $validated['limit'] ?? 20,
            ),
        ]);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'threshold' => $schema->integer()->min(0)->max(100000)->default(20),
            'limit' => $schema->integer()->min(1)->max(100)->default(20),
        ];
    }
}
