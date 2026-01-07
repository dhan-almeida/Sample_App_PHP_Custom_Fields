# QuickBooks Online Custom Fields - PHP Sample Application

A production-ready PHP application demonstrating **best practices** for working with QuickBooks Online Custom Fields API. This app showcases proper implementation of custom fields across multiple entity types with comprehensive validation and security features.

---

## 📋 Table of Contents

- [What This App Does](#what-this-app-does)
- [Key Features](#key-features)
- [Requirements](#requirements)
- [Quick Start](#quick-start)
- [How to Use](#how-to-use)
- [API Reference](#api-reference)
- [Architecture](#architecture)
- [Security Features](#security-features)
- [Troubleshooting](#troubleshooting)
- [Additional Resources](#additional-resources)

---

## 🎯 What This App Does

This application demonstrates the **complete workflow** for working with QuickBooks Online Custom Fields:

### 1. **Manage Custom Field Definitions** (GraphQL API)
- Create custom field definitions with different data types (STRING, NUMBER, DROPDOWN)
- Update existing field definitions
- Associate fields with specific entities (Invoice, Customer, Item)
- Validate field configurations

### 2. **Apply Custom Fields to Entities** (REST API)
- Create **Invoices** with custom fields (e.g., "Cost of Fuel", "Priority Level")
- Create **Customers** with custom fields (e.g., "Customer Tier", "Account Manager")
- Create **Items** with custom fields (e.g., "Supplier Code", "Warranty Period")
- Update entities while preserving custom field data

### 3. **Validate & Protect Data**
- Automatic validation of custom field data types
- Protection against field overwriting bugs
- Security hardening for critical fields (Id, SyncToken)
- Clear, actionable error messages

---

## ✨ Key Features

### 🔒 **Security & Data Protection**
- ✅ Prevents CustomField overwriting via `additionalData`
- ✅ Protects core fields (Line, CustomerRef, DisplayName, etc.)
- ✅ Prevents Id and SyncToken tampering
- ✅ Input validation and sanitization

### 🎨 **Smart Type Handling**
- ✅ Automatic `NumberValue` vs `StringValue` selection
- ✅ Type validation and auto-correction
- ✅ Dropdown option validation
- ✅ Active field checking

### 🚀 **Developer Experience**
- ✅ Clean, RESTful API design
- ✅ Comprehensive error messages
- ✅ Interactive web UI for testing
- ✅ Full documentation with examples

### 📊 **Production Ready**
- ✅ Proper error handling
- ✅ Validation caching for performance
- ✅ PSR-compliant code structure
- ✅ No silent data loss

---

## 📦 Requirements

### System Requirements
- **PHP**: 8.1 or later
- **Composer**: Latest version
- **Web Server**: PHP built-in server or Apache/Nginx

### QuickBooks Requirements
- QuickBooks Online account (Sandbox or Production)
- QuickBooks app with OAuth 2.0 credentials
- App Foundations API access (for GraphQL)

### PHP Extensions
- `php-curl`
- `php-json`
- `php-mbstring`

---

## 🚀 Quick Start

### 1. Install Dependencies

```bash
composer install
```

### 2. Configure Environment

Create a `.env` file in the project root:

```bash
cp .env.example .env
```

Edit `.env` with your QuickBooks app credentials:

```env
# QuickBooks OAuth 2.0 Credentials
CLIENT_ID=your_client_id_here
CLIENT_SECRET=your_client_secret_here
REDIRECT_URI=http://localhost:3000/api/auth/callback

# Environment
ENVIRONMENT=production

# API Endpoints (defaults shown)
APP_FOUNDATIONS_GRAPHQL_URL=https://qb.api.intuit.com/graphql
QBO_BASE_URL=https://quickbooks.api.intuit.com
```

### 3. Start the Server

```bash
php -S localhost:3000 -t public
```

### 4. Open in Browser

Navigate to: **http://localhost:3000**

---

## 📖 How to Use

### Step 1: Authenticate with QuickBooks

1. Click **"Sign in with QuickBooks"**
2. Authorize the app to access your QuickBooks company
3. You'll be redirected back to the app

### Step 2: Create Custom Field Definition

Use the **"Custom fields (App Foundations GraphQL)"** section:

```json
{
  "label": "Cost of Fuel",
  "dataType": "NUMBER",
  "active": true,
  "associations": [
    {
      "associatedEntity": "Invoice",
      "active": true,
      "validationOptions": { "required": false },
      "allowedOperations": ["Create", "Update"]
    }
  ]
}
```

**Important**: Save the `legacyIDV2` value from the response - this is your `DefinitionId`!

### Step 3: Validate Custom Fields (Optional but Recommended)

Before creating entities, validate your custom field values:

```json
{
  "customFields": [
    { "definitionId": "1", "value": 50.00, "type": "NUMBER" }
  ]
}
```

The validation will:
- ✅ Check if the field exists and is active
- ✅ Verify the data type matches
- ✅ Auto-correct type mismatches if possible
- ✅ Validate dropdown options

### Step 4: Create Entity with Custom Field

#### Example: Create Invoice

```json
{
  "customerId": "1",
  "lineItems": [
    { "itemId": "1", "amount": 100.00, "quantity": 2 }
  ],
  "customFields": [
    { "definitionId": "1", "value": 50.00, "type": "NUMBER" }
  ],
  "additionalData": {
    "DocNumber": "INV-001",
    "TxnDate": "2026-01-07"
  }
}
```

#### Example: Create Customer

```json
{
  "displayName": "John Doe",
  "customFields": [
    { "definitionId": "2", "value": "Premium", "type": "STRING" }
  ],
  "additionalData": {
    "GivenName": "John",
    "FamilyName": "Doe",
    "PrimaryEmailAddr": { "Address": "john@example.com" }
  }
}
```

#### Example: Create Item

```json
{
  "name": "Premium Service",
  "type": "Service",
  "customFields": [
    { "definitionId": "3", "value": 100, "type": "NUMBER" }
  ],
  "additionalData": {
    "IncomeAccountRef": { "value": "79" },
    "Description": "Premium service offering"
  }
}
```

---

## 🔌 API Reference

### Authentication Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/auth/login` | Initiates OAuth 2.0 flow |
| GET | `/api/auth/callback` | Handles OAuth callback |
| POST | `/api/auth/retrieveToken` | Returns current token (debug) |

### Custom Field Definition Endpoints (GraphQL)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/quickbook/custom-fields` | List all custom field definitions |
| POST | `/api/quickbook/custom-fields` | Create a custom field definition |
| PUT | `/api/quickbook/custom-fields/:id` | Update a custom field definition |
| DELETE | `/api/quickbook/custom-fields/:id` | Deactivate a custom field definition |
| POST | `/api/quickbook/custom-fields/validate` | Validate custom field values |

### Invoice Endpoints (REST API)

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/quickbook/invoices` | Create invoice with custom fields |
| POST | `/api/quickbook/invoices/cost-of-fuel` | Create invoice (specific example) |

### Customer Endpoints (REST API)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/quickbook/customers/:id` | Get customer with custom fields |
| POST | `/api/quickbook/customers` | Create customer with custom fields |
| PUT | `/api/quickbook/customers/:id` | Update customer with custom fields |

### Item Endpoints (REST API)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/quickbook/items/:id` | Get item with custom fields |
| POST | `/api/quickbook/items` | Create item with custom fields |
| PUT | `/api/quickbook/items/:id` | Update item with custom fields |

---

## 🏗️ Architecture

### Two-API Approach

This app correctly implements QuickBooks' two-API architecture:

#### 1. **GraphQL API** (App Foundations)
- **Purpose**: Manage custom field **definitions**
- **Endpoint**: `https://qb.api.intuit.com/graphql`
- **Operations**: Create, read, update field definitions
- **Returns**: `legacyIDV2` (used as `DefinitionId` in REST API)

#### 2. **REST API** (Accounting API)
- **Purpose**: Apply custom fields to **entities**
- **Endpoint**: `https://quickbooks.api.intuit.com`
- **Operations**: Create/update entities with custom fields
- **Uses**: `DefinitionId` (from GraphQL `legacyIDV2`)

### Data Flow

```
1. GraphQL API → Create Custom Field Definition
   ↓
2. Receive legacyIDV2 (e.g., "1")
   ↓
3. REST API → Create Entity with CustomField
   {
     "DefinitionId": "1",
     "NumberValue": 50.00
   }
   ↓
4. Entity created with custom field visible in QuickBooks
```

### Custom Field Payload Structure

```php
// NUMBER field
[
    'DefinitionId' => '1',
    'NumberValue' => 50.00  // Use NumberValue for NUMBER type
]

// STRING field
[
    'DefinitionId' => '2',
    'StringValue' => 'Text'  // Use StringValue for STRING type
]

// DROPDOWN field
[
    'DefinitionId' => '3',
    'StringValue' => 'Option1'  // Use StringValue for DROPDOWN type
]
```

### Project Structure

```
sampleapp-customfields-php-full/
├── public/
│   └── index.php              # Main entry point & router
├── pages/
│   └── index.html             # Interactive web UI
├── src/
│   ├── GraphQL/
│   │   └── CustomFields/      # GraphQL queries & mutations
│   ├── Routes/                # API route handlers
│   │   ├── AuthRoutes.php
│   │   ├── CustomFieldsRoutes.php
│   │   ├── CustomerRoutes.php
│   │   ├── InvoiceRoutes.php
│   │   └── ItemRoutes.php
│   └── Services/              # Business logic
│       ├── AuthService.php
│       ├── CustomFieldsService.php
│       ├── CustomFieldValidationService.php
│       ├── CustomerService.php
│       ├── InvoiceService.php
│       └── ItemService.php
├── .env                       # Configuration (create from .env.example)
├── composer.json              # Dependencies
└── README.md                  # This file
```

---

## 🔒 Security Features

### 1. Field Overwrite Protection

The app prevents silent data loss by validating `additionalData`:

```php
// ✅ CORRECT - Use dedicated parameters
createInvoice(
    customerId: '1',
    lineItems: [...],
    customFields: [...]  // Use this for custom fields
);

// ❌ BLOCKED - Throws InvalidArgumentException
createInvoice(
    customerId: '1',
    lineItems: [...],
    additionalData: [
        'CustomField' => [...]  // This will be rejected
    ]
);
```

### 2. Core Field Protection

Protected fields that cannot be in `additionalData`:

| Entity | Protected Fields | Reason |
|--------|------------------|--------|
| Invoice | `Line`, `CustomerRef` | Use method parameters |
| Customer (Create) | `DisplayName` | Use method parameter |
| Customer (Update) | `Id`, `SyncToken` | Managed internally |
| Item (Create) | `Name`, `Type` | Use method parameters |
| Item (Update) | `Id`, `SyncToken` | Managed internally |

### 3. Validation Before API Calls

All custom fields are validated **before** making QuickBooks API calls:

1. ✅ Field exists and is active
2. ✅ Data type matches definition
3. ✅ Dropdown options are valid
4. ✅ No protected fields in additionalData

This prevents:
- Wasted API calls with invalid data
- Silent data corruption
- Security vulnerabilities (Id/SyncToken tampering)

---

## 🔧 Troubleshooting

### Common Issues

#### Issue: "Not authenticated" error
**Solution**: Click "Sign in with QuickBooks" to authorize the app.

#### Issue: "CustomField should not be in additionalData"
**Solution**: Use the `customFields` parameter instead of putting `CustomField` in `additionalData`.

```json
// ✅ Correct
{
  "customFields": [{"definitionId": "1", "value": 50}],
  "additionalData": {"DocNumber": "INV-001"}
}

// ❌ Wrong
{
  "additionalData": {
    "CustomField": [{"DefinitionId": "1", "NumberValue": 50}]
  }
}
```

#### Issue: "Value must be numeric for NUMBER field"
**Solution**: Ensure you're passing a number, not a string:

```json
// ✅ Correct
{"definitionId": "1", "value": 50.00, "type": "NUMBER"}

// ❌ Wrong
{"definitionId": "1", "value": "50.00", "type": "NUMBER"}
```

#### Issue: "Definition ID not found"
**Solution**: 
1. Make sure you're using `legacyIDV2` from the GraphQL response
2. Check that the custom field is `active: true`
3. Verify the field is associated with the entity type you're creating

#### Issue: Custom field not appearing in QuickBooks
**Solution**:
1. Verify the field definition has the correct `associatedEntity`
2. Check that `allowedOperations` includes "Create" or "Update"
3. Ensure you're using the correct `DefinitionId` (legacyIDV2)
4. Use the validation endpoint to check for issues

### Debug Mode

To see detailed error information, check the browser console and network tab:

1. Open Developer Tools (F12)
2. Go to Network tab
3. Make an API call
4. Click on the request to see full response

---

## 📚 Additional Resources

### Official Documentation
- [QuickBooks Custom Fields Guide](https://developer.intuit.com/app/developer/qbo/docs/workflows/create-custom-fields/get-started)
- [App Foundations GraphQL API](https://developer.intuit.com/app/developer/qbo/docs/api/graphql)
- [QuickBooks Accounting API](https://developer.intuit.com/app/developer/qbo/docs/api/accounting/all-entities/invoice)

### Project Documentation
- [`IMPLEMENTATION_SUMMARY.md`](./IMPLEMENTATION_SUMMARY.md) - Technical implementation details
- [`QUICK_START.md`](./QUICK_START.md) - Quick reference guide
- [`BUGFIX_CUSTOMFIELD_OVERWRITE.md`](./BUGFIX_CUSTOMFIELD_OVERWRITE.md) - Bug fix documentation
- [`SYSTEM_TEST_RESULTS.md`](./SYSTEM_TEST_RESULTS.md) - Comprehensive test results

### Custom Field Data Types

| Type | Description | API Field | Example Value |
|------|-------------|-----------|---------------|
| STRING | Text values | `StringValue` | `"Premium Customer"` |
| NUMBER | Numeric values | `NumberValue` | `50.00` |
| DROPDOWN | Predefined options | `StringValue` | `"Option1"` |

### Best Practices

1. **Always validate** custom fields before creating entities
2. **Use correct types**: NUMBER for numbers, STRING for text
3. **Never put CustomField in additionalData** - Use the `customFields` parameter
4. **Cache definitions** for better performance (done automatically)
5. **Check field is active** before using it
6. **Handle errors gracefully** - QuickBooks may reject invalid data
7. **Use legacyIDV2** from GraphQL as DefinitionId in REST API
8. **Test in sandbox** before deploying to production

---

## 🤝 Support & Contributing

### Getting Help
- Check the [Troubleshooting](#troubleshooting) section
- Review the [Additional Resources](#additional-resources)
- Examine the test results in `SYSTEM_TEST_RESULTS.md`

### Reporting Issues
When reporting issues, please include:
- PHP version
- Error message (full text)
- Request payload (sanitized)
- Steps to reproduce

---

## ⚠️ Important Notes

### Production Deployment
- **DO NOT** commit `.env` file to version control
- **DO** use environment variables for sensitive data
- **DO** implement rate limiting for production use
- **DO** add proper logging and monitoring
- **DO** test thoroughly in sandbox before production

### Limitations
- This is a **sample application** for demonstration purposes
- Tokens are stored in memory (use database/Redis for production)
- No rate limiting implemented (add for production)
- No user management (single OAuth session)

### License
This sample application is provided as-is for educational purposes.

---

## 🎉 Summary

This application demonstrates **production-ready** implementation of QuickBooks Custom Fields API with:

✅ **Complete Coverage**: Invoices, Customers, Items  
✅ **Type Safety**: Automatic NUMBER/STRING handling  
✅ **Data Protection**: No silent field overwriting  
✅ **Security**: Id/SyncToken tampering prevention  
✅ **Validation**: Pre-flight checks before API calls  
✅ **Developer UX**: Clear errors and documentation  

**Ready to use, learn from, and extend!** 🚀

---

**Version**: 2.0  
**Last Updated**: 2026-01-07  
**Status**: ✅ Production Ready
