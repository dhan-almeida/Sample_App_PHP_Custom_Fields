<?php
declare(strict_types=1);

namespace App\Services;

use App\GraphQL\CustomFields\GetAllCustomFields;
use App\GraphQL\CustomFields\CreateCustomField;
use App\GraphQL\CustomFields\UpdateCustomField;
use GuzzleHttp\Client;

class CustomFieldsService
{
    private static function getGraphQLClient(string $token, string $realmId): Client
    {
        $baseUrl = $_ENV['APP_FOUNDATIONS_GRAPHQL_URL'] ?? 'https://qb.api.intuit.com/graphql';

        return new Client([
            'base_uri' => $baseUrl,
            'headers'  => [
                'Authorization'   => 'Bearer ' . $token,
                'intuit-realm-id' => $realmId,
                'Content-Type'    => 'application/json',
            ],
        ]);
    }

    private static function makeRequest(Client $client, string $query, array $variables = []): array
    {
        $response = $client->post('', [
            'json' => [
                'query'     => $query,
                'variables' => $variables,
            ],
        ]);

        $body = json_decode((string) $response->getBody(), true);

        if (isset($body['errors'])) {
            throw new \RuntimeException('GraphQL error: ' . json_encode($body['errors']));
        }

        return $body['data'] ?? [];
    }

    private static function ensureClient(): Client
    {
        if (!AuthService::isAuthenticated()) {
            throw new \RuntimeException('Not authenticated');
        }

        $token   = AuthService::getAccessToken();
        $realmId = AuthService::getRealmId() ?? '';

        return self::getGraphQLClient((string) $token, $realmId);
    }

    public static function getCustomFields(): array
    {
        $client = self::ensureClient();
        return self::makeRequest($client, GetAllCustomFields::QUERY, []);
    }

    public static function createCustomField(array $body): array
    {
        $client    = self::ensureClient();
        $variables = CreateCustomField::buildVariables($body);

        return self::makeRequest($client, CreateCustomField::MUTATION, $variables);
    }

    public static function updateCustomField(string $id, array $body): array
    {
        $client    = self::ensureClient();
        $variables = UpdateCustomField::buildVariables($id, $body);

        return self::makeRequest($client, UpdateCustomField::MUTATION, $variables);
    }
}
