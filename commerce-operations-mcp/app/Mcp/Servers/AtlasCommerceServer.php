<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\CompareSalesPeriodsTool;
use App\Mcp\Tools\FindLowStockProductsTool;
use App\Mcp\Tools\GetProductStockTool;
use App\Mcp\Tools\GetSalesSummaryTool;
use App\Mcp\Tools\GetTopProductsTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Atlas Commerce Operations')]
#[Version('0.1.0')]
#[Instructions('Use these read-only tools for factual Atlas Commerce sales and inventory questions. Dates must use YYYY-MM-DD. Never infer missing dates or claim that a tool result contains information it does not provide.')]
class AtlasCommerceServer extends Server
{
    /** @var array<int, class-string> */
    protected array $tools = [
        GetSalesSummaryTool::class,
        CompareSalesPeriodsTool::class,
        GetTopProductsTool::class,
        GetProductStockTool::class,
        FindLowStockProductsTool::class,
    ];
}
