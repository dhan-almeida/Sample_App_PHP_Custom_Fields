# QuickBooks Custom Fields PHP Sample

This is a small standalone PHP sample app that shows how to:

- Authenticate with QuickBooks Online using OAuth 2.0.
- Manage Custom Field Definitions via the App Foundations GraphQL API.
- Apply a specific custom field (Cost of Fuel) on an invoice using the Accounting REST API.

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

- `GET /`  
  Serves the sample front end.

- `GET /api/auth/login`  
  Starts the OAuth flow.

- `GET /api/auth/callback`  
  Handles the OAuth redirect and stores tokens in memory.

- `POST /api/auth/retrieveToken`  
  Returns the current token (for debugging).

- `GET /api/quickbook/custom-fields`  
  Lists custom field definitions via GraphQL.

- `POST /api/quickbook/custom-fields`  
  Creates a custom field definition.

- `PUT /api/quickbook/custom-fields/:id`  
  Updates a custom field definition.

- `POST /api/quickbook/invoices/cost-of-fuel`  
  Creates an invoice that includes a Cost of Fuel custom field.

This app is for demonstration and manual testing only, not for production use.
