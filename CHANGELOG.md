# Changelog

All notable changes to the QuickBooks Custom Fields PHP application.

## [2.0.0] - 2026-01-07

### Added
- **NUMBER Custom Field Support**: Added `buildCustomFieldPayload()` method that correctly uses `NumberValue` for NUMBER fields
- **General Invoice Creation**: New `createInvoice()` method supporting multiple line items and custom fields
- **Customer Entity Support**: Complete CRUD operations for customers with custom fields
- **Item Entity Support**: Complete CRUD operations for items with custom fields
- **Custom Field Validation Service**: Comprehensive validation with auto-correction and type checking
- **Validation API Endpoint**: `POST /api/quickbook/custom-fields/validate` for pre-validation
- **Environment Setup Documentation**: Complete guides for `.env` configuration
- **ngrok Integration**: Full documentation and setup guides for local OAuth development
- **Port 80 Support**: Instructions for running on privileged port 80

### Fixed
- **Bug #1**: CustomField array overwrite via additionalData (silent data loss prevention)
- **Bug #2**: Core entity fields overwrite protection (9 protected fields across all services)
- **Bug #3**: Validation auto-correction now works (was skipping correction on type mismatch)
- **Bug #4**: Misleading null type messages in auto-correction logs
- **Bug #5**: Hardcoded line item amount in `createInvoiceWithCostOfFuel` (now uses parameter)
- **Bug #6**: Lingering reference after foreach loop (PHP best practice violation)
- **Bug #7**: Missing custom field definitions no longer pass validation silently
- **Bug #8**: Validation endpoint consistency with entity creation

### Security
- Added protection against CustomField overwrites
- Added protection against core field tampering (Line, CustomerRef, Id, SyncToken, etc.)
- Added validation for missing and inactive custom field definitions
- Implemented defensive programming with clear error messages

### Documentation
- **README.md**: Complete rewrite with comprehensive usage guide (22 KB)
- **SETUP_GUIDE.md**: Step-by-step setup instructions (13 KB)
- **NGROK_SETUP.md**: Complete ngrok integration guide (11 KB)
- **ENV_SETUP_QUICKREF.md**: Quick reference card for environment setup (4 KB)
- **BUGFIXES.md**: Consolidated all bug fix documentation
- **TESTING.md**: Complete test results and verification (21/21 tests passing)
- **CHANGELOG.md**: This file

### Changed
- `createInvoiceWithCostOfFuel()` now accepts `fieldType` parameter (defaults to 'NUMBER')
- All entity creation methods now validate custom fields before API calls
- All entity services now prevent field overwrites via additionalData
- Validation service now returns consistent results across all endpoints

### Removed
- Redundant documentation files (consolidated into comprehensive guides)
- Excessive emoji usage from documentation

---

## [1.0.0] - Initial Release

### Added
- Basic QuickBooks OAuth 2.0 authentication
- GraphQL API integration for custom field definitions
- REST API integration for invoice creation
- Simple custom field support (STRING only)
- Basic web UI

---

## Version History

| Version | Date | Status | Changes |
|---------|------|--------|---------|
| **2.0.0** | 2026-01-07 | ✅ Current | Major feature additions, 8 bug fixes, complete documentation |
| 1.0.0 | Initial | Deprecated | Basic implementation |

---

## Upgrade Guide

### From 1.0.0 to 2.0.0

**Breaking Changes**: None (fully backwards compatible)

**New Features Available**:
1. NUMBER custom fields (use `type: "NUMBER"` in customFields array)
2. General invoice creation endpoint (`POST /api/quickbook/invoices`)
3. Customer operations (`/api/quickbook/customers`)
4. Item operations (`/api/quickbook/items`)
5. Validation endpoint (`POST /api/quickbook/custom-fields/validate`)

**Recommended Actions**:
1. Review new documentation in README.md
2. Test custom field validation before entity creation
3. Use dedicated `customFields` parameter (don't put CustomField in additionalData)
4. Update any code that relied on hardcoded invoice amounts

---

## Future Roadmap

### Planned for 2.1.0
- Support for more entity types (Estimate, Bill, Vendor)
- Batch operations for multiple entities
- Custom field templates/presets
- PHPUnit test suite
- Docker containerization

### Under Consideration
- Advanced dropdown option management
- Field dependency validation
- Audit logging for custom field changes
- CI/CD pipeline
- API rate limiting

---

**Maintained by**: Development Team  
**License**: Educational/Sample Code  
**Repository**: sampleapp-customfields-php-full
