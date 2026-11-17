# VIES VAT Number Validation Skill

Validate EU VAT numbers in real-time via the official VIES (VAT Information Exchange System) database, served through eu-vat.info with multi-layer caching.

## What You Can Do

- Validate any EU VAT number against the official EU VIES database
- Retrieve the registered company name and address for valid numbers
- Batch-validate up to 10 VAT numbers in a single request
- Check validity for all 27 EU member states (note: Greece uses country code `EL`)

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

### MCP Tool: `validate_vat_number`

**Input:**
| Parameter      | Type   | Required | Description |
|----------------|--------|----------|-------------|
| `country_code` | string | yes      | Two-letter ISO country code. Greece uses `"EL"`, not `"GR"`. |
| `vat_number`   | string | yes      | VAT number, with or without the country prefix (`"123456789"` or `"DE123456789"`) |

```json
{
  "jsonrpc": "2.0",
  "id": 1,
  "method": "tools/call",
  "params": {
    "name": "validate_vat_number",
    "arguments": {
      "country_code": "DE",
      "vat_number": "123456789"
    }
  }
}
```

**Response (valid):**
```json
{
  "valid": true,
  "country_code": "DE",
  "vat_number": "123456789",
  "name": "Example GmbH",
  "address": "Musterstraße 1, 10115 Berlin",
  "request_date": "2026-09-29",
  "source": "vies_api"
}
```

**Response (invalid):**
```json
{
  "valid": false,
  "country_code": "DE",
  "vat_number": "000000000",
  "source": "vies_api"
}
```

`name` and `address` are omitted when the member state does not publish them. When VIES is unavailable the tool returns an error result (`isError: true`) with a plain-language message instead of reporting the number as invalid. Each IP address can run 20 checks per minute.

## REST API

### Single Validation

```
POST https://eu-vat.info/api/vat/validation/validate
Content-Type: application/json

{
  "country_code": "DE",
  "vat_number": "123456789"
}
```

### Batch Validation (up to 10)

```
POST https://eu-vat.info/api/vat/validation/batch
Content-Type: application/json

{
  "numbers": [
    { "country_code": "DE", "vat_number": "123456789" },
    { "country_code": "LT", "vat_number": "100001919314" }
  ]
}
```

### Health Check

```
GET https://eu-vat.info/api/vat/validation/health
```

## Country Code Reference

| Country | Code | Country | Code |
|---------|------|---------|------|
| Austria | AT | Latvia | LV |
| Belgium | BE | Lithuania | LT |
| Bulgaria | BG | Luxembourg | LU |
| Croatia | HR | Malta | MT |
| Cyprus | CY | Netherlands | NL |
| Czech Republic | CZ | Poland | PL |
| Denmark | DK | Portugal | PT |
| Estonia | EE | Romania | RO |
| Finland | FI | Slovakia | SK |
| France | FR | Slovenia | SI |
| Germany | DE | Spain | ES |
| **Greece** | **EL** | Sweden | SE |
| Hungary | HU | | |
| Ireland | IE | | |
| Italy | IT | | |

> **Note:** Greece uses `EL` (not `GR`) per the VIES standard.

## Caching Behaviour

Results are cached via a multi-layer strategy (cache → database → VIES API) to reduce load on the EU VIES service. A confirmed answer from VIES is reused for up to 24 hours; failed lookups are never cached.

## Human-Readable Reference

- Interactive validator: `https://eu-vat.info/vat-number-validator/{slug}`
- MCP server guide: `https://eu-vat.info/mcp-server`
