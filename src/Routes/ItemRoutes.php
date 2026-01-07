<?php
declare(strict_types=1);

namespace App\Routes;

use App\Services\AuthService;
use App\Services\ItemService;

class ItemRoutes
{
    public static function handle(string $method, string $uri): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!AuthService::isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['message' => 'Not authenticated']);
            return;
        }

        $base = '/api/quickbook/items';

        // GET /api/quickbook/items/:id
        if (preg_match('#^' . preg_quote($base, '#') . '/([^/]+)$#', $uri, $matches) && $method === 'GET') {
            self::get($matches[1]);
            return;
        }

        // POST /api/quickbook/items
        if ($uri === $base && $method === 'POST') {
            self::create();
            return;
        }

        // PUT /api/quickbook/items/:id
        if (preg_match('#^' . preg_quote($base, '#') . '/([^/]+)$#', $uri, $matches) && $method === 'PUT') {
            self::update($matches[1]);
            return;
        }

        http_response_code(404);
        echo json_encode(['message' => 'Item route not found']);
    }

    private static function get(string $itemId): void
    {
        try {
            $data = ItemService::getItem($itemId);
            echo json_encode($data);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'message' => 'Failed to fetch item',
                'error'   => $e->getMessage(),
            ]);
        }
    }

    private static function create(): void
    {
        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        $name = (string) ($body['name'] ?? '');
        $type = (string) ($body['type'] ?? 'Service');
        $customFields = (array) ($body['customFields'] ?? []);
        $additionalData = (array) ($body['additionalData'] ?? []);

        if (!$name) {
            http_response_code(400);
            echo json_encode(['message' => 'name is required']);
            return;
        }

        try {
            $data = ItemService::createItem(
                $name,
                $type,
                $customFields,
                $additionalData
            );

            echo json_encode($data);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'message' => 'Failed to create item',
                'error'   => $e->getMessage(),
            ]);
        }
    }

    private static function update(string $itemId): void
    {
        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        $customFields = (array) ($body['customFields'] ?? []);
        $additionalData = (array) ($body['additionalData'] ?? []);

        try {
            $data = ItemService::updateItem(
                $itemId,
                $customFields,
                $additionalData
            );

            echo json_encode($data);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'message' => 'Failed to update item',
                'error'   => $e->getMessage(),
            ]);
        }
    }
}
