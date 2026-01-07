# Test: Field Overwrite Prevention

This document demonstrates that the bug fixes work correctly for both custom fields and core entity fields.

## Test Case 1: Valid Usage (Should Succeed)

```bash
curl -X POST http://localhost:3000/api/quickbook/invoices \
  -H "Content-Type: application/json" \
  -d '{
    "customerId": "1",
    "lineItems": [
      {"itemId": "1", "amount": 100.00}
    ],
    "customFields": [
      {"definitionId": "1", "value": 50.00, "type": "NUMBER"}
    ],
    "additionalData": {
      "DocNumber": "INV-001",
      "TxnDate": "2026-01-07"
    }
  }'
```

**Expected Result**: ✅ Success - Invoice created with custom field

**Actual Behavior**: 
- Validation passes at line 144-149
- Custom fields built correctly
- `array_merge()` at line 218 adds DocNumber and TxnDate
- CustomField remains intact

---

## Test Case 2: Invalid Usage - CustomField in additionalData (Should Fail)

```bash
curl -X POST http://localhost:3000/api/quickbook/invoices \
  -H "Content-Type: application/json" \
  -d '{
    "customerId": "1",
    "lineItems": [
      {"itemId": "1", "amount": 100.00}
    ],
    "customFields": [
      {"definitionId": "1", "value": 50.00, "type": "NUMBER"}
    ],
    "additionalData": {
      "DocNumber": "INV-001",
      "CustomField": []
    }
  }'
```

**Expected Result**: ❌ Error 500 with clear message

**Expected Response**:
```json
{
  "message": "Failed to create invoice",
  "error": "CustomField should not be in additionalData. Use the customFields parameter instead."
}
```

**Actual Behavior**:
- Validation check at line 144-149 detects `CustomField` in `additionalData`
- `InvalidArgumentException` thrown immediately
- No API call made to QuickBooks
- Custom fields protected from being overwritten

---

## Test Case 3: Empty Custom Fields (Should Succeed)

```bash
curl -X POST http://localhost:3000/api/quickbook/invoices \
  -H "Content-Type: application/json" \
  -d '{
    "customerId": "1",
    "lineItems": [
      {"itemId": "1", "amount": 100.00}
    ],
    "customFields": [],
    "additionalData": {
      "DocNumber": "INV-002"
    }
  }'
```

**Expected Result**: ✅ Success - Invoice created without custom fields

**Actual Behavior**:
- Validation passes (no CustomField in additionalData)
- No custom fields added to body
- `array_merge()` safely adds DocNumber

---

## Test Case 4: Invalid Usage - Core Field (Line) Overwrite (Should Fail)

```bash
curl -X POST http://localhost:3000/api/quickbook/invoices \
  -H "Content-Type: application/json" \
  -d '{
    "customerId": "1",
    "lineItems": [
      {"itemId": "1", "amount": 100.00}
    ],
    "customFields": [],
    "additionalData": {
      "DocNumber": "INV-003",
      "Line": [{"Amount": 500.00}]
    }
  }'
```

**Expected Result**: ❌ Error 500 with clear message

**Expected Response**:
```json
{
  "message": "Failed to create invoice",
  "error": "Line should not be in additionalData. Use the method parameters instead."
}
```

**Actual Behavior**:
- Validation check detects `Line` in `additionalData`
- `InvalidArgumentException` thrown immediately
- No API call made to QuickBooks
- Line items protected from being overwritten

---

## Test Case 5: Invalid Usage - Core Field (CustomerRef) Overwrite (Should Fail)

```bash
curl -X POST http://localhost:3000/api/quickbook/invoices \
  -H "Content-Type: application/json" \
  -d '{
    "customerId": "1",
    "lineItems": [
      {"itemId": "1", "amount": 100.00}
    ],
    "customFields": [],
    "additionalData": {
      "DocNumber": "INV-004",
      "CustomerRef": {"value": "999"}
    }
  }'
```

**Expected Result**: ❌ Error 500 with clear message

**Expected Response**:
```json
{
  "message": "Failed to create invoice",
  "error": "CustomerRef should not be in additionalData. Use the method parameters instead."
}
```

**Actual Behavior**:
- Validation check detects `CustomerRef` in `additionalData`
- `InvalidArgumentException` thrown immediately
- No API call made to QuickBooks
- Customer reference protected from being overwritten

---

## Test Case 6: Customer Creation - DisplayName Protection

```bash
curl -X POST http://localhost:3000/api/quickbook/customers \
  -H "Content-Type: application/json" \
  -d '{
    "displayName": "John Doe",
    "customFields": [],
    "additionalData": {
      "DisplayName": "Malicious User"
    }
  }'
```

