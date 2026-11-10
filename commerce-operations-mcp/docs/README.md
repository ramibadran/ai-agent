# Commerce Operations MCP — Design Reference

This Laravel application is a read-only Model Context Protocol (MCP) server for a fictional ecommerce business, **Atlas Commerce**.

It is intentionally separate from `ai-agent-be` and `ai-agent-fe`:

```text
ai-agent-be                 Laravel RAG chat application
ai-agent-fe                 React chat application
commerce-operations-mcp    Laravel commerce data + future MCP server
```

The project is being built in deliberate stages:

1. Laravel and MySQL foundation.
2. Fictional ecommerce data model and seeded reports.
3. Normal Laravel domain services and tests.
4. Read-only MCP tools.
5. Connection from the AI Agent to the MCP server.
6. Authentication, audit logging, and explicit approval for any future write tools.

The initial MCP server exposes five read-only commerce reporting tools. Connection from `ai-agent-be` is the next stage.

Read the documents in this order:

- [Local setup](LOCAL_SETUP.md)
- [Commerce domain model](COMMERCE_DOMAIN.md)
- [MCP architecture and boundaries](MCP_ARCHITECTURE.md)
- [Proposed tool contracts](MCP_TOOL_CONTRACTS.md)
- [Reporting verification API](API.md)
- [MCP testing guide](MCP_TESTING.md)
- [Delivery plan](DELIVERY_PLAN.md)
