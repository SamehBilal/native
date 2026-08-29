<?php

namespace App;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Fully local demo "backend" for the native screens — no network requests
 * of any kind. Everything (users, requests, offers, messages, live
 * tracking) lives in the app's own database-backed cache and is fabricated
 * on the fly, so the app runs standalone on a device with nothing to host
 * or configure. This is for demonstration only: there is no real
 * authentication, persistence guarantee, or multi-device sync.
 *
 * @phpstan-type ApiResult array{ok: bool, status: int, data: array<string, mixed>, message: string|null}
 */
class MarketplaceApi
{
    /**
     * @var array<string, array{id: int, name: string, phone: string, role: string}>
     */
    protected const DEMO_USERS = [
        'customer' => ['id' => 1, 'name' => 'Alex Rivera', 'phone' => '+1-555-0100', 'role' => 'customer'],
        'provider' => ['id' => 2, 'name' => 'Sam the Mechanic', 'phone' => '+1-555-0101', 'role' => 'provider'],
    ];

    /**
     * Canned providers used to populate "nearby providers" and to author
     * the offers that automatically arrive on a new request. Each only
     * offers (and only bids on) the service types listed, same as a real
     * provider profile would.
     *
     * @var list<array{id: int, name: string, vehicle_info: string, rating: float, phone: string, services: list<string>}>
     */
    protected const DEMO_PROVIDERS = [
        ['id' => 101, 'name' => "Sam's Mobile Tires", 'vehicle_info' => 'Flatbed Truck - RA-204', 'rating' => 4.8, 'phone' => '+1-555-0201', 'services' => ['tire_exchange']],
        ['id' => 102, 'name' => 'QuickFix Roadside', 'vehicle_info' => 'Service Van - RA-315', 'rating' => 4.6, 'phone' => '+1-555-0202', 'services' => ['tire_exchange', 'emergency_tow']],
        ['id' => 103, 'name' => 'Desert Rescue Towing', 'vehicle_info' => 'Tow Truck - RA-118', 'rating' => 4.9, 'phone' => '+1-555-0203', 'services' => ['emergency_tow']],
        ['id' => 104, 'name' => 'Rapid Response Auto', 'vehicle_info' => 'Tow Truck - RA-402', 'rating' => 4.7, 'phone' => '+1-555-0204', 'services' => ['emergency_tow', 'tire_exchange']],
        ['id' => 105, 'name' => "Layla's Tire Service", 'vehicle_info' => 'Pickup Truck - RA-556', 'rating' => 4.95, 'phone' => '+1-555-0205', 'services' => ['tire_exchange']],
        ['id' => 106, 'name' => '24/7 Highway Rescue', 'vehicle_info' => 'Heavy Tow Truck - RA-731', 'rating' => 4.5, 'phone' => '+1-555-0206', 'services' => ['emergency_tow']],
    ];

    /**
     * Request IDs at or above this are seeded walk-ins (see WALK_IN_REQUESTS)
     * rather than something a demo customer created — used to trigger the
     * "customer accepts immediately" behavior in submitOffer().
     */
    protected const WALK_IN_ID_START = 9000;

