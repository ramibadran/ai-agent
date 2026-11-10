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

#[Name('get_product_stock')]
#[Description('Get the latest inventory snapshot and reorder point for one Atlas Commerce SKU.')]
#[IsReadOnly]
class GetProductStockTool extends CommerceTool
{
    public function handle(Request $request, CommerceReportingService $reporting): Response|ResponseFactory
    {
        $validated = $request->validate([
            'sku' => ['required', 'string', 'max:100'],
        ]);
        $stock = $reporting->productStock($validated['sku']);

        return $stock === null
            ? Response::error('Product stock was not found for the supplied SKU.')
            : Response::structured($stock);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'sku' => $schema->string()->min(1)->max(100)->required()->description('The exact product SKU, for example ATL-AERO-X1-BLK.'),
        ];
    }
}
