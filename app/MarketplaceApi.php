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
        $failureReason = $this->storeSession($result['data']);

        if ($failureReason === null) {
            return $result;
        }

        // The API call succeeded, but the device could not persist the
        // session. Without this check the caller would navigate on to a
        // screen that immediately bounces back to a blank login form, which
        // looks like the tap did nothing.
        return [
            'ok' => false,
            'status' => $result['status'],
            'data' => $result['data'],
            'message' => $failureReason,
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

    /**
     * Persist the session, then read it straight back through SecureStorage
     * to confirm it actually round-tripped rather than trusting a bare
     * success flag from the write.
     *
     * @param  array{token: string, user: array<string, mixed>}  $data
     * @return string|null A human-readable failure reason, or null on success.
     */
    protected function storeSession(array $data): ?string
    {
        $tokenSaved = SecureStorage::set('auth_token', $data['token']);
        $userSaved = SecureStorage::set('auth_user', json_encode($data['user']));

        if (! $tokenSaved || ! $userSaved) {
            return 'Signed in, but this device rejected saving your session (SecureStorage write failed). '.
                'If you are using an emulator, make sure a screen lock (PIN or pattern) is set, then try again.';
        }

        $readBack = SecureStorage::read('auth_token');

        if ($readBack->found() && $readBack->value === $data['token']) {
            return null;
        }

        return match (true) {
            $readBack->unavailable() => 'Signed in, but your device is locked and cannot decrypt the saved session yet. Unlock your device and try again.',
            $readBack->missing() => 'Signed in, but the session was not actually saved on this device. Please try again.',
            default => 'Signed in, but could not verify the saved session'
                .($readBack->code ? " [{$readBack->code}]" : '')
                .($readBack->message ? ": {$readBack->message}" : '').'.',
        };
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
