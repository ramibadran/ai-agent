# Commerce Operations MCP

A learning-focused Laravel application that will become a read-only MCP server for fictional ecommerce reporting data.

```text
ai-agent-be                 Laravel RAG chat backend
ai-agent-fe                 React chat frontend
commerce-operations-mcp    Laravel commerce data and future MCP server
```

The project uses a dedicated MySQL database named `commerce_operations_mcp`. It does not use Qdrant, Redis, queues, or OpenAI.

## Current stage

The project has completed its first read-only MCP-server implementation: commerce reporting services are tested, a normal verification API is available, and five MCP tools expose the same trusted reporting logic.

AI-agent integration is deliberately still pending. The MCP server remains local and read-only.

## Documentation

Read the [documentation index](docs/README.md) before extending the project.

- [Local MySQL and Laravel setup](docs/LOCAL_SETUP.md)
- [Atlas Commerce domain model](docs/COMMERCE_DOMAIN.md)
- [MCP architecture and security boundary](docs/MCP_ARCHITECTURE.md)
- [Proposed read-only MCP tool contracts](docs/MCP_TOOL_CONTRACTS.md)
- [Reporting verification API](docs/API.md)
- [Staged delivery plan](docs/DELIVERY_PLAN.md)

## Baseline commands

```bash
php artisan config:clear
php artisan migrate
php artisan test
php artisan serve --port=8001
```

Configure local MySQL credentials in `.env` before migrations. See the [local setup guide](docs/LOCAL_SETUP.md) for database creation instructions.

## First future MCP tools

```text
get_sales_summary
compare_sales_periods
get_top_products
get_product_stock
find_low_stock_products
```

All first-release MCP tools will be read-only.

## Local MCP endpoint

Start Laravel on port `8001`, then configure an MCP-compatible client with this HTTP endpoint:

```text
http://127.0.0.1:8001/mcp/atlas-commerce
```

The server uses the official `laravel/mcp` package. It exposes tool discovery and these read-only tools:

```text
get_sales_summary
compare_sales_periods
get_top_products
get_product_stock
find_low_stock_products
```

Use the inspector to discover and call tools during development:

```bash
php artisan mcp:inspector mcp/atlas-commerce
```

---

## Laravel framework reference

<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
