# Custom Fields API Implementation Summary

This document summarizes the enhancements made to convert the QuickBooks sample app to fully support the Custom Fields API as documented at https://developer.intuit.com/app/developer/qbo/docs/workflows/create-custom-fields/get-started

## Completed Enhancements

### 1. ✅ NUMBER Custom Field Support
**Files Modified:**
- `src/Services/InvoiceService.php`
- `src/Routes/InvoiceRoutes.php`
- `pages/index.html`

**Changes:**
- Added `buildCustomFieldPayload()` method that correctly uses `NumberValue` for NUMBER fields and `StringValue` for STRING/DROPDOWN fields
- Updated `createInvoiceWithCostOfFuel()` to accept an optional `fieldType` parameter (defaults to 'NUMBER')
- Added UI dropdown to select field type when creating invoices

**Usage Example:**
```php
// For NUMBER fields
$customField = [
    'DefinitionId' => '1',
    'NumberValue' => 50.00
];

// For STRING fields
$customField = [
    'DefinitionId' => '2',
    'StringValue' => 'Some text'
];
```

### 2. ✅ General-Purpose Invoice Creation
**Files Created/Modified:**
- `src/Services/InvoiceService.php` - Added `createInvoice()` method
- `src/Routes/InvoiceRoutes.php` - Added `create()` route handler
- `pages/index.html` - Added UI section for general invoice creation

**Features:**
- Create invoices with multiple line items
- Support multiple custom fields of any type
- Accept additional invoice data (TxnDate, DueDate, DocNumber, etc.)
- Flexible line item configuration (quantity, description, etc.)

**API Endpoint:**
```
POST /api/quickbook/invoices
{
  "customerId": "1",
  "lineItems": [
    { "itemId": "1", "amount": 100.00, "quantity": 2 }
  ],
  "customFields": [
    { "definitionId": "1", "value": 50.00, "type": "NUMBER" }
  ],
  "additionalData": {
    "DocNumber": "INV-001"
  }
}
```

### 3. ✅ Customer Entity Custom Fields Support
**Files Created:**
- `src/Services/CustomerService.php`
- `src/Routes/CustomerRoutes.php`

**Files Modified:**
- `public/index.php` - Added customer routes
- `pages/index.html` - Added customer UI section

**Features:**
- Create customers with custom fields
- Update customers with custom fields
- Get customer with custom fields included
- Support for all customer properties (GivenName, FamilyName, PrimaryEmailAddr, etc.)

**API Endpoints:**
```
GET /api/quickbook/customers/:id
POST /api/quickbook/customers
PUT /api/quickbook/customers/:id
```

### 4. ✅ Item Entity Custom Fields Support
**Files Created:**
- `src/Services/ItemService.php`
- `src/Routes/ItemRoutes.php`

**Files Modified:**
- `public/index.php` - Added item routes
- `pages/index.html` - Added item UI section

**Features:**
- Create items (products/services) with custom fields
- Update items with custom fields
- Get item with custom fields included
- Support for all item types (Service, Inventory, NonInventory, etc.)
- Support for item properties (IncomeAccountRef, ExpenseAccountRef, etc.)

**API Endpoints:**
```
GET /api/quickbook/items/:id
POST /api/quickbook/items
PUT /api/quickbook/items/:id
```

### 5. ✅ Custom Field Data Type Validation
**Files Created:**
- `src/Services/CustomFieldValidationService.php`

**Files Modified:**
- `src/Services/InvoiceService.php` - Integrated validation
- `src/Services/CustomerService.php` - Integrated validation
- `src/Services/ItemService.php` - Integrated validation
- `src/Routes/CustomFieldsRoutes.php` - Added validation endpoint
- `pages/index.html` - Added validation UI

**Features:**
- Fetches custom field definitions from GraphQL API
- Caches definitions for performance
- Validates field values against their data types
- Checks if fields are active
- Validates dropdown options
- Auto-corrects type mismatches
- Provides detailed error messages

**Validation Rules:**
- **NUMBER fields**: Must be numeric, uses `NumberValue`
- **STRING fields**: Must be convertible to string, uses `StringValue`
- **DROPDOWN fields**: Must match one of the allowed options, uses `StringValue`

**API Endpoint:**
```
POST /api/quickbook/custom-fields/validate
{
  "customFields": [
    { "definitionId": "1", "value": 50.00, "type": "NUMBER" }
  ]
}
```

**Response:**
```json
{
  "valid": true,
  "errors": [],
  "warnings": []
}
```

## Architecture

