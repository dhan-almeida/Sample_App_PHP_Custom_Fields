<?php
declare(strict_types=1);

use App\Routes\AuthRoutes;
use App\Routes\CustomFieldsRoutes;
use App\Routes\CustomerRoutes;
use App\Routes\InvoiceRoutes;
use App\Routes\ItemRoutes;

require __DIR__ . '/../vendor/autoload.php';

$dotenvPath = __DIR__ . '/..';
if (file_exists($dotenvPath . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable($dotenvPath);
    $dotenv->load();
}

$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Serve root page
if ($uri === '/') {
    $pagePath = __DIR__ . '/../pages/index.html';
    if (file_exists($pagePath)) {
        header('Content-Type: text/html; charset=utf-8');
        readfile($pagePath);
        exit;
    }
    http_response_code(404);
    echo 'index.html not found';
    exit;
}

// Static assets
if (str_starts_with($uri, '/pages/')) {
    $filePath = __DIR__ . '/..' . $uri;
    if (file_exists($filePath)) {
        $ext = pathinfo($filePath, PATHINFO_EXTENSION);
        if ($ext === 'css') {
            header('Content-Type: text/css; charset=utf-8');
        } elseif ($ext === 'js') {
            header('Content-Type: application/javascript; charset=utf-8');
        } else {
            header('Content-Type: text/plain; charset=utf-8');
        }
        readfile($filePath);
        exit;
    }
    http_response_code(404);
    echo 'File not found';
    exit;
}

// Auth routes
if (str_starts_with($uri, '/api/auth')) {
    AuthRoutes::handle($method, $uri);
    exit;
}

// Custom field routes
if (str_starts_with($uri, '/api/quickbook/custom-fields')) {
    CustomFieldsRoutes::handle($method, $uri);
    exit;
}

// Customer routes
if (str_starts_with($uri, '/api/quickbook/customers')) {
    CustomerRoutes::handle($method, $uri);
    exit;
}

// Item routes
if (str_starts_with($uri, '/api/quickbook/items')) {
    ItemRoutes::handle($method, $uri);
    exit;
}

// Invoice routes
if (str_starts_with($uri, '/api/quickbook/invoices')) {
    InvoiceRoutes::handle($method, $uri);
    exit;
}

// Fallback
http_response_code(404);
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['message' => 'Not found']);
