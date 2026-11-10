<?php

namespace Tests\Feature;

use Tests\TestCase;

class CommerceMcpHttpAuthenticationTest extends TestCase
{
    private const string MCP_PATH = '/mcp/atlas-commerce';

    protected function setUp(): void
    {
        parent::setUp();

        config(['commerce.mcp_token' => 'test-commerce-mcp-token']);
    }

    public function test_mcp_endpoint_requires_a_bearer_token(): void
    {
        $this->postJson(self::MCP_PATH, $this->toolsListPayload(), $this->mcpHeaders())
            ->assertUnauthorized()
            ->assertHeader('WWW-Authenticate', 'Bearer realm="mcp", error="invalid_token"')
            ->assertJsonPath('error', 'Unauthorized.');
    }

    public function test_mcp_endpoint_allows_an_authenticated_client_to_discover_tools(): void
    {
        $this->withToken('test-commerce-mcp-token')
            ->postJson(self::MCP_PATH, $this->toolsListPayload(), $this->mcpHeaders())
            ->assertOk()
            ->assertJsonPath('result.tools.0.name', 'get_sales_summary');
    }

    /** @return array<string, mixed> */
    private function toolsListPayload(): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/list',
            'params' => [
                '_meta' => [
                    'io.modelcontextprotocol/protocolVersion' => '2026-07-28',
                    'io.modelcontextprotocol/clientCapabilities' => [],
                ],
            ],
        ];
    }

    /** @return array<string, string> */
    private function mcpHeaders(): array
    {
        return [
            'MCP-Protocol-Version' => '2026-07-28',
            'Mcp-Method' => 'tools/list',
        ];
    }
}