    /**
     * Realistic walk-in requests already waiting when the provider dashboard
     * is first opened, so there's more than one scenario to demo.
     *
     * @var list<array{id: int, name: string, service_type: string, description: string, distance_km: float, offset: array{0: float, 1: float}}>
     */
    protected const WALK_IN_REQUESTS = [
        ['id' => 9001, 'name' => 'Dana Cole', 'service_type' => 'tire_exchange', 'description' => 'Flat rear tire on the King Fahd Road shoulder.', 'distance_km' => 2.2, 'offset' => [0.010, 0.006]],
        ['id' => 9002, 'name' => 'Marcus Webb', 'service_type' => 'emergency_tow', 'description' => "Car won't start after overheating near the Al Olaya exit ramp.", 'distance_km' => 4.5, 'offset' => [-0.018, 0.014]],
        ['id' => 9003, 'name' => 'Priya Nair', 'service_type' => 'tire_exchange', 'description' => 'Blown tire in a parking garage — need someone with a jack.', 'distance_km' => 1.1, 'offset' => [0.004, -0.008]],
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
     * Log in as whichever demo account the keyword names. There is no real
     * authentication: typing "customer" or "provider" signs straight in.
     */
    public function loginAsDemo(string $keyword): array
    {
        $keyword = strtolower(trim($keyword));

        $role = collect(self::DEMO_USERS)->keys()->first(fn (string $role) => str_contains($keyword, $role));

        if ($role === null) {
            return $this->fail(422, 'Type "customer" or "provider" to continue.');
        }

        $this->signIn(self::DEMO_USERS[$role]);

        return $this->ok(['user' => $this->currentUser(), 'token' => $this->token()]);
    }

    /**
     * Create a new local demo account from whatever was typed in the
     * registration form and sign straight into it — no real account is
     * created anywhere.
     */
    public function register(array $payload): array
    {
        $role = $payload['role'] ?? 'customer';

        $this->signIn([
            'id' => $this->nextId('user'),
            'name' => $payload['name'] ?? 'New User',
            'email' => $payload['email'] ?? null,
            'phone' => $payload['phone'] ?? '+1-555-0199',
            'role' => $role,
            'vehicle_info' => $payload['vehicle_info'] ?? null,
        ]);

        return $this->ok(['user' => $this->currentUser(), 'token' => $this->token()]);
    }

    protected function signIn(array $user): void
    {
        Cache::forever('auth_token', 'demo-token-'.$user['id']);
        Cache::forever('auth_user', $user);
    }

    public function logout(): void
    {
        Cache::forget('auth_token');
        Cache::forget('auth_user');
    }

    public function me(): array
    {
        return $this->currentUser() ? $this->ok($this->currentUser()) : $this->fail(401, 'Not logged in.');
    }

    public function updateProfile(array $data): array
    {
        $user = array_merge($this->currentUser() ?? [], array_filter([
            'name' => $data['name'] ?? null,
            'phone' => $data['phone'] ?? null,
        ]));

        Cache::forever('auth_user', $user);

        return $this->ok($user);
    }

    public function createServiceRequest(array $data): array
    {
        $id = $this->nextId('request');
        $now = microtime(true);
        $providers = $this->providersFor($data['service_type'])->shuffle();
        $budget = isset($data['budget']) ? (int) $data['budget'] : null;

        $requests = $this->requests();
        $requests[$id] = [
            'id' => $id,
            'customer' => $this->currentUser() ?? self::DEMO_USERS['customer'],
            'service_type' => $data['service_type'],
            'description' => $data['description'] ?? null,
            'budget' => $budget,
            'pickup_latitude' => $data['pickup_latitude'],
            'pickup_longitude' => $data['pickup_longitude'],
            'status' => 'pending',
            'created_at' => $now,
            'accepted_provider' => null,
            'tracking' => null,
            // Every matching provider bids, arriving staggered ~5s apart so
            // offers visibly trickle in as the customer polls, instead of
            // showing up all at once.
            'offers' => $providers->values()->map(fn (array $provider, int $index) => $this->makeOffer(
                provider: $provider,
                serviceType: $data['service_type'],
                revealAt: $now + 4 + $index * 5,
                budget: $budget,
            ))->all(),
        ];
        $this->saveRequests($requests);

        return $this->ok(['id' => $id]);
    }

    /**
     * Cancel a still-pending request. Only the requester can meaningfully do
     * this in the real app; the demo doesn't check that since there's only
     * ever one demo customer.
     */
    public function cancelRequest(int $requestId): array
    {
        $requests = $this->requests();
        $request = $requests[$requestId] ?? null;

        if ($request === null) {
            return $this->fail(404, 'Request not found.');
        }

        $requests[$requestId]['status'] = 'cancelled';
        $this->saveRequests($requests);

        return $this->ok([]);
    }

    /**
     * @return Collection<int, array{id: int, name: string, vehicle_info: string, rating: float, phone: string, services: list<string>}>
     */
    protected function providersFor(string $serviceType): Collection
    {
        return collect(self::DEMO_PROVIDERS)->filter(fn (array $provider) => in_array($serviceType, $provider['services'], true));
    }

    public function showServiceRequest(int $requestId): array
    {
        $request = $this->requests()[$requestId] ?? null;

        if ($request === null) {
            return $this->fail(404, 'Request not found.');
        }

        return $this->ok([
            'status' => $request['status'],
            'service_type' => $request['service_type'],
            'budget' => $request['budget'] ?? null,
            'offers' => $this->visibleOffers($request),
            'customer' => $request['customer'],
            'accepted_provider' => $request['accepted_provider'],
        ]);
    }

    public function nearbyProviders(int $requestId): array
    {
        $request = $this->requests()[$requestId] ?? null;
        $providers = $request !== null ? $this->providersFor($request['service_type']) : collect(self::DEMO_PROVIDERS);

        return $this->ok($providers
            ->values()
            ->map(fn (array $provider, int $index) => [
                'id' => $provider['id'],
                'name' => $provider['name'],
                'distance_km' => round(1.2 + $index * 1.1, 1),
            ])->all());
    }

    public function updateMyLocation(int $requestId, float $latitude, float $longitude): array
    {
        return $this->ok([]);
    }

    public function acceptOffer(int $offerId): array
    {
        $requests = $this->requests();

        foreach ($requests as $id => $request) {
            $offerIndex = collect($request['offers'])->search(fn (array $offer) => $offer['id'] === $offerId);

            if ($offerIndex === false) {
                continue;
            }

            $this->applyAcceptance($requests, $id, $offerIndex);
            $this->saveRequests($requests);

            return $this->ok([]);
        }

        return $this->fail(404, 'Offer not found.');
    }

    /**
     * @param  array<int, array<string, mixed>>  $requests  Passed by reference and mutated in place.
     */
    protected function applyAcceptance(array &$requests, int $requestId, int $offerIndex): void
    {
        $request = $requests[$requestId];

        foreach ($request['offers'] as $index => $offer) {
            $requests[$requestId]['offers'][$index]['status'] = $index === $offerIndex ? 'accepted' : 'rejected';
        }

        $accepted = $request['offers'][$offerIndex];
        $requests[$requestId]['status'] = 'accepted';
        $requests[$requestId]['accepted_provider'] = $accepted['provider'];
        $requests[$requestId]['tracking'] = [
            'provider_latitude' => $request['pickup_latitude'] + 0.02,
            'provider_longitude' => $request['pickup_longitude'] + 0.02,
        ];
    }

    /**
     * Reports live tracking positions, nudging the accepted provider's
     * simulated location a little closer to the customer on every poll so
     * the radar screen visibly animates the provider "arriving".
     */
    public function track(int $requestId): array
    {
        $requests = $this->requests();
        $request = $requests[$requestId] ?? null;

        if ($request === null) {
            return $this->fail(404, 'Request not found.');
        }

        $customerLat = $request['pickup_latitude'];
        $customerLng = $request['pickup_longitude'];

        if ($request['tracking'] === null) {
            return $this->ok([
                'status' => $request['status'],
                'customer' => ['latitude' => $customerLat, 'longitude' => $customerLng],
                'provider' => null,
                'distance_km' => null,
            ]);
        }

        $providerLat = $request['tracking']['provider_latitude'];
        $providerLng = $request['tracking']['provider_longitude'];

        // Close a third of the remaining gap each poll, so the marker keeps
        // visibly creeping toward the customer without ever overshooting.
        $providerLat += ($customerLat - $providerLat) * 0.35;
        $providerLng += ($customerLng - $providerLng) * 0.35;

        $requests[$requestId]['tracking'] = ['provider_latitude' => $providerLat, 'provider_longitude' => $providerLng];
        $this->saveRequests($requests);

        return $this->ok([
            'status' => $request['status'],
            'customer' => ['latitude' => $customerLat, 'longitude' => $customerLng],
            'provider' => ['latitude' => $providerLat, 'longitude' => $providerLng],
            'distance_km' => round(Geo::distanceKm($customerLat, $customerLng, $providerLat, $providerLng), 2),
        ]);
    }

    public function messages(int $requestId): array
    {
        return $this->ok($this->messagesFor($requestId));
    }

    public function sendMessage(int $requestId, string $body): array
    {
        $messages = $this->messagesFor($requestId);
        $senderRole = $this->currentUser()['role'] ?? 'customer';

        $messages[] = [
            'id' => count($messages) + 1,
            'body' => $body,
            'sender_role' => $senderRole,
        ];

        // A small demo touch: the other side "replies" a moment later so the
        // chat screen (which polls every 3s) feels like a live conversation.
        $messages[] = [
            'id' => count($messages) + 1,
            'body' => $senderRole === 'provider' ? "Got it, thanks!" : "On my way, thanks for the update!",
            'sender_role' => $senderRole === 'provider' ? 'customer' : 'provider',
        ];

        $this->saveMessages($requestId, $messages);

        return $this->ok([]);
    }

    public function providerRequests(): array
    {
        $this->seedWalkInRequestsIfEmpty();
        $providerId = $this->currentUser()['id'] ?? null;

        return $this->ok(collect($this->requests())
            ->filter(fn (array $request) => $request['status'] === 'pending')
            ->reject(fn (array $request) => collect($request['offers'])->contains(fn (array $offer) => ($offer['provider']['id'] ?? null) === $providerId))
            ->map(fn (array $request) => [
                'id' => $request['id'],
                'service_type' => $request['service_type'],
                'description' => $request['description'],
                'budget' => $request['budget'] ?? null,
                'distance_km' => $request['distance_km'] ?? round(1.5 + ($request['id'] % 5) * 0.7, 1),
            ])
            ->values()
            ->all());
    }

    public function providerOffers(): array
    {
        $providerId = $this->currentUser()['id'] ?? null;

        return $this->ok(collect($this->requests())
            ->flatMap(fn (array $request) => collect($request['offers'])
                ->filter(fn (array $offer) => ($offer['provider']['id'] ?? null) === $providerId)
                ->map(fn (array $offer) => [
                    'id' => $offer['id'],
                    'service_request_id' => $request['id'],
                    'fee' => $offer['fee'],
                    'eta_minutes' => $offer['eta_minutes'],
                    'status' => $offer['status'],
                ]))
            ->values()
            ->all());
    }

    public function submitOffer(int $requestId, array $data): array
    {
        $requests = $this->requests();
        $request = $requests[$requestId] ?? null;

        if ($request === null) {
            return $this->fail(404, 'Request not found.');
        }

        $user = $this->currentUser() ?? self::DEMO_USERS['provider'];
        $offerIndex = count($request['offers']);

        $requests[$requestId]['offers'][] = [
            'id' => $this->nextId('offer'),
            'provider' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'vehicle_info' => $user['vehicle_info'] ?? 'Roadside Assistance Vehicle',
                'rating' => 5.0,
                'phone' => $user['phone'] ?? '+1-555-0199',
            ],
            'fee' => $data['fee'],
            'eta_minutes' => $data['eta_minutes'],
            'message' => $data['message'] ?? null,
            'status' => 'pending',
            'reveal_at' => null,
        ];

        // The seeded walk-in requests (see WALK_IN_REQUESTS) simulate a
        // stranded customer who accepts the first bid immediately, so a
        // provider can demo the full offer → tracking → chat flow solo,
        // without switching accounts to accept it as the customer.
        if ($requestId >= self::WALK_IN_ID_START) {
            $this->applyAcceptance($requests, $requestId, $offerIndex);
        }

        $this->saveRequests($requests);

        return $this->ok([]);
    }

