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

        if ($uri === $base . '/cost-of-fuel' && $method === 'POST') {
            self::createWithCostOfFuel();
            return;
        }

        http_response_code(404);
        echo json_encode(['message' => 'Invoice route not found']);
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
                $fuelCost
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
