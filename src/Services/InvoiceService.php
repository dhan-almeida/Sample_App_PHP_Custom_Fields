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

    public static function createInvoiceWithCostOfFuel(
        string $definitionId,
        string $customerId,
        string $itemId,
        float $fuelCost
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
                [
                    'DefinitionId' => $definitionId,
                    'StringValue'  => (string) $fuelCost,
                ],
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
}
