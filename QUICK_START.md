# Quick Start Guide - Custom Fields API

## Overview
This app demonstrates how to use QuickBooks Online Custom Fields API with proper support for different data types (STRING, NUMBER, DROPDOWN) across multiple entities (Invoice, Customer, Item).

## Setup (5 minutes)

1. **Install dependencies:**
   ```bash
   composer install
   ```

2. **Configure environment:**
   ```bash
   cp .env.example .env
   # Edit .env with your QuickBooks app credentials
   ```

3. **Start the server:**
   ```bash
   php -S localhost:3000 -t public
   ```

4. **Open browser:**
   ```
   http://localhost:3000
   ```

## Workflow

### Step 1: Connect to QuickBooks
Click "Sign in with QuickBooks" and authorize the app.

### Step 2: Create Custom Field Definition
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

**Important:** Save the `legacyIDV2` value from the response - this is your `DefinitionId`!

### Step 3: Validate Custom Fields (Optional)
Before creating entities, validate your custom field values:

```json
{
  "customFields": [
    { "definitionId": "1", "value": 50.00, "type": "NUMBER" }
  ]
}
```

### Step 4: Create Entity with Custom Field

#### Invoice Example:
```json
{
  "customerId": "1",
  "lineItems": [
    { "itemId": "1", "amount": 100.00 }
  ],
  "customFields": [
    { "definitionId": "1", "value": 50.00, "type": "NUMBER" }
  ]
}
```

#### Customer Example:
```json
{
  "displayName": "John Doe",
  "customFields": [
    { "definitionId": "2", "value": "Premium", "type": "STRING" }
  ],
  "additionalData": {
    "GivenName": "John",
    "FamilyName": "Doe"
  }
}
```

#### Item Example:
```json
{
  "name": "Premium Service",
  "type": "Service",
  "customFields": [
    { "definitionId": "3", "value": 100, "type": "NUMBER" }
  ],
  "additionalData": {
    "IncomeAccountRef": { "value": "79" }
  }
}
```

## Key Concepts

### Data Types
- **NUMBER**: Use for numeric values (prices, quantities, ratings)
  - API uses `NumberValue` field
  - Example: `50.00`, `100`, `4.5`

- **STRING**: Use for text values (notes, descriptions, names)
  - API uses `StringValue` field
  - Example: `"Premium"`, `"Notes here"`

- **DROPDOWN**: Use for predefined options
  - API uses `StringValue` field
  - Must match one of the dropdown options
  - Example: `"Option1"`, `"Option2"`

### Custom Field Structure
```javascript
{
  "definitionId": "1",  // legacyIDV2 from GraphQL
  "value": 50.00,       // The actual value
  "type": "NUMBER"      // STRING, NUMBER, or DROPDOWN
}
```

### API Endpoints

#### Custom Field Definitions (GraphQL)
- `GET /api/quickbook/custom-fields` - List all definitions
- `POST /api/quickbook/custom-fields` - Create definition
- `PUT /api/quickbook/custom-fields/:id` - Update definition
- `POST /api/quickbook/custom-fields/validate` - Validate fields

#### Entities with Custom Fields (REST)
- **Invoices**: `POST /api/quickbook/invoices`
- **Customers**: `POST /api/quickbook/customers`
- **Items**: `POST /api/quickbook/items`

## Common Issues & Solutions

### Issue: "Type mismatch" error
**Solution:** Make sure the `type` field matches the custom field definition's `dataType`.

### Issue: "Definition not found" warning
**Solution:** The validation cache may be stale. The validation endpoint automatically refreshes it.

### Issue: "Value must be numeric for NUMBER field"
**Solution:** Ensure you're passing a number, not a string: `50.00` not `"50.00"`

### Issue: "Value is not a valid dropdown option"
**Solution:** Check the allowed dropdown options in the field definition and use an exact match.

### Issue: Custom field not appearing in QuickBooks
**Solution:** 
1. Check the field is `active: true`
2. Verify the association includes the entity type
3. Ensure you're using the correct `legacyIDV2` as `definitionId`

