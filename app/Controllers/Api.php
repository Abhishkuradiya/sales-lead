<?php

namespace App\Controllers;

use Config\Api as ApiConfig;
use CodeIgniter\HTTP\ResponseInterface;

class Api extends BaseController
{
    private ApiConfig $apiConfig;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->apiConfig = config(ApiConfig::class);
    }

    public function publicSettings()
    {
        return $this->proxy('GET', $this->apiConfig->publicSettingsPath);
    }

    public function login()
    {
        $payload = $this->request->getJSON(true) ?? [];
        $response = $this->requestApi('POST', $this->apiConfig->loginPath, $payload);

        if ($response['statusCode'] >= 200 && $response['statusCode'] < 300 && is_array($response['body'])) {
            $body = $response['body'];
            if (($body['status'] ?? '') === 'success' && !empty($body['token'])) {
                $this->storeAuthenticatedSession($body['token'], $body['user'] ?? []);
            } elseif (!empty($body['otp_session'])) {
                session()->set('otp_session', $body['otp_session']);
            }
        }

        return $this->safeResponse($response);
    }

    public function verifyOtp()
    {
        $payload = $this->request->getJSON(true) ?? [];
        $payload['otp_session'] = session('otp_session') ?? ($payload['otp_session'] ?? '');
        $response = $this->requestApi('POST', $this->apiConfig->verifyOtpPath, $payload);

        if ($response['statusCode'] >= 200 && $response['statusCode'] < 300 && is_array($response['body'])) {
            $body = $response['body'];
            if (($body['status'] ?? '') === 'success' && !empty($body['token'])) {
                $this->storeAuthenticatedSession($body['token'], $body['user'] ?? []);
                session()->remove('otp_session');
            }
        }

        return $this->safeResponse($response);
    }

    public function me()
    {
        $token = session('api_token');
        if (!$token) {
            return $this->response->setStatusCode(401)->setJSON(['status' => 'error', 'message' => 'Not authenticated.']);
        }

        return $this->safeResponse($this->requestApi('GET', $this->apiConfig->mePath, [], $token));
    }

    public function thirdPartyDirectory()
    {
        $token = session('api_token');
        if (!$token) {
            return $this->response->setStatusCode(401)->setJSON(['status' => 'error', 'message' => 'Not authenticated.']);
        }

        return $this->safeResponse($this->requestApi('GET', $this->apiConfig->thirdPartyDirectoryPath, [], $token));
    }

    public function logout()
    {
        $token = session('api_token');
        if ($token) {
            $this->requestApi('POST', '/api/auth/logout', [], $token);
        }
        session()->destroy();
        return $this->response->setJSON(['status' => 'success']);
    }

    public function establishSession()
    {
        $payload = $this->request->getJSON(true) ?? [];
        $token = trim((string) ($payload['token'] ?? ''));
        if ($token === '') {
            return $this->response->setStatusCode(422)->setJSON(['status' => 'error', 'message' => 'Bearer token is required.']);
        }

        $this->saveAuthenticatedSession($token, is_array($payload['user'] ?? null) ? $payload['user'] : []);
        return $this->response->setJSON(['status' => 'success']);
    }

    private function proxy(string $method, string $path)
    {
        return $this->safeResponse($this->requestApi($method, $path));
    }

    private function storeAuthenticatedSession(string $token, array $user = []): void
    {
        $this->saveAuthenticatedSession($token, $user);

        $profile = $this->requestApi('GET', $this->apiConfig->mePath, [], $token);
        if ($profile['statusCode'] >= 200 && $profile['statusCode'] < 300 && is_array($profile['body']['user'] ?? null)) {
            session()->set('api_user', $profile['body']['user']);
        }
    }

    private function saveAuthenticatedSession(string $token, array $user = []): void
    {
        session()->regenerate(true);
        session()->set('api_token', $token);
        session()->set('api_user', $user);
    }

    private function requestApi(string $method, string $path, array $payload = [], ?string $token = null): array
    {
        $curl = curl_init($this->apiConfig->baseUrl . $path);
        $headers = ['Accept: application/json', 'Content-Type: application/json'];
        if ($token) {
            $headers[] = 'Authorization: Bearer ' . $token;
        }

        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => $this->apiConfig->timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        if ($method !== 'GET' && $payload !== []) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payload));
        }

        $raw = curl_exec($curl);
        $error = curl_error($curl);
        $statusCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($raw === false || $error) {
            return ['statusCode' => 502, 'body' => ['status' => 'error', 'message' => 'Authentication API is unavailable at ' . $this->apiConfig->baseUrl . $path . '. Check the API server and try again.']];
        }

        $body = json_decode($raw, true);
        return ['statusCode' => $statusCode ?: 502, 'body' => is_array($body) ? $body : ['status' => 'error', 'message' => 'Invalid response from authentication service.']];
    }

    private function safeResponse(array $result)
    {
        $body = $result['body'];
        if (is_array($body)) {
            unset($body['token']);
        }
        return $this->response->setStatusCode($result['statusCode'])->setJSON($body);
    }
}
