<?php

namespace App\NativeComponents;

use App\MarketplaceApi;
use Illuminate\View\View;
use Native\Mobile\Attributes\Poll;
use Native\Mobile\Edge\NativeComponent;
use Native\Mobile\Facades\Browser;

class Tracking extends NativeComponent
{
    public int $requestId = 0;

    public bool $isProvider = false;

    public string $status = 'accepted';

    public array $customer = [];

    public array $provider = [];

    public ?float $distanceKm = null;

    /**
     * A self-contained Leaflet/OpenStreetMap page, built once the first time
     * both parties' coordinates are known and never rebuilt afterward — the
     * "provider approaching" animation runs entirely client-side in the
     * embedded JavaScript, so the webview never has to reload on every poll.
     */
    public string $mapHtml = '';

    public array $messages = [];

    public string $newMessage = '';

    public bool $sending = false;

    public ?string $error = null;

    public function mount(): void
    {
        $api = app(MarketplaceApi::class);

        if (! $api->isLoggedIn()) {
            $this->replace('/app/login');

            return;
        }

        $this->requestId = (int) $this->param('id');
        $this->isProvider = $api->isProvider();

        $details = $api->showServiceRequest($this->requestId);

        if ($details['ok']) {
            $this->customer = $details['data']['customer'] ?? [];
            $this->provider = $details['data']['accepted_provider'] ?? [];
            $this->status = $details['data']['status'];
        }

        $this->refresh();
    }

    #[Poll(3000)]
    public function refresh(): void
    {
        $api = app(MarketplaceApi::class);

        $tracking = $api->track($this->requestId);

        if ($tracking['ok']) {
            $this->status = $tracking['data']['status'];
            $this->distanceKm = $tracking['data']['distance_km'] ?? null;

            $this->buildMapIfReady($tracking['data']);
        }

        $messages = $api->messages($this->requestId);

        if ($messages['ok']) {
            $this->messages = $messages['data'] ?? [];
        }
    }

    protected function buildMapIfReady(array $tracking): void
    {
        if ($this->mapHtml !== '') {
            return;
        }

        $customerLat = $tracking['customer']['latitude'] ?? null;
        $customerLng = $tracking['customer']['longitude'] ?? null;
        $providerLat = $tracking['provider']['latitude'] ?? null;
        $providerLng = $tracking['provider']['longitude'] ?? null;

        if ($customerLat === null || $providerLat === null) {
            return;
        }

        $this->mapHtml = $this->renderMap($customerLat, $customerLng, $providerLat, $providerLng);
    }

    /**
     * Renders a standalone HTML page: an OpenStreetMap tile map (via
     * Leaflet, loaded from its public CDN) with a marker for each party and
     * a 45-second client-side animation moving the provider marker toward
     * the customer, updating a live distance badge as it goes.
     */
    protected function renderMap(float $customerLat, float $customerLng, float $providerLat, float $providerLng): string
    {
        $durationMs = 45_000;

        return <<<HTML
        <!doctype html>
        <html>
        <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
        <style>
            html, body, #map { height: 100%; margin: 0; padding: 0; background: #e5e7eb; }
            .ra-badge { background: #fff; padding: 6px 10px; border-radius: 8px; font: 600 12px -apple-system, Roboto, sans-serif; box-shadow: 0 1px 4px rgba(0,0,0,.3); color: #111827; }
        </style>
        </head>
        <body>
        <div id="map"></div>
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <script>
            var customer = [{$customerLat}, {$customerLng}];
            var start = [{$providerLat}, {$providerLng}];
            var map = L.map('map', { zoomControl: false, attributionControl: false }).fitBounds([customer, start], { padding: [40, 40] });
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);

            function dot(color) {
                return L.divIcon({
                    className: '',
                    html: '<div style="background:' + color + ';width:16px;height:16px;border-radius:50%;border:2px solid white;box-shadow:0 0 4px rgba(0,0,0,.4)"></div>',
                    iconSize: [16, 16],
                    iconAnchor: [8, 8],
                });
            }

            L.marker(customer, { icon: dot('#2563eb') }).addTo(map).bindPopup('You');
            var providerMarker = L.marker(start, { icon: dot('#f97316') }).addTo(map).bindPopup('Provider');
            var line = L.polyline([start, customer], { color: '#94a3b8', dashArray: '6,6' }).addTo(map);

            var badge = L.control({ position: 'bottomleft' });
            badge.onAdd = function () {
                var div = L.DomUtil.create('div', 'ra-badge');
                div.id = 'distance-badge';
                return div;
            };
            badge.addTo(map);

            function distanceKm(a, b) {
                var R = 6371, dLat = (b[0] - a[0]) * Math.PI / 180, dLng = (b[1] - a[1]) * Math.PI / 180;
                var s = Math.sin(dLat / 2) ** 2 + Math.cos(a[0] * Math.PI / 180) * Math.cos(b[0] * Math.PI / 180) * Math.sin(dLng / 2) ** 2;
                return R * 2 * Math.atan2(Math.sqrt(s), Math.sqrt(1 - s));
            }

            var startTime = Date.now();
            var durationMs = {$durationMs};

            function tick() {
                var t = Math.min(1, (Date.now() - startTime) / durationMs);
                var eased = 1 - Math.pow(1 - t, 2);
                var pos = [
                    start[0] + (customer[0] - start[0]) * eased,
                    start[1] + (customer[1] - start[1]) * eased,
                ];
                providerMarker.setLatLng(pos);
                line.setLatLngs([pos, customer]);
                document.getElementById('distance-badge').innerText = t >= 1
                    ? 'Arrived'
                    : distanceKm(pos, customer).toFixed(2) + ' km away';
                if (t < 1) requestAnimationFrame(tick);
            }
            tick();
        </script>
        </body>
        </html>
        HTML;
    }

    public function sendMessage(): void
    {
        if (trim($this->newMessage) === '') {
            return;
        }

        $this->sending = true;
        $result = app(MarketplaceApi::class)->sendMessage($this->requestId, $this->newMessage);
        $this->sending = false;

        if (! $result['ok']) {
            $this->error = $result['message'];

            return;
        }

        $this->newMessage = '';
        $this->refresh();
    }

    public function call(): void
    {
        $phone = $this->isProvider ? ($this->customer['phone'] ?? null) : ($this->provider['phone'] ?? null);

        if ($phone) {
            Browser::open('tel:'.$phone);
        }
    }

    public function render(): View
    {
        return view('native.tracking');
    }
}