## Testing Checklist

- [ ] Create NUMBER custom field definition
- [ ] Create STRING custom field definition
- [ ] Validate custom fields before use
- [ ] Create invoice with NUMBER custom field
- [ ] Create customer with STRING custom field
- [ ] Create item with custom field
- [ ] Update entity with custom field
- [ ] Verify fields appear in QuickBooks UI

## API Reference

### GraphQL (Definitions)
```graphql
# Get all custom field definitions
query GetCustomFieldDefinitions {
  appFoundationsCustomFieldDefinitions {
    edges {
      node {
        id
        legacyIDV2  # Use this as DefinitionId in REST API
        label
        dataType
        active
      }
    }
  }
}
```

### REST API (Entities)
```http
POST /v3/company/{realmId}/invoice?minorversion=75&include=enhancedAllCustomFields
Content-Type: application/json

{
  "CustomerRef": { "value": "1" },
  "Line": [...],
  "CustomField": [
    {
      "DefinitionId": "1",
      "NumberValue": 50.00
    }
  ]
}
```

## Best Practices

1. **Always validate** custom fields before creating entities
2. **Use correct types**: NUMBER for numbers, STRING for text
3. **Use the customFields parameter** - Never put `CustomField` in `additionalData`
4. **Cache definitions** for better performance (done automatically)
5. **Check field is active** before using it
6. **Handle errors gracefully** - QuickBooks may reject invalid data
7. **Use legacyIDV2** from GraphQL as DefinitionId in REST API

## Important Note: Custom Fields Parameter

⚠️ **Always use the dedicated `customFields` parameter** for passing custom fields. Do NOT include a `CustomField` key in `additionalData`:

```javascript
// ✅ CORRECT
{
  "customFields": [
    { "definitionId": "1", "value": 50.00, "type": "NUMBER" }
  ],
  "additionalData": {
    "DocNumber": "INV-001"
  }
}

// ❌ WRONG - Will throw an error
{
  "additionalData": {
    "CustomField": [...],  // Don't do this!
    "DocNumber": "INV-001"
  }
}
```

This validation prevents silent data loss where `additionalData` could accidentally overwrite your custom fields.

## Support

For QuickBooks API documentation:
- Custom Fields: https://developer.intuit.com/app/developer/qbo/docs/workflows/create-custom-fields/get-started
- GraphQL API: https://developer.intuit.com/app/developer/qbo/docs/api/graphql
- REST API: https://developer.intuit.com/app/developer/qbo/docs/api/accounting/all-entities/invoice

## Example: Complete Workflow

```bash
# 1. Create custom field definition
curl -X POST http://localhost:3000/api/quickbook/custom-fields \
  -H "Content-Type: application/json" \
  -d '{
    "label": "Priority Level",
    "dataType": "NUMBER",
    "active": true,
    "associations": [{
      "associatedEntity": "Invoice",
      "active": true,
      "allowedOperations": ["Create", "Update"]
    }]
  }'

# Response: { "id": "...", "legacyIDV2": "1", ... }

# 2. Validate before use
curl -X POST http://localhost:3000/api/quickbook/custom-fields/validate \
  -H "Content-Type: application/json" \
  -d '{
    "customFields": [
      { "definitionId": "1", "value": 5, "type": "NUMBER" }
    ]
  }'

# Response: { "valid": true, "errors": [], "warnings": [] }

# 3. Create invoice with custom field
curl -X POST http://localhost:3000/api/quickbook/invoices \
  -H "Content-Type: application/json" \
  -d '{
    "customerId": "1",
    "lineItems": [
      { "itemId": "1", "amount": 100.00 }
    ],
    "customFields": [
      { "definitionId": "1", "value": 5, "type": "NUMBER" }
    ]
  }'
```

## Next Steps

1. Explore the UI at `http://localhost:3000`
2. Try creating different custom field types
3. Test validation with invalid data
4. Create entities with multiple custom fields
5. Check the QuickBooks UI to see your custom fields

Happy coding! 🚀
