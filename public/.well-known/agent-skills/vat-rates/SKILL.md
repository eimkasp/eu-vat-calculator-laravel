# EU VAT Rates Skill

Query live VAT rates for the 27 EU member states and five other European countries (Iceland, Norway, Switzerland, Turkey and the United Kingdom) via the eu-vat.info API or MCP server.

## What You Can Do

- Get current VAT rates (standard, reduced, super-reduced, parking) for any EU country
- Calculate VAT amounts (add VAT to net price, or extract VAT from gross price)
- Compare VAT rates across multiple EU countries
- List recorded and upcoming VAT rate changes
- Look up a country by name, ISO code (e.g. `DE`), or slug (e.g. `germany`)

## Authentication

No authentication required. All endpoints are publicly accessible.

## MCP Server (Recommended for AI Agents)

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

### Available MCP Tools

#### `get_all_vat_rates`
Returns standard, reduced, super-reduced, and parking rates for all 27 EU member states.

```json
{ "jsonrpc": "2.0", "id": 1, "method": "tools/call", "params": { "name": "get_all_vat_rates", "arguments": {} } }
```

#### `get_country_vat_rate`
Get VAT rates for a specific country.

**Input:**
| Parameter | Type   | Required | Description |
|-----------|--------|----------|-------------|
| `country` | string | yes      | Country name (`"Germany"`), ISO code (`"DE"`), or slug (`"germany"`) |

```json
{ "jsonrpc": "2.0", "id": 1, "method": "tools/call", "params": { "name": "get_country_vat_rate", "arguments": { "country": "DE" } } }
```

#### `calculate_vat`
Calculate VAT for a given amount and country.

**Input:**
| Parameter   | Type   | Required | Description |
|-------------|--------|----------|-------------|
| `amount`    | number | yes      | Monetary amount |
| `country`   | string | yes      | Country name, ISO code, or slug |
| `mode`      | string | no       | `"add"` (net → gross) or `"remove"` (gross → net). Default: `"add"` |
| `rate_type` | string | no       | `"standard"`, `"reduced"`, `"super_reduced"`, `"parking"`. Default: `"standard"` |

```json
{ "jsonrpc": "2.0", "id": 1, "method": "tools/call", "params": { "name": "calculate_vat", "arguments": { "amount": 100, "country": "DE", "mode": "add", "rate_type": "standard" } } }
```

#### `compare_vat_rates`
Compare standard and reduced rates across multiple EU countries.

**Input:**
| Parameter   | Type  | Required | Description |
|-------------|-------|----------|-------------|
| `countries` | array | yes      | Array of country names, ISO codes, or slugs |

```json
{ "jsonrpc": "2.0", "id": 1, "method": "tools/call", "params": { "name": "compare_vat_rates", "arguments": { "countries": ["DE", "FR", "LT"] } } }
```

#### `get_vat_rate_changes`
Recorded and upcoming VAT rate changes with old and new rates and the date each takes effect.

**Input:**
| Parameter       | Type    | Required | Description |
|-----------------|---------|----------|-------------|
| `country`       | string  | no       | Limit to one country (name, ISO code or slug) |
| `upcoming_only` | boolean | no       | Only changes that take effect today or later. Default: `false` |
| `limit`         | integer | no       | 1 to 50. Default: `20` |

```json
{ "jsonrpc": "2.0", "id": 1, "method": "tools/call", "params": { "name": "get_vat_rate_changes", "arguments": { "upcoming_only": true } } }
```

Every tool returns its data twice: as JSON text for older clients and as `structuredContent` for clients on protocol 2025-06-18.

## REST API

The v1 API is described by an OpenAPI 3.1 document at `https://eu-vat.info/api/v1/openapi.json`.

### Get All Countries

```
GET https://eu-vat.info/api/v1/countries
```

### Get a Single Country

```
GET https://eu-vat.info/api/v1/countries/{slug-or-iso}
```

Example: `GET https://eu-vat.info/api/v1/countries/germany`

### Calculate VAT

```
GET https://eu-vat.info/api/v1/calculate?amount=100&country=DE&rate_type=standard&mode=add
```

`mode=remove` extracts VAT from a gross amount.

### LLM-Optimised Rates List

```
GET https://eu-vat.info/api/llm/vat-rates
```

Returns a compact JSON array of all countries with their rates — ideal for context injection.

## Response Format

MCP `get_country_vat_rate` result:

```json
{
  "country": "Austria",
  "iso_code": "AT",
  "slug": "austria",
  "eu_member": true,
  "currency": "EUR",
  "rates": {
    "standard": 20,
    "reduced": 10,
    "reduced_rates": [10, 13],
    "super_reduced": null,
    "parking": 13
  },
  "last_updated": "2026-09-29T00:00:00+00:00"
}
```

`reduced` is the lowest reduced rate; `reduced_rates` lists every one.

## Human-Readable Reference

- Country calculators: `https://eu-vat.info/vat-calculator/{slug}`
- VAT calculator: `https://eu-vat.info/vat-calculator`
- Rate history: `https://eu-vat.info/vat-rates/{slug}/history`
- Guide for AI agents: `https://eu-vat.info/llms.txt`
- Full rates table: `https://eu-vat.info/llms-full.txt`
- MCP server guide: `https://eu-vat.info/mcp-server`
