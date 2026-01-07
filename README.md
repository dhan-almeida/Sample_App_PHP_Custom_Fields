# QuickBooks Custom Fields PHP Sample

This is a comprehensive PHP sample app that demonstrates how to:

- Authenticate with QuickBooks Online using OAuth 2.0
- Manage Custom Field Definitions via the App Foundations GraphQL API
- Apply custom fields to various QuickBooks entities (Invoices, Customers, Items)
- Validate custom field data types automatically
- Use both STRING and NUMBER custom field types correctly

## Prerequisites

- PHP 8.1 or later
- Composer
- A QuickBooks Online app with OAuth 2.0
- A QBO sandbox company

## Setup

1. Clone or unzip this project.
2. Copy `.env.example` to `.env` and fill in your values:

   - `CLIENT_ID`
   - `CLIENT_SECRET`
   - `REDIRECT_URI`
   - `ENVIRONMENT` (usually `production` for QBO)
   - `APP_FOUNDATIONS_GRAPHQL_URL` (default: `https://qb.api.intuit.com/graphql`)
   - `QBO_BASE_URL` (default: `https://quickbooks.api.intuit.com`)

3. Install dependencies:

   ```bash
   composer install
   ```

4. Start the server from the project root:

   ```bash
   php -S localhost:3000 -t public
   ```

5. Open `http://localhost:3000` in your browser.

## Main Endpoints

### Authentication
- `GET /api/auth/login` - Starts the OAuth flow
- `GET /api/auth/callback` - Handles the OAuth redirect and stores tokens
- `POST /api/auth/retrieveToken` - Returns the current token (debugging)

### Custom Field Definitions (GraphQL)
- `GET /api/quickbook/custom-fields` - Lists all custom field definitions
- `POST /api/quickbook/custom-fields` - Creates a custom field definition
- `PUT /api/quickbook/custom-fields/:id` - Updates a custom field definition
- `DELETE /api/quickbook/custom-fields/:id` - Deactivates a custom field definition
- `POST /api/quickbook/custom-fields/validate` - Validates custom field values against definitions

### Customers (REST API)
- `GET /api/quickbook/customers/:id` - Get a customer with custom fields
- `POST /api/quickbook/customers` - Create a customer with custom fields
- `PUT /api/quickbook/customers/:id` - Update a customer with custom fields

### Items (REST API)
- `GET /api/quickbook/items/:id` - Get an item with custom fields
- `POST /api/quickbook/items` - Create an item with custom fields
- `PUT /api/quickbook/items/:id` - Update an item with custom fields

### Invoices (REST API)
- `POST /api/quickbook/invoices` - Create a general invoice with custom fields
- `POST /api/quickbook/invoices/cost-of-fuel` - Create an invoice with Cost of Fuel custom field (specific example)

## Features

### Custom Field Type Support
The app automatically handles different custom field types:
- **STRING**: Text values (uses `StringValue` in the API)
- **NUMBER**: Numeric values (uses `NumberValue` in the API)
- **DROPDOWN**: Dropdown selections (uses `StringValue` in the API)

### Automatic Validation
All services automatically validate custom field values against their definitions:
- Checks if the field is active
- Validates data type matches (NUMBER vs STRING)
- Validates dropdown options if applicable
- Auto-corrects type mismatches when possible

### Entity Support
Custom fields can be applied to:
- **Invoices**: Transaction-level custom fields
- **Customers**: Customer-specific custom fields
- **Items**: Product/service-specific custom fields

## Usage Notes

This app is for demonstration and manual testing only, not for production use.

The validation service caches custom field definitions for performance. The cache is automatically cleared when validating to ensure fresh data.
