<?php
declare(strict_types=1);

namespace App\Routes;

use App\Services\AuthService;
use GuzzleHttp\Client;

class AuthRoutes
{
    public static function handle(string $method, string $uri): void
    {
        if ($uri === '/api/auth/login' && $method === 'GET') {
            self::login();
            return;
        }

        if ($uri === '/api/auth/callback' && $method === 'GET') {
            self::callback();
            return;
        }

        if ($uri === '/api/auth/retrieveToken' && $method === 'POST') {
            self::retrieveToken();
            return;
        }

        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['message' => 'Auth route not found']);
    }

    private static function login(): void
    {
        $clientId    = $_ENV['CLIENT_ID'] ?? '';
        $redirectUri = $_ENV['REDIRECT_URI'] ?? '';
        $environment = $_ENV['ENVIRONMENT'] ?? 'production';

        if (!$clientId || !$redirectUri) {
            http_response_code(500);
            echo 'CLIENT_ID or REDIRECT_URI not set in environment.';
            return;
        }

        $authBaseUrl = 'https://appcenter.intuit.com/connect/oauth2';

        $scope = implode(' ', [
            'app-foundations.custom-field-definitions.read',
            'app-foundations.custom-field-definitions',
            'com.intuit.quickbooks.accounting',
            'openid',
            'profile',
            'email',
        ]);

        $query = http_build_query([
            'client_id'     => $clientId,
            'response_type' => 'code',
            'scope'         => $scope,
            'redirect_uri'  => $redirectUri,
            'state'         => bin2hex(random_bytes(8)),
        ]);

        header('Location: ' . $authBaseUrl . '?' . $query);
        exit;
    }

    private static function callback(): void
    {
        $code    = $_GET['code'] ?? null;
        $realmId = $_GET['realmId'] ?? null;

        if (!$code) {
            http_response_code(400);
            echo 'Missing authorisation code.';
            return;
        }

        $clientId     = $_ENV['CLIENT_ID'] ?? '';
        $clientSecret = $_ENV['CLIENT_SECRET'] ?? '';
        $redirectUri  = $_ENV['REDIRECT_URI'] ?? '';

        if (!$clientId || !$clientSecret) {
            http_response_code(500);
            echo 'CLIENT_ID or CLIENT_SECRET not configured.';
            return;
        }

        $tokenUrl = 'https://oauth.platform.intuit.com/oauth2/v1/tokens/bearer';

        $client = new Client();

        try {
            $response = $client->post($tokenUrl, [
                'auth' => [$clientId, $clientSecret],
                'form_params' => [
                    'grant_type'   => 'authorization_code',
                    'code'         => $code,
                    'redirect_uri' => $redirectUri,
                ],
            ]);

            $body      = json_decode((string) $response->getBody(), true);
            $expiresIn = (int) ($body['expires_in'] ?? 3600);
            $expiresAt = time() + $expiresIn;

            AuthService::setToken([
                'access_token'  => $body['access_token'] ?? null,
                'refresh_token' => $body['refresh_token'] ?? null,
                'expires_at'    => $expiresAt,
                'realm_id'      => $realmId,
                'raw'           => $body,
            ]);

            header('Location: /');
        } catch (\Throwable $e) {
            http_response_code(500);
            echo 'OAuth callback error: ' . $e->getMessage();
        }
    }

    private static function retrieveToken(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $token = AuthService::getToken();

        if (!$token) {
            http_response_code(401);
            echo json_encode(['message' => 'Not authenticated']);
            return;
        }

        echo json_encode(['token' => $token]);
    }
}
