<?php
declare(strict_types=1);

namespace App\Routes;

use App\Services\AuthService;
use App\Services\InvoiceService;

class InvoiceRoutes
{
    public static function handle(string $method, string $uri): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $base = '/api/quickbook/invoices';

        if ($uri === $base && $method === 'POST') {
            self::create();
            return;
        }

        if ($uri === $base . '/cost-of-fuel' && $method === 'POST') {
            self::createWithCostOfFuel();
            return;
        }

        http_response_code(404);
        echo json_encode(['message' => 'Invoice route not found']);
    }

    private static function create(): void
    {
        if (!AuthService::isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['message' => 'Not authenticated']);
            return;
        }

        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        $customerId = (string) ($body['customerId'] ?? '');
        $lineItems = (array) ($body['lineItems'] ?? []);
        $customFields = (array) ($body['customFields'] ?? []);
        $additionalData = (array) ($body['additionalData'] ?? []);

        if (!$customerId) {
            http_response_code(400);
            echo json_encode(['message' => 'customerId is required']);
            return;
        }

        if (empty($lineItems)) {
            http_response_code(400);
            echo json_encode(['message' => 'At least one line item is required']);
            return;
        }

        try {
            $data = InvoiceService::createInvoice(
                $customerId,
                $lineItems,
                $customFields,
                $additionalData
            );

            echo json_encode($data);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'message' => 'Failed to create invoice',
                'error'   => $e->getMessage(),
            ]);
        }
    }

    private static function createWithCostOfFuel(): void
    {
        if (!AuthService::isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['message' => 'Not authenticated']);
            return;
        }

        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        $definitionId = (string) ($body['definitionId'] ?? '');
        $customerId   = (string) ($body['customerId'] ?? '');
        $itemId       = (string) ($body['itemId'] ?? '');
        $fuelCost     = (float)  ($body['fuelCost'] ?? 0);
        $fieldType    = (string) ($body['fieldType'] ?? 'NUMBER'); // Default to NUMBER for fuel cost

        if (!$definitionId || !$customerId || !$itemId) {
            http_response_code(400);
            echo json_encode([
                'message' => 'definitionId, customerId and itemId are required',
            ]);
            return;
        }

        try {
            $data = InvoiceService::createInvoiceWithCostOfFuel(
                $definitionId,
                $customerId,
                $itemId,
                $fuelCost,
                $fieldType
            );

            echo json_encode($data);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'message' => 'Failed to create invoice with cost of fuel custom field',
                'error'   => $e->getMessage(),
            ]);
        }
    }
}
