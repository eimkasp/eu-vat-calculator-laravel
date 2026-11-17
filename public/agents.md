# EU VAT Info — Agent Discovery

> Machine-readable guide for AI agents to discover and use EU VAT Info capabilities.

## Overview

EU VAT Info (`eu-vat.info`) provides free, daily-updated VAT data for all 27 EU member states.
No authentication required. All endpoints are publicly accessible.

## MCP Server (Recommended)

Connect via the Model Context Protocol for structured tool access:

```json
{
  "mcpServers": {
    "eu-vat-info": {
      "type": "http",
      "url": "https://eu-vat.info/api/mcp"
    }
  }
}
```

### Tools

| Tool | Description |
|------|-------------|
| `get_all_vat_rates` | Get current VAT rates for all 27 EU member states |
| `get_country_vat_rate` | Get VAT rates for a specific country (by name, ISO code, or slug) |
| `calculate_vat` | Calculate VAT for a given amount and country (add or remove) |
| `compare_vat_rates` | Compare VAT rates between two or more EU countries |
| `validate_vat_number` | Validate an EU VAT number against the official VIES database |

## REST API

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/countries` | All 27 EU countries with VAT rates (cached 600s) |
| GET | `/api/countries/{slug}` | Single country VAT data |
| GET | `/api/llm/vat-rates` | Compact JSON optimised for LLM context |
| GET | `/api/vat-changes` | Recent VAT rate changes across all countries |
| POST | `/api/vat/validation/validate` | Validate a single VAT number via VIES |
| POST | `/api/vat/validation/batch` | Batch validate up to 10 VAT numbers |
| GET | `/api/vat/validation/health` | VIES validation service health check |

## Discovery Endpoints

| Path | Format | Description |
|------|--------|-------------|
| `/.well-known/agent-skills` | JSON | Agent skills index with all capabilities |
| `/.well-known/agent-skills/vat-rates/SKILL.md` | Markdown | Detailed VAT rates skill documentation |
| `/.well-known/agent-skills/vies-validation/SKILL.md` | Markdown | Detailed VIES validation skill documentation |
| `/.well-known/api-catalog` | JSON | RFC 9727 API catalog |
| `/.well-known/mcp/server-card.json` | JSON | MCP server card (SEP-1649) |
| `/.well-known/oauth-protected-resource` | JSON | RFC 9728 resource metadata |
| `/llms.txt` | Text | LLM-optimised site description |
| `/llms-full.txt` | Text | Full VAT rates table in Markdown |
| `/agents.md` | Markdown | This file |

## Authentication

None. All endpoints are free and open. No API keys, OAuth tokens, or rate limits.

## Data Freshness

VAT rates are synchronised daily from official EU sources. Cached responses are typically 10 minutes old.

## Source

- Website: https://eu-vat.info
- Repository: https://github.com/eimkasp/eu-vat.info
