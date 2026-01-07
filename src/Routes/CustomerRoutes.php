<?php
declare(strict_types=1);

namespace App\Routes;

use App\Services\AuthService;
use App\Services\CustomerService;

class CustomerRoutes
{
    public static function handle(string $method, string $uri): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!AuthService::isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['message' => 'Not authenticated']);
            return;
        }

        $base = '/api/quickbook/customers';

        // GET /api/quickbook/customers/:id
        if (preg_match('#^' . preg_quote($base, '#') . '/([^/]+)$#', $uri, $matches) && $method === 'GET') {
            self::get($matches[1]);
            return;
        }

        // POST /api/quickbook/customers
        if ($uri === $base && $method === 'POST') {
            self::create();
            return;
        }

        // PUT /api/quickbook/customers/:id
        if (preg_match('#^' . preg_quote($base, '#') . '/([^/]+)$#', $uri, $matches) && $method === 'PUT') {
            self::update($matches[1]);
            return;
        }

        http_response_code(404);
        echo json_encode(['message' => 'Customer route not found']);
    }

    private static function get(string $customerId): void
    {
        try {
            $data = CustomerService::getCustomer($customerId);
            echo json_encode($data);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'message' => 'Failed to fetch customer',
                'error'   => $e->getMessage(),
            ]);
        }
    }

    private static function create(): void
    {
        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        $displayName = (string) ($body['displayName'] ?? '');
        $customFields = (array) ($body['customFields'] ?? []);
        $additionalData = (array) ($body['additionalData'] ?? []);

        if (!$displayName) {
            http_response_code(400);
            echo json_encode(['message' => 'displayName is required']);
            return;
        }

        try {
            $data = CustomerService::createCustomer(
                $displayName,
                $customFields,
                $additionalData
            );

            echo json_encode($data);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'message' => 'Failed to create customer',
                'error'   => $e->getMessage(),
            ]);
        }
    }

    private static function update(string $customerId): void
    {
        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        $customFields = (array) ($body['customFields'] ?? []);
        $additionalData = (array) ($body['additionalData'] ?? []);

        try {
            $data = CustomerService::updateCustomer(
                $customerId,
                $customFields,
                $additionalData
            );

            echo json_encode($data);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'message' => 'Failed to update customer',
                'error'   => $e->getMessage(),
            ]);
        }
    }
}