    public function pushProviderLocation(float $latitude, float $longitude): array
    {
        return $this->ok([]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function requests(): array
    {
        return Cache::get('demo_requests', []);
    }

    /**
     * @param  array<int, array<string, mixed>>  $requests
     */
    protected function saveRequests(array $requests): void
    {
        Cache::forever('demo_requests', $requests);
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function messagesFor(int $requestId): array
    {
        return Cache::get("demo_messages_{$requestId}", []);
    }

    /**
     * @param  list<array<string, mixed>>  $messages
     */
    protected function saveMessages(int $requestId, array $messages): void
    {
        Cache::forever("demo_messages_{$requestId}", $messages);
    }

    /**
     * Only the offers whose reveal time has passed are visible yet, so a
     * freshly created request's offers appear to "arrive" one at a time as
     * the customer polls — auto-generated offers only; anything a real
     * provider submitted (reveal_at is null) is visible immediately.
     *
     * @param  array<string, mixed>  $request
     * @return list<array<string, mixed>>
     */
    protected function visibleOffers(array $request): array
    {
        $now = microtime(true);

        return collect($request['offers'])
            ->filter(fn (array $offer) => ($offer['reveal_at'] ?? null) === null || $offer['reveal_at'] <= $now)
            ->values()
            ->all();
    }

    /**
     * @param  array{id: int, name: string, vehicle_info: string, rating: float, phone: string}  $provider
     */
    protected function makeOffer(array $provider, string $serviceType, float $revealAt, ?int $budget = null): array
    {
        $isTow = $serviceType === 'emergency_tow';
        [$min, $max] = $isTow ? [90, 150] : [45, 70];

        // A stated budget nudges providers to bid near it (±10%) instead of
        // the plain default range, so picking a budget visibly shapes the
        // offers that come back — clamped to a sane floor either way.
        if ($budget !== null) {
            $min = max(20, (int) round($budget * 0.9));
            $max = max($min + 5, (int) round($budget * 1.1));
        }

        return [
            'id' => $this->nextId('offer'),
            'provider' => $provider,
            'fee' => random_int($min, $max),
            'eta_minutes' => $isTow ? random_int(15, 30) : random_int(8, 20),
            'message' => null,
            'status' => 'pending',
            'reveal_at' => $revealAt,
        ];
    }

    /**
     * Seeds a handful of realistic pending requests — different service
     * types, descriptions, and distances — so the provider dashboard has
     * more than one scenario to demo before any customer has created a
     * real request in this run.
     */
    protected function seedWalkInRequestsIfEmpty(): void
    {
        if ($this->requests() !== []) {
            return;
        }

        [$baseLat, $baseLng] = [24.7136, 46.6753];

        $this->saveRequests(collect(self::WALK_IN_REQUESTS)->mapWithKeys(fn (array $scenario) => [
            $scenario['id'] => [
                'id' => $scenario['id'],
                'customer' => ['id' => 900 + $scenario['id'], 'name' => $scenario['name'], 'phone' => '+1-555-02'.$scenario['id'], 'role' => 'customer'],
                'service_type' => $scenario['service_type'],
                'description' => $scenario['description'],
                'distance_km' => $scenario['distance_km'],
                'pickup_latitude' => $baseLat + $scenario['offset'][0],
                'pickup_longitude' => $baseLng + $scenario['offset'][1],
                'status' => 'pending',
                'created_at' => microtime(true),
                'accepted_provider' => null,
                'tracking' => null,
                'offers' => [],
            ],
        ])->all());
    }

    /**
     * A small per-key counter. "user" starts past the two fixed demo
     * accounts (id 1 and 2) so a freshly registered demo user never
     * collides with them.
     */
    protected function nextId(string $key): int
    {
        $next = Cache::get("demo_next_{$key}_id", $key === 'user' ? 3 : 1);
        Cache::forever("demo_next_{$key}_id", $next + 1);

        return $next;
    }

    /**
     * @return array{ok: bool, status: int, data: array<string, mixed>, message: string|null}
     */
    protected function ok(array $data): array
    {
        return ['ok' => true, 'status' => 200, 'data' => $data, 'message' => null];
    }

    /**
     * @return array{ok: bool, status: int, data: array<string, mixed>, message: string|null}
     */
    protected function fail(int $status, string $message): array
    {
        return ['ok' => false, 'status' => $status, 'data' => [], 'message' => $message];
    }
}
