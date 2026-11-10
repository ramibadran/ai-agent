<?php

namespace App\Services\V1;

use InvalidArgumentException;
use Laravel\Mcp\Client;
use Laravel\Mcp\Client\Primitives\Tool;

class CommerceMcpClient
{
    /** @var list<string> */
    private const ALLOWED_TOOLS = [
        'get_sales_summary',
        'compare_sales_periods',
        'get_top_products',
        'get_product_stock',
        'find_low_stock_products',
    ];

    /**
     * @return list<array{name: string, description: string, parameters: array<string, mixed>}>
     */
    public function toolDefinitions(): array
    {
        return $this->client()->tools()
            ->map(fn (Tool $tool): array => [
                'name' => $tool->name,
                'description' => $tool->description ?? $tool->name,
                'parameters' => $tool->inputSchema,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array{is_error: bool, data: array<string, mixed>|null, text: string}
     */
    public function call(string $toolName, array $arguments): array
    {
        if (! in_array($toolName, self::ALLOWED_TOOLS, true)) {
            throw new InvalidArgumentException("MCP tool [{$toolName}] is not allowed.");
        }

        $result = $this->client()->callTool($toolName, $arguments);

        return [
            'is_error' => $result->isError,
            'data' => $result->structuredContent,
            'text' => $result->text(),
        ];
    }

    /** @return list<string> */
    public function allowedToolNames(): array
    {
        return self::ALLOWED_TOOLS;
    }

    private function client(): Client
    {
        $url = (string) config('services.commerce_mcp.url');
        $token = (string) config('services.commerce_mcp.token');

        if ($url === '' || $token === '') {
            throw new InvalidArgumentException('Commerce MCP client is not configured.');
        }

        return Client::web($url)
            ->withToken($token)
            ->withTimeout((float) config('services.commerce_mcp.timeout_seconds'))
            ->onlyTools(self::ALLOWED_TOOLS);
    }
}