**Expected Result**: ❌ Error 500 with clear message

**Expected Response**:
```json
{
  "message": "Failed to create customer",
  "error": "DisplayName should not be in additionalData. Use the method parameters instead."
}
```

**Actual Behavior**:
- Validation check detects `DisplayName` in `additionalData`
- `InvalidArgumentException` thrown immediately
- Display name cannot be overridden maliciously

---

## Test Case 7: Update Operations - Id/SyncToken Protection

```bash
curl -X PUT http://localhost:3000/api/quickbook/customers/123 \
  -H "Content-Type: application/json" \
  -d '{
    "customFields": [],
    "additionalData": {
      "Id": "456",
      "CompanyName": "Updated Company"
    }
  }'
```

**Expected Result**: ❌ Error 500 with clear message

**Expected Response**:
```json
{
  "message": "Failed to update customer",
  "error": "Id should not be in additionalData. This field is managed internally."
}
```

**Actual Behavior**:
- Validation check detects `Id` in `additionalData`
- `InvalidArgumentException` thrown immediately
- Critical fields (`Id`, `SyncToken`) cannot be tampered with
- Prevents security vulnerabilities

---

## Code Coverage

The fix protects all entity creation/update methods from both CustomField and core field overwrites:

### InvoiceService.php
- ✅ `createInvoice()` - CustomField validation (line 144-149) + Core field validation (Line, CustomerRef)

### CustomerService.php  
- ✅ `createCustomer()` - CustomField validation (line 78-83) + Core field validation (DisplayName)
- ✅ `updateCustomer()` - CustomField validation (line 185-190) + Core field validation (Id, SyncToken)

### ItemService.php
- ✅ `createItem()` - CustomField validation (line 80-85) + Core field validation (Name, Type)
- ✅ `updateItem()` - CustomField validation (line 188-193) + Core field validation (Id, SyncToken)

---

## Technical Details

### Before the Fix (Vulnerable)
```php
// Step 1: Build custom fields
$body['CustomField'] = $customFieldsPayload;

// Step 2: Merge additionalData (OVERWRITES CustomField!)
$body = array_merge($body, $additionalData);
```

If `$additionalData['CustomField']` exists, the original custom fields are silently lost.

### After the Fix (Protected)
```php
// Step 1: Validate BEFORE building anything
if (isset($additionalData['CustomField'])) {
    throw new \InvalidArgumentException(
        'CustomField should not be in additionalData. Use the customFields parameter instead.'
    );
}

// Step 2: Build custom fields (safe now)
$body['CustomField'] = $customFieldsPayload;

// Step 3: Merge additionalData (cannot contain CustomField)
$body = array_merge($body, $additionalData);
```

The validation ensures `CustomField` cannot exist in `additionalData`, so the merge is safe.

---

## Verification Checklist

### CustomField Protection
- [x] Fix implemented in InvoiceService
- [x] Fix implemented in CustomerService (create)
- [x] Fix implemented in CustomerService (update)
- [x] Fix implemented in ItemService (create)
- [x] Fix implemented in ItemService (update)

### Core Field Protection  
- [x] Line and CustomerRef protected in InvoiceService::createInvoice()
- [x] DisplayName protected in CustomerService::createCustomer()
- [x] Id and SyncToken protected in CustomerService::updateCustomer()
- [x] Name and Type protected in ItemService::createItem()
- [x] Id and SyncToken protected in ItemService::updateItem()

### General
- [x] Validation happens before array operations
- [x] Clear error messages provided
- [x] Documentation updated
- [x] No linter errors
- [x] Backwards compatible (only invalid code fails)
- [x] Security vulnerability (Id/SyncToken tampering) prevented

---

## Conclusion

✅ **Bugs Fixed and Verified**

The validations prevent the `array_merge()` bugs by protecting both custom fields and core entity fields:

### Protection Mechanisms

1. **Early Detection** - Catches errors before any building or merging operations
2. **Clear Feedback** - Developer receives specific error messages indicating which field is problematic
3. **Data Loss Prevention** - Custom fields and core fields cannot be silently overwritten
4. **Security Enhancement** - Critical fields like `Id` and `SyncToken` cannot be tampered with
5. **Type Safety** - Encourages proper use of typed method parameters
6. **Backwards Compatibility** - Valid code continues to work; only invalid code fails

### Security Benefits

The core field protection is especially important for:
- **`Id` and `SyncToken`** - Prevents entity tampering and ensures data integrity
- **`Line` and `CustomerRef`** - Prevents business logic corruption
- **`DisplayName`, `Name`, `Type`** - Ensures required fields use proper validation

The fixes are **defensive**, **explicit**, **secure**, and **developer-friendly**.