### GraphQL API (App Foundations)
Used for managing custom field **definitions**:
- Create, read, update custom field definitions
- Get `legacyIDV2` which maps to `DefinitionId` in REST API
- Endpoint: `https://qb.api.intuit.com/graphql`

### REST API (Accounting API)
Used for applying custom fields to **entities**:
- Create/update invoices, customers, items with custom fields
- Uses `DefinitionId` (from `legacyIDV2`) to reference field definitions
- Uses `NumberValue` for NUMBER fields, `StringValue` for STRING/DROPDOWN
- Query parameter: `include=enhancedAllCustomFields`
- Endpoint: `https://quickbooks.api.intuit.com`

## Key Implementation Details

### Custom Field Payload Structure
```php
// NUMBER field
[
    'DefinitionId' => '1',
    'NumberValue' => 50.00
]

// STRING field
[
    'DefinitionId' => '2',
    'StringValue' => 'Text value'
]

// DROPDOWN field
[
    'DefinitionId' => '3',
    'StringValue' => 'Option1'  // Must match dropdown options
]
```

### Entity Creation with Custom Fields
All entity creation/update endpoints follow this pattern:
1. Validate that `CustomField` is not in `additionalData` (prevents silent data loss)
2. Validate custom fields against definitions
3. Auto-correct types if needed
4. Build custom field payloads with correct value types
5. Include `minorversion=75` and `include=enhancedAllCustomFields` in URL
6. Handle errors from QuickBooks API

**Important:** The `customFields` parameter is the dedicated way to pass custom fields. If you accidentally include a `CustomField` key in `additionalData`, the API will throw an `InvalidArgumentException` to prevent silent data loss.

### Validation Flow
1. Fetch custom field definitions from GraphQL API
2. Cache definitions in memory
3. For each custom field:
   - Check if definition exists
   - Check if field is active
   - Validate value type matches definition
   - For dropdowns, validate against allowed options
4. Return validation results with detailed errors

## Testing the Implementation

### 1. Create a Custom Field Definition
```
POST /api/quickbook/custom-fields
{
  "label": "Cost of Fuel",
  "dataType": "NUMBER",
  "active": true,
  "associations": [
    {
      "associatedEntity": "Invoice",
      "active": true,
      "allowedOperations": ["Create", "Update"]
    }
  ]
}
```

### 2. Validate Custom Fields
```
POST /api/quickbook/custom-fields/validate
{
  "customFields": [
    { "definitionId": "1", "value": 50.00, "type": "NUMBER" }
  ]
}
```

### 3. Create Invoice with Custom Field
```
POST /api/quickbook/invoices
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

## Best Practices Implemented

1. **Type Safety**: Automatic validation ensures correct data types
2. **Error Handling**: Comprehensive error messages for debugging
3. **Caching**: Custom field definitions are cached for performance
4. **Flexibility**: Support for multiple entities and field types
5. **Documentation**: Clear API structure and usage examples
6. **REST API Compliance**: Follows QuickBooks API conventions
7. **GraphQL Integration**: Proper use of App Foundations API
8. **Auto-correction**: Automatically fixes type mismatches when possible

## Files Structure

```
src/
├── GraphQL/
│   └── CustomFields/
│       ├── CreateCustomField.php
│       ├── GetAllCustomFields.php
│       └── UpdateCustomField.php
├── Routes/
│   ├── AuthRoutes.php
│   ├── CustomFieldsRoutes.php
│   ├── CustomerRoutes.php
│   ├── InvoiceRoutes.php
│   └── ItemRoutes.php
└── Services/
    ├── AuthService.php
    ├── CustomFieldsService.php
    ├── CustomFieldValidationService.php
    ├── CustomerService.php
    ├── InvoiceService.php
    └── ItemService.php
```

## Compliance with QuickBooks Documentation

This implementation fully complies with the QuickBooks Custom Fields API documentation:

✅ Uses GraphQL API for custom field definitions  
✅ Uses REST API for applying custom fields to entities  
✅ Correctly maps `legacyIDV2` to `DefinitionId`  
✅ Uses `NumberValue` for NUMBER fields  
✅ Uses `StringValue` for STRING and DROPDOWN fields  
✅ Includes `enhancedAllCustomFields` query parameter  
✅ Supports multiple entity types (Invoice, Customer, Item)  
✅ Validates field associations and data types  
✅ Handles active/inactive field states  

## Future Enhancements (Optional)

- Support for more entity types (Estimate, Bill, Vendor, etc.)
- Batch operations for multiple entities
- Custom field templates/presets
- Advanced dropdown option management
- Field dependency validation
- Audit logging for custom field changes
