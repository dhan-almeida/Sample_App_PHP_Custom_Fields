<?php
declare(strict_types=1);

namespace App\Services;

use GuzzleHttp\Client;

class InvoiceService
{
    private static function getClient(string $token): Client
    {
        $baseUrl = $_ENV['QBO_BASE_URL'] ?? 'https://quickbooks.api.intuit.com';

        return new Client([
            'base_uri' => $baseUrl,
            'headers'  => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ],
        ]);
    }

    /**
     * Build a custom field payload based on the field type
     *
     * @param string $definitionId The custom field definition ID (legacyIDV2 from GraphQL)
     * @param mixed $value The value to set
     * @param string $type The data type: 'STRING', 'NUMBER', or 'DROPDOWN'
     * @return array The custom field payload
     */
    private static function buildCustomFieldPayload(
        string $definitionId,
        $value,
        string $type = 'STRING'
    ): array {
        $field = ['DefinitionId' => $definitionId];

        switch (strtoupper($type)) {
            case 'NUMBER':
                // For numeric fields, use NumberValue
                $field['NumberValue'] = is_numeric($value) ? (float) $value : 0.0;
                break;

            case 'STRING':
            case 'DROPDOWN':
            default:
                // For string and dropdown fields, use StringValue
                $field['StringValue'] = (string) $value;
                break;
        }

        return $field;
    }

    public static function createInvoiceWithCostOfFuel(
        string $definitionId,
        string $customerId,
        string $itemId,
        float $fuelCost,
        string $fieldType = 'NUMBER'
    ): array {
        if (!AuthService::isAuthenticated()) {
            throw new \RuntimeException('Not authenticated');
        }

        $token   = AuthService::getAccessToken();
        $realmId = AuthService::getRealmId();

        if (!$token || !$realmId) {
            throw new \RuntimeException('Missing token or realmId');
        }

        $client = self::getClient((string) $token);

        $path = sprintf(
            '/v3/company/%s/invoice?minorversion=75&include=enhancedAllCustomFields',
            urlencode($realmId)
        );

        $body = [
            'Line' => [
                [
                    'Amount' => 100.00,
                    'DetailType' => 'SalesItemLineDetail',
                    'SalesItemLineDetail' => [
                        'ItemRef' => [
                            'value' => $itemId,
                        ],
                    ],
                ],
            ],
            'CustomerRef' => [
                'value' => $customerId,
            ],
            'CustomField' => [
                self::buildCustomFieldPayload($definitionId, $fuelCost, $fieldType),
            ],
        ];

        $response = $client->post($path, [
            'json' => $body,
        ]);

        $data = json_decode((string) $response->getBody(), true);

        if (isset($data['Fault'])) {
            throw new \RuntimeException('QBO error: ' . json_encode($data['Fault']));
        }

        return $data;
    }

    /**
     * Create a general invoice with multiple custom fields
     *
     * @param string $customerId The customer reference ID
     * @param array $lineItems Array of line items, each with 'itemId', 'amount', 'quantity' (optional), 'description' (optional)
     * @param array $customFields Array of custom fields, each with 'definitionId', 'value', and 'type' (STRING|NUMBER|DROPDOWN)
     * @param array $additionalData Optional additional invoice data (TxnDate, DueDate, etc.)
     * @return array The created invoice data
     */
    public static function createInvoice(
        string $customerId,
        array $lineItems,
        array $customFields = [],
        array $additionalData = []
    ): array {
        if (!AuthService::isAuthenticated()) {
            throw new \RuntimeException('Not authenticated');
        }

        $token   = AuthService::getAccessToken();
        $realmId = AuthService::getRealmId();

        if (!$token || !$realmId) {
            throw new \RuntimeException('Missing token or realmId');
        }

        if (empty($lineItems)) {
            throw new \InvalidArgumentException('At least one line item is required');
        }

        // Prevent CustomField in additionalData from overwriting the customFields parameter
        if (isset($additionalData['CustomField'])) {
            throw new \InvalidArgumentException(
                'CustomField should not be in additionalData. Use the customFields parameter instead.'
            );
        }

        // Prevent core fields from being overwritten via additionalData
        $protectedFields = ['Line', 'CustomerRef'];
        foreach ($protectedFields as $field) {
            if (isset($additionalData[$field])) {
                throw new \InvalidArgumentException(
                    sprintf(
                        '%s should not be in additionalData. Use the method parameters instead.',
                        $field
                    )
                );
            }
        }

        $client = self::getClient((string) $token);

        $path = sprintf(
            '/v3/company/%s/invoice?minorversion=75&include=enhancedAllCustomFields',
            urlencode($realmId)
        );

        // Build line items
        $lines = [];
        foreach ($lineItems as $item) {
            $line = [
                'Amount' => (float) ($item['amount'] ?? 0.0),
                'DetailType' => 'SalesItemLineDetail',
                'SalesItemLineDetail' => [
                    'ItemRef' => [
                        'value' => (string) ($item['itemId'] ?? ''),
                    ],
                ],
            ];

            // Add optional quantity
            if (isset($item['quantity'])) {
                $line['SalesItemLineDetail']['Qty'] = (float) $item['quantity'];
            }

            // Add optional description
            if (isset($item['description'])) {
                $line['Description'] = (string) $item['description'];
            }

            $lines[] = $line;
        }

        // Validate and auto-correct custom field types
        $validation = CustomFieldValidationService::validateAndCorrectTypes($customFields);
        if (!$validation['valid']) {
            throw new \InvalidArgumentException(
                'Custom field validation failed: ' . implode('; ', $validation['errors'])
            );
        }

        // Build custom fields
        $customFieldsPayload = [];
        foreach ($customFields as $field) {
            $definitionId = (string) ($field['definitionId'] ?? '');
            $value = $field['value'] ?? '';
            $type = (string) ($field['type'] ?? 'STRING');

            if ($definitionId) {
                $customFieldsPayload[] = self::buildCustomFieldPayload($definitionId, $value, $type);
            }
        }

        // Build invoice body
        $body = [
            'Line' => $lines,
            'CustomerRef' => [
                'value' => $customerId,
            ],
        ];

        // Add custom fields if any
        if (!empty($customFieldsPayload)) {
            $body['CustomField'] = $customFieldsPayload;
        }

        // Merge additional data (TxnDate, DueDate, DocNumber, etc.)
        $body = array_merge($body, $additionalData);

        $response = $client->post($path, [
            'json' => $body,
        ]);

        $data = json_decode((string) $response->getBody(), true);

        if (isset($data['Fault'])) {
            throw new \RuntimeException('QBO error: ' . json_encode($data['Fault']));
        }

        return $data;
    }
}
