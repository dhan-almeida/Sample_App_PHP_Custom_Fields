# Bug Fix: CustomField Data Loss Prevention

## Issue Description

A critical bug was identified where the `array_merge()` operation in entity creation methods could silently overwrite custom fields if `additionalData` contained a `CustomField` key.

### Affected Methods
- `InvoiceService::createInvoice()`
- `CustomerService::createCustomer()`
- `CustomerService::updateCustomer()`
- `ItemService::createItem()`
- `ItemService::updateItem()`

### Root Cause

The methods followed this pattern:

```php
// Step 1: Build custom fields from dedicated parameter
if (!empty($customFieldsPayload)) {
    $body['CustomField'] = $customFieldsPayload;
}

// Step 2: Merge additionalData
$body = array_merge($body, $additionalData);  // ⚠️ Overwrites CustomField if present
```

Since `array_merge()` overwrites keys from the first array with values from the second array, if `$additionalData` contained a `CustomField` key, it would silently replace the carefully constructed custom fields from the `$customFields` parameter.

### Example of the Bug

```php
// User calls:
InvoiceService::createInvoice(
    customerId: '1',
    lineItems: [...],
    customFields: [
        ['definitionId' => '1', 'value' => 50.00, 'type' => 'NUMBER']
    ],
    additionalData: [
        'DocNumber' => 'INV-001',
        'CustomField' => []  // ⚠️ Silently overwrites custom fields!
    ]
);

// Result: The custom field with value 50.00 is lost!
```

## The Fix

Added explicit validation in all affected methods to prevent `CustomField` from being in `additionalData`:

```php
// Prevent CustomField in additionalData from overwriting the customFields parameter
if (isset($additionalData['CustomField'])) {
    throw new \InvalidArgumentException(
        'CustomField should not be in additionalData. Use the customFields parameter instead.'
    );
}
```

### Benefits of This Approach

1. **Prevents Silent Data Loss**: The API now fails fast with a clear error message
2. **Clear API Contract**: Separates concerns - custom fields have their own parameter
3. **Developer-Friendly**: Error message explains exactly what went wrong
4. **Backwards Compatible**: Valid code continues to work; only catches incorrect usage

## Updated API Usage

### ✅ Correct Usage

```php
InvoiceService::createInvoice(
    customerId: '1',
    lineItems: [
        ['itemId' => '1', 'amount' => 100.00]
    ],
    customFields: [
        ['definitionId' => '1', 'value' => 50.00, 'type' => 'NUMBER']
    ],
    additionalData: [
        'DocNumber' => 'INV-001',
        'TxnDate' => '2026-01-07'
    ]
);
```

### ❌ Now Throws Error (Prevents Bug)

```php
InvoiceService::createInvoice(
    customerId: '1',
    lineItems: [
        ['itemId' => '1', 'amount' => 100.00]
    ],
    customFields: [
        ['definitionId' => '1', 'value' => 50.00, 'type' => 'NUMBER']
    ],
    additionalData: [
        'DocNumber' => 'INV-001',
        'CustomField' => [...]  // ❌ Throws InvalidArgumentException
    ]
);

// Error: "CustomField should not be in additionalData. Use the customFields parameter instead."
```

## Files Modified

1. **src/Services/InvoiceService.php**
   - Added validation in `createInvoice()` method
   
2. **src/Services/CustomerService.php**
   - Added validation in `createCustomer()` method
   - Added validation in `updateCustomer()` method
   
3. **src/Services/ItemService.php**
   - Added validation in `createItem()` method
   - Added validation in `updateItem()` method

4. **IMPLEMENTATION_SUMMARY.md**
   - Updated entity creation pattern documentation
   - Added note about validation
   
5. **QUICK_START.md**
   - Added "Best Practices" section with examples
   - Documented correct vs incorrect usage

## Testing

### Test Case 1: Valid Usage (Should Work)
```bash
curl -X POST http://localhost:3000/api/quickbook/invoices \
  -H "Content-Type: application/json" \
  -d '{
    "customerId": "1",
    "lineItems": [{"itemId": "1", "amount": 100.00}],
    "customFields": [{"definitionId": "1", "value": 50.00, "type": "NUMBER"}],
    "additionalData": {"DocNumber": "INV-001"}
  }'

# Expected: Success - invoice created with custom field
```

### Test Case 2: Invalid Usage (Should Fail)
```bash
curl -X POST http://localhost:3000/api/quickbook/invoices \
  -H "Content-Type: application/json" \
  -d '{
    "customerId": "1",
    "lineItems": [{"itemId": "1", "amount": 100.00}],
    "customFields": [{"definitionId": "1", "value": 50.00, "type": "NUMBER"}],
    "additionalData": {
      "DocNumber": "INV-001",
      "CustomField": []
    }
  }'

# Expected: Error 500
# Response: {
#   "message": "Failed to create invoice",
#   "error": "CustomField should not be in additionalData. Use the customFields parameter instead."
# }
```

## Impact Assessment

### Risk Level: HIGH → FIXED
- **Before**: Silent data loss - custom fields could be accidentally overwritten
- **After**: Explicit error prevents data loss and guides developers

### Breaking Changes: NONE
- Valid code continues to work
- Only code with the bug will now throw an error (which is the desired behavior)

### Migration Required: NO
- Existing valid implementations are unaffected
- Invalid implementations will now fail with a clear error message

## Recommendations

1. **For API Users**: Review any code that uses `additionalData` to ensure it doesn't include `CustomField`
2. **For Developers**: Always use the `customFields` parameter for custom fields
3. **For QA**: Add test cases to verify the validation catches invalid usage
4. **For Documentation**: The updated guides include clear examples of correct usage

## Related Documentation

- [QUICK_START.md](./QUICK_START.md) - See "Important Note: Custom Fields Parameter"
- [IMPLEMENTATION_SUMMARY.md](./IMPLEMENTATION_SUMMARY.md) - See "Entity Creation with Custom Fields"
- [README.md](./README.md) - See "Features" section

## Conclusion

This fix prevents a subtle but serious bug where custom fields could be silently lost due to `array_merge()` behavior. The validation ensures that the API contract is clear: use the `customFields` parameter for custom fields, and `additionalData` for other QuickBooks entity properties.

The fix is **defensive programming** at its best - it makes the API more robust and developer-friendly by failing fast with clear error messages rather than allowing silent data corruption.
