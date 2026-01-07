<?php
declare(strict_types=1);

namespace App\Routes;

use App\Services\AuthService;
use App\Services\CustomFieldsService;
use App\Services\CustomFieldValidationService;

class CustomFieldsRoutes
{
    public static function handle(string $method, string $uri): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!AuthService::isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['message' => 'Not authenticated']);
            return;
        }

        $base = '/api/quickbook/custom-fields';

        if ($uri === $base && $method === 'GET') {
            self::getAll();
            return;
        }

        if ($uri === $base && $method === 'POST') {
            self::create();
            return;
        }

        if ($uri === $base . '/validate' && $method === 'POST') {
            self::validate();
            return;
        }

        if (str_starts_with($uri, $base . '/')) {
            $id = substr($uri, strlen($base) + 1);

            if ($method === 'PUT') {
                self::update($id);
                return;
            }

            if ($method === 'DELETE') {
                self::delete($id);
                return;
            }
        }

        http_response_code(404);
        echo json_encode(['message' => 'Custom fields route not found']);
    }

    private static function getAll(): void
    {
        try {
            $data = CustomFieldsService::getCustomFields();
            echo json_encode($data);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'message' => 'Failed to fetch custom fields',
                'error'   => $e->getMessage(),
            ]);
        }
    }

    private static function create(): void
    {
        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        try {
            $data = CustomFieldsService::createCustomField($body);
            echo json_encode($data);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'message' => 'Failed to create custom field',
                'error'   => $e->getMessage(),
            ]);
        }
    }

    private static function update(string $id): void
    {
        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        try {
            $data = CustomFieldsService::updateCustomField($id, $body);
            echo json_encode($data);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'message' => 'Failed to update custom field',
                'error'   => $e->getMessage(),
            ]);
        }
    }

    private static function delete(string $id): void
    {
        try {
            $data = CustomFieldsService::updateCustomField($id, ['active' => false]);
            echo json_encode($data);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'message' => 'Failed to delete custom field',
                'error'   => $e->getMessage(),
            ]);
        }
    }

    private static function validate(): void
    {
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $customFields = (array) ($body['customFields'] ?? []);

        if (empty($customFields)) {
            http_response_code(400);
            echo json_encode(['message' => 'customFields array is required']);
            return;
        }

        try {
            // Clear cache to get fresh definitions
            CustomFieldValidationService::clearCache();
            
            $result = CustomFieldValidationService::validateFields($customFields);
            
            http_response_code($result['valid'] ? 200 : 400);
            echo json_encode($result);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'message' => 'Failed to validate custom fields',
                'error'   => $e->getMessage(),
            ]);
        }
    }
}
