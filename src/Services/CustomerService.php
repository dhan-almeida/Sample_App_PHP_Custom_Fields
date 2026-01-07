<?php
declare(strict_types=1);

namespace App\Services;

use GuzzleHttp\Client;

class CustomerService
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
                $field['NumberValue'] = is_numeric($value) ? (float) $value : 0.0;
                break;

            case 'STRING':
            case 'DROPDOWN':
            default:
                $field['StringValue'] = (string) $value;
                break;
        }

        return $field;
    }

    /**
     * Create a customer with custom fields
     *
     * @param string $displayName The customer display name
     * @param array $customFields Array of custom fields, each with 'definitionId', 'value', and 'type'
     * @param array $additionalData Optional additional customer data (GivenName, FamilyName, etc.)
     * @return array The created customer data
     */
    public static function createCustomer(
        string $displayName,
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

        // Prevent CustomField in additionalData from overwriting the customFields parameter
        if (isset($additionalData['CustomField'])) {
            throw new \InvalidArgumentException(
                'CustomField should not be in additionalData. Use the customFields parameter instead.'
            );
        }

        // Prevent core fields from being overwritten via additionalData
        $protectedFields = ['DisplayName'];
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
            '/v3/company/%s/customer?minorversion=75&include=enhancedAllCustomFields',
            urlencode($realmId)
        );

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

        // Build customer body
        $body = [
            'DisplayName' => $displayName,
        ];

        // Add custom fields if any
        if (!empty($customFieldsPayload)) {
            $body['CustomField'] = $customFieldsPayload;
        }

        // Merge additional data (GivenName, FamilyName, CompanyName, PrimaryEmailAddr, etc.)
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

    /**
     * Update a customer with custom fields
     *
     * @param string $customerId The customer ID to update
     * @param array $customFields Array of custom fields, each with 'definitionId', 'value', and 'type'
     * @param array $additionalData Optional additional customer data to update
     * @return array The updated customer data
     */
    public static function updateCustomer(
        string $customerId,
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

        $client = self::getClient((string) $token);

        // First, fetch the existing customer to get the SyncToken
        $getPath = sprintf(
            '/v3/company/%s/customer/%s?minorversion=75',
            urlencode($realmId),
            urlencode($customerId)
        );

        $getResponse = $client->get($getPath);
        $existingData = json_decode((string) $getResponse->getBody(), true);

        if (isset($existingData['Fault'])) {
            throw new \RuntimeException('QBO error fetching customer: ' . json_encode($existingData['Fault']));
        }

        $customer = $existingData['Customer'] ?? [];
        $syncToken = $customer['SyncToken'] ?? null;

        if ($syncToken === null) {
            throw new \RuntimeException('Could not retrieve customer SyncToken');
        }

        // Prevent CustomField in additionalData from overwriting the customFields parameter
        if (isset($additionalData['CustomField'])) {
            throw new \InvalidArgumentException(
                'CustomField should not be in additionalData. Use the customFields parameter instead.'
            );
        }

        // Prevent core fields from being overwritten via additionalData
        $protectedFields = ['Id', 'SyncToken'];
        foreach ($protectedFields as $field) {
            if (isset($additionalData[$field])) {
                throw new \InvalidArgumentException(
                    sprintf(
                        '%s should not be in additionalData. This field is managed internally.',
                        $field
                    )
                );
            }
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

        // Build update body
        $body = [
            'Id' => $customerId,
            'SyncToken' => $syncToken,
        ];

        // Add custom fields if any
        if (!empty($customFieldsPayload)) {
            $body['CustomField'] = $customFieldsPayload;
        }

        // Merge additional data
        $body = array_merge($body, $additionalData);

        // Update the customer
        $updatePath = sprintf(
            '/v3/company/%s/customer?minorversion=75&include=enhancedAllCustomFields',
            urlencode($realmId)
        );

        $response = $client->post($updatePath, [
            'json' => $body,
        ]);

        $data = json_decode((string) $response->getBody(), true);

        if (isset($data['Fault'])) {
            throw new \RuntimeException('QBO error: ' . json_encode($data['Fault']));
        }

        return $data;
    }

    /**
     * Get a customer by ID
     *
     * @param string $customerId The customer ID
     * @return array The customer data
     */
    public static function getCustomer(string $customerId): array
    {
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
            '/v3/company/%s/customer/%s?minorversion=75&include=enhancedAllCustomFields',
            urlencode($realmId),
            urlencode($customerId)
        );

        $response = $client->get($path);
        $data = json_decode((string) $response->getBody(), true);

        if (isset($data['Fault'])) {
            throw new \RuntimeException('QBO error: ' . json_encode($data['Fault']));
        }

        return $data;
    }
}
