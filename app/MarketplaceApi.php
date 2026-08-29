<?php

namespace App;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Native\Mobile\Facades\SecureStorage;

/**
 * Thin HTTP client the native screens use to talk to the marketplace
 * backend. Wraps the bearer token in SecureStorage and normalizes every
 * response into a simple ['ok' => bool, ...] shape so Blade views never
 * have to deal with HTTP client internals directly.
 *
 * @phpstan-type ApiResult array{ok: bool, status: int, data: array<string, mixed>, message: string|null}
 */
class MarketplaceApi
{
    public function token(): ?string
    {
        return SecureStorage::get('auth_token');
    }

    public function isLoggedIn(): bool
    {
        return filled($this->token());
    }

    public function currentUser(): ?array
    {
        $cached = SecureStorage::get('auth_user');

        return $cached ? json_decode($cached, true) : null;
    }

    public function isProvider(): bool
    {
        return ($this->currentUser()['role'] ?? null) === 'provider';
    }

    public function login(string $email, string $password): array
    {
        $result = $this->request('post', '/login', [
            'email' => $email,
            'password' => $password,
            'device_name' => 'nativephp-mobile',
        ], authenticated: false);

        return $result['ok'] ? $this->finalizeSession($result) : $result;
    }

    public function register(array $payload): array
    {
        $result = $this->request('post', '/register', $payload, authenticated: false);

        return $result['ok'] ? $this->finalizeSession($result) : $result;
    }

    /**
     * @param  array{ok: bool, status: int, data: array<string, mixed>, message: string|null}  $result
     * @return array{ok: bool, status: int, data: array<string, mixed>, message: string|null}
     */
    protected function finalizeSession(array $result): array
    {
        if ($this->rememberSession($result['data'])) {
            return $result;
        }

        // The API call succeeded, but the device could not persist the
        // session (e.g. an Android emulator without a lock-screen PIN
        // rejects hardware-backed Keystore writes). Without this check the
        // caller would navigate on to a screen that immediately bounces
        // back to a blank login form, which looks like the tap did nothing.
        return [
            'ok' => false,
            'status' => $result['status'],
            'data' => $result['data'],
            'message' => 'Signed in, but this device could not save your session. '.
                'If you are using an emulator, set a screen lock (PIN or pattern) on it and try again.',
        ];
    }

    public function logout(): void
    {
        $this->request('post', '/logout');

        SecureStorage::delete('auth_token');
        SecureStorage::delete('auth_user');
    }

    public function me(): array
    {
        $result = $this->request('get', '/me');

        if ($result['ok']) {
            SecureStorage::set('auth_user', json_encode($result['data']));
        }

        return $result;
    }

    public function updateProfile(array $data): array
    {
        return $this->request('put', '/profile', $data);
    }

    public function createServiceRequest(array $data): array
    {
        return $this->request('post', '/service-requests', $data);
    }

    public function showServiceRequest(int $requestId): array
    {
        return $this->request('get', "/service-requests/{$requestId}");
    }

    public function nearbyProviders(int $requestId): array
    {
        return $this->request('get', "/service-requests/{$requestId}/nearby-providers");
    }

    public function updateMyLocation(int $requestId, float $latitude, float $longitude): array
    {
        return $this->request('post', "/service-requests/{$requestId}/location", [
            'latitude' => $latitude,
            'longitude' => $longitude,
        ]);
    }

    public function acceptOffer(int $offerId): array
    {
        return $this->request('post', "/offers/{$offerId}/accept");
    }

    public function track(int $requestId): array
    {
        return $this->request('get', "/service-requests/{$requestId}/track");
    }

    public function messages(int $requestId): array
    {
        return $this->request('get', "/service-requests/{$requestId}/messages");
    }

    public function sendMessage(int $requestId, string $body): array
    {
        return $this->request('post', "/service-requests/{$requestId}/messages", ['body' => $body]);
    }

    public function providerRequests(): array
    {
        return $this->request('get', '/provider/requests');
    }

    public function providerOffers(): array
    {
        return $this->request('get', '/provider/offers');
    }

    public function submitOffer(int $requestId, array $data): array
    {
        return $this->request('post', "/service-requests/{$requestId}/offers", $data);
    }

    public function pushProviderLocation(float $latitude, float $longitude): array
    {
        return $this->request('post', '/provider/location', [
            'latitude' => $latitude,
            'longitude' => $longitude,
        ]);
    }

    protected function rememberSession(array $data): bool
    {
        $tokenSaved = SecureStorage::set('auth_token', $data['token']);
        $userSaved = SecureStorage::set('auth_user', json_encode($data['user']));

        return $tokenSaved && $userSaved && $this->token() === $data['token'];
    }

    protected function client(bool $authenticated): PendingRequest
    {
        $client = Http::baseUrl(rtrim(config('services.marketplace.url'), '/').'/api/v1')->acceptJson();

        if ($authenticated && $this->token()) {
            $client = $client->withToken($this->token());
        }

        return $client;
    }

    /**
     * @return array{ok: bool, status: int, data: array<string, mixed>, message: string|null}
     */
    protected function request(string $method, string $path, array $data = [], bool $authenticated = true): array
    {
        $response = $this->client($authenticated)->{$method}($path, $data);

        return [
            'ok' => $response->successful(),
            'status' => $response->status(),
            'data' => (array) $response->json(),
            'message' => $response->successful() ? null : ($response->json('message') ?? 'Something went wrong.'),
        ];
    }
}
