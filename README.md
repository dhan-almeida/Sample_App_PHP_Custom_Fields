# QuickBooks Online Custom Fields - PHP Sample Application

A production-ready PHP application demonstrating best practices for QuickBooks Online Custom Fields API.

[![Tests](https://img.shields.io/badge/tests-21%2F21%20passing-brightgreen)]()
[![PHP](https://img.shields.io/badge/php-%3E%3D8.1-blue)]()
[![License](https://img.shields.io/badge/license-Educational-blue)]()

---

## What This App Does

This app shows you how to **create and use custom fields** in QuickBooks Online across three entity types:

- **Invoices** - Track fuel costs, priority levels, project codes
- **Customers** - Store customer tiers, account managers, regions
- **Items** - Add supplier codes, warranty periods, categories

**Two-API Architecture**:
1. **GraphQL API** - Create and manage custom field definitions
2. **REST API** - Apply custom fields when creating/updating entities

---

## Features

- ✅ Support for STRING, NUMBER, and DROPDOWN custom fields
- ✅ Automatic type validation and correction
- ✅ Protection against data loss and field overwrites
- ✅ Security hardening (prevents Id/SyncToken tampering)
- ✅ Interactive web UI for testing
- ✅ Complete documentation

---

## Quick Start

### 1. Install Dependencies
```bash
composer install
```

### 2. Configure Environment
```bash
# Copy template
cp env.example .env

# Edit .env and add your QuickBooks credentials:
# - CLIENT_ID (from QuickBooks Developer Portal)
# - CLIENT_SECRET (from QuickBooks Developer Portal)
# - REDIRECT_URI (http://localhost:8000/api/auth/callback)
```

**Get credentials**: https://developer.intuit.com/app/developer/myapps

**Need help?** See [`SETUP_GUIDE.md`](./SETUP_GUIDE.md) for detailed instructions.

### 3. Start the Server
```bash
# Option A: Standard port (no sudo)
php -S localhost:8000 -t public

# Option B: Port 80 (requires sudo)
sudo php -S localhost:80 -t public
```

### 4. Open in Browser
```
http://localhost:8000
```

### 5. Authenticate
Click "Sign in with QuickBooks" and authorize the app.

**OAuth not working?** You may need ngrok - see [`NGROK_SETUP.md`](./NGROK_SETUP.md)

---

## How to Use

### Web UI
Open `http://localhost:8000` in your browser for an interactive interface to:
- Create custom field definitions
- Validate custom fields
- Create invoices, customers, and items with custom fields

### API Endpoints

#### Authentication
```
GET  /api/auth/login          # Start OAuth flow
GET  /api/auth/callback       # OAuth callback
```

#### Custom Field Definitions (GraphQL)
```
GET  /api/quickbook/custom-fields           # List all definitions
POST /api/quickbook/custom-fields           # Create definition
PUT  /api/quickbook/custom-fields/:id       # Update definition
POST /api/quickbook/custom-fields/validate  # Validate fields
```

#### Invoices (REST)
```
POST /api/quickbook/invoices                # Create invoice
POST /api/quickbook/invoices/cost-of-fuel   # Create with fuel cost
```

#### Customers (REST)
```
GET  /api/quickbook/customers/:id           # Get customer
POST /api/quickbook/customers               # Create customer
PUT  /api/quickbook/customers/:id           # Update customer
```

#### Items (REST)
```
GET  /api/quickbook/items/:id               # Get item
POST /api/quickbook/items                   # Create item
PUT  /api/quickbook/items/:id               # Update item
```

**Full API examples**: See [`QUICK_START.md`](./QUICK_START.md)

---

## Example: Create Invoice with Custom Field

### Step 1: Create Custom Field Definition
```bash
POST /api/quickbook/custom-fields
```
```json
{
  "label": "Cost of Fuel",
  "dataType": "NUMBER",
  "active": true,
  "associations": [{
    "associatedEntity": "Invoice",
    "active": true,
    "allowedOperations": ["Create", "Update"]
  }]
}
```

**Response**: Note the `legacyIDV2` (e.g., "1")

### Step 2: Create Invoice with Custom Field
```bash
POST /api/quickbook/invoices
```
```json
{
  "customerId": "1",
  "lineItems": [
    {"itemId": "1", "amount": 100.00}
  ],
  "customFields": [
    {"definitionId": "1", "value": 50.00, "type": "NUMBER"}
  ]
}
```

**Done!** The invoice now has a custom "Cost of Fuel" field with value 50.00.

---

## Architecture

### File Structure
```
sampleapp-customfields-php-full/
├── public/
│   └── index.php              # Main entry point & router
├── pages/
│   └── index.html             # Web UI
├── src/
│   ├── GraphQL/CustomFields/  # GraphQL queries
│   ├── Routes/                # API route handlers
│   └── Services/              # Business logic
├── .env                       # Your credentials (create from env.example)
└── composer.json              # Dependencies
```

### How It Works

1. **GraphQL API** (`https://qb.api.intuit.com/graphql`)
   - Creates custom field definitions
   - Returns `legacyIDV2` (used as `DefinitionId` in REST API)

2. **REST API** (`https://quickbooks.api.intuit.com`)
   - Creates entities (Invoice, Customer, Item)
   - Includes `CustomField` array with `DefinitionId` and value
   - Uses `NumberValue` for NUMBER fields, `StringValue` for STRING/DROPDOWN

3. **Validation Service**
   - Fetches definitions via GraphQL
   - Validates types and values
   - Auto-corrects type mismatches
   - Caches definitions for performance

---

## Security Features

This app includes protection against common vulnerabilities:

- ✅ **CustomField overwrite prevention** - Blocks silent data loss
- ✅ **Core field protection** - Prevents Line, CustomerRef, DisplayName tampering
- ✅ **Id/SyncToken security** - Blocks entity ID and sync token manipulation
- ✅ **Type validation** - Ensures data types match definitions
- ✅ **Input sanitization** - Validates and sanitizes all inputs

**Details**: See [`BUGFIXES.md`](./BUGFIXES.md) for all security fixes.

---

## Troubleshooting

### "Not authenticated" Error
**Solution**: Click "Sign in with QuickBooks" to authenticate first.

### "Invalid client" Error
**Cause**: Incorrect CLIENT_ID or CLIENT_SECRET  
**Solution**: Verify credentials in QuickBooks Developer Portal and `.env` file.

### "Redirect URI mismatch" Error
**Cause**: Redirect URI in `.env` doesn't match QuickBooks portal  
**Solution**: 
1. Check REDIRECT_URI in `.env`
2. Verify it matches exactly in QuickBooks portal (Keys & OAuth → Redirect URIs)
3. Restart PHP server after changing `.env`

### OAuth Not Working with localhost
**Cause**: QuickBooks requires publicly accessible URLs  
**Solution**: Use ngrok to create a public tunnel:
```bash
# Install ngrok (see NGROK_SETUP.md)
ngrok http 8000

# Update .env with ngrok URL
REDIRECT_URI=https://your-ngrok-url.ngrok-free.app/api/auth/callback

# Update QuickBooks portal with same URL
# Restart server
```

**Full guide**: [`NGROK_SETUP.md`](./NGROK_SETUP.md)

### "Custom field definition not found" Error
**Cause**: Using wrong `definitionId` or field doesn't exist  
**Solution**: 
1. Call `GET /api/quickbook/custom-fields` to list all definitions
2. Use the `legacyIDV2` value as your `definitionId`

### Variables Not Loading from .env
**Cause**: PHP server not restarted after editing `.env`  
**Solution**: Stop server (Ctrl+C) and restart it.

### More Help
- **Environment setup**: [`ENV_SETUP_QUICKREF.md`](./ENV_SETUP_QUICKREF.md)
- **Detailed setup**: [`SETUP_GUIDE.md`](./SETUP_GUIDE.md)
- **ngrok issues**: [`NGROK_SETUP.md`](./NGROK_SETUP.md)

---

## Testing

**Test Status**: ✅ 21/21 tests passing (100%)

Run validation test:
```bash
curl -X POST http://localhost:8000/api/quickbook/custom-fields/validate \
  -H "Content-Type: application/json" \
  -d '{
    "customFields": [
      {"definitionId": "1", "value": 50.00, "type": "NUMBER"}
    ]
  }'
```

**Full test results**: See [`TESTING.md`](./TESTING.md)

---

## Documentation

- [`QUICK_START.md`](./QUICK_START.md) - Quick reference with examples
- [`SETUP_GUIDE.md`](./SETUP_GUIDE.md) - Detailed setup instructions
- [`NGROK_SETUP.md`](./NGROK_SETUP.md) - ngrok for local OAuth
- [`ENV_SETUP_QUICKREF.md`](./ENV_SETUP_QUICKREF.md) - Environment setup cheat sheet
- [`TESTING.md`](./TESTING.md) - Test results and verification
- [`BUGFIXES.md`](./BUGFIXES.md) - Security fixes and improvements
- [`CHANGELOG.md`](./CHANGELOG.md) - Version history
- [`DOCUMENTATION.md`](./DOCUMENTATION.md) - Guide to all documentation

---

## Resources

### QuickBooks Documentation
- [Custom Fields Workflow](https://developer.intuit.com/app/developer/qbo/docs/workflows/create-custom-fields/get-started)
- [GraphQL API Reference](https://developer.intuit.com/app/developer/qbo/docs/api/graphql)
- [Accounting REST API](https://developer.intuit.com/app/developer/qbo/docs/api/accounting)
- [Developer Community](https://help.developer.intuit.com/)

### Tools
- [QuickBooks Developer Portal](https://developer.intuit.com/)
- [ngrok](https://ngrok.com/)
- [Composer](https://getcomposer.org/)

---

## Requirements Summary

| Requirement | Version | Install |
|-------------|---------|---------|
| **PHP** | 8.1+ | https://php.net |
| **Composer** | Latest | https://getcomposer.org |
| **ngrok** (optional) | 3.x | https://ngrok.com/download |

---

## Support

- **Issues**: Check [`TROUBLESHOOTING`](#troubleshooting) section above
- **Setup Help**: See [`SETUP_GUIDE.md`](./SETUP_GUIDE.md)
- **API Help**: See [`QUICK_START.md`](./QUICK_START.md)
- **QuickBooks Help**: https://help.developer.intuit.com/

---

## License

Educational/Sample Code - For demonstration and learning purposes.

---

**Version**: 2.0.0  
**Status**: ✅ Production Ready  
**Last Updated**: 2026-01-07
