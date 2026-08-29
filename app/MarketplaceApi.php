<?php

namespace App;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Thin HTTP client the native screens use to talk to the marketplace
 * backend. Persists the bearer token in the app's own database-backed
 * cache (not the device Keychain/Keystore, which has proven unreliable
 * across NativePHP builds/emulators) and normalizes every response into
 * a simple ['ok' => bool, ...] shape so Blade views never have to deal
 * with HTTP client internals directly.
 *
 * @phpstan-type ApiResult array{ok: bool, status: int, data: array<string, mixed>, message: string|null}
 */
class MarketplaceApi
{
    /**
     * Demo accounts a login keyword maps onto. There is no real
     * authentication here by design: typing "customer" or "provider"
     * (anywhere in the field, case-insensitive) logs in as the matching
     * seeded demo account. See MarketplaceDemoSeeder for these users.
     *
     * @var array<string, array{email: string, password: string}>
     */
    protected const DEMO_ACCOUNTS = [
        'provider' => ['email' => 'provider1@example.com', 'password' => 'password'],
        'customer' => ['email' => 'customer@example.com', 'password' => 'password'],
    ];

    public function token(): ?string
    {
        return Cache::get('auth_token');
    }

    public function isLoggedIn(): bool
    {
        return filled($this->token());
    }

    public function currentUser(): ?array
    {
        return Cache::get('auth_user');
    }

    public function isProvider(): bool
    {
        return ($this->currentUser()['role'] ?? null) === 'provider';
    }

    /**
     * Log in as whichever demo account the keyword names, skipping real
     * credential entry entirely. Returns a validation-style error when the
     * keyword doesn't match either demo account.
     */
    public function loginAsDemo(string $keyword): array
    {
        $keyword = strtolower(trim($keyword));

        $account = collect(self::DEMO_ACCOUNTS)
            ->first(fn (array $account, string $role) => str_contains($keyword, $role));

        if ($account === null) {
            return [
                'ok' => false,
                'status' => 422,
                'data' => [],
                'message' => 'Type "customer" or "provider" to continue.',
            ];
        }

        return $this->login($account['email'], $account['password']);
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

        Cache::forget('auth_token');
        Cache::forget('auth_user');
    }

    public function me(): array
    {
        $result = $this->request('get', '/me');

        if ($result['ok']) {
            Cache::forever('auth_user', $result['data']);
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
     * Persist the session, then read it straight back to confirm it
     * actually round-tripped rather than trusting the write alone.
     *
     * @param  array{token: string, user: array<string, mixed>}  $data
     * @return string|null A human-readable failure reason, or null on success.
     */
    protected function storeSession(array $data): ?string
    {
        Cache::forever('auth_token', $data['token']);
        Cache::forever('auth_user', $data['user']);

        if ($this->token() === $data['token']) {
            return null;
        }

        return 'Signed in, but this device could not save your session. Please try again.';
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
