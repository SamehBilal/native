<?php

namespace Database\Seeders;

use App\Models\Provider;
use App\Models\ServiceMessage;
use App\Models\ServiceOffer;
use App\Models\ServiceRequest;
use App\Models\User;
use App\ServiceType;
use Database\Factories\ProviderFactory;
use Illuminate\Database\Seeder;

class MarketplaceDemoSeeder extends Seeder
{
    /**
     * Seed a realistic set of nearby providers, a demo customer, and sample
     * service requests so the mobile app has something to explore immediately.
     */
    public function run(): void
    {
        $customer = User::factory()->create([
            'name' => 'Test Customer',
            'email' => 'customer@example.com',
            'phone' => '+966500000001',
        ]);

        $providers = collect([
            ['name' => 'Fahad\'s Tire Service', 'km' => 1.2, 'bearing' => 30, 'types' => [ServiceType::TireExchange], 'available' => true],
            ['name' => 'Khalid Roadside Tires', 'km' => 4.5, 'bearing' => 120, 'types' => [ServiceType::TireExchange], 'available' => true],
            ['name' => 'Speedy Tow Riyadh', 'km' => 2.0, 'bearing' => 200, 'types' => [ServiceType::EmergencyTow], 'available' => true],
            ['name' => 'Al-Amin Towing', 'km' => 8.0, 'bearing' => 280, 'types' => [ServiceType::EmergencyTow], 'available' => true],
            ['name' => 'Gulf Auto Rescue', 'km' => 15.0, 'bearing' => 60, 'types' => [ServiceType::TireExchange, ServiceType::EmergencyTow], 'available' => true],
            ['name' => 'Desert Wheels (offline)', 'km' => 3.0, 'bearing' => 340, 'types' => [ServiceType::TireExchange], 'available' => false],
        ])->map(function (array $data, int $index) {
            $user = User::factory()->provider()->create([
                'name' => $data['name'],
                'email' => sprintf('provider%d@example.com', $index + 1),
                'phone' => sprintf('+96650000%04d', $index + 10),
            ]);

            return Provider::factory()
                ->for($user)
                ->offering(...$data['types'])
                ->atDistanceKm($data['km'], $data['bearing'])
                ->state(['is_available' => $data['available']])
                ->create();
        });

        // A live pending request with two competing offers, so the "explore"
        // and "offers inbox" screens have something to show out of the box.
        $pendingRequest = ServiceRequest::factory()->for($customer)->create([
            'service_type' => ServiceType::TireExchange,
            'pickup_latitude' => ProviderFactory::BASE_LATITUDE,
            'pickup_longitude' => ProviderFactory::BASE_LONGITUDE,
            'customer_latitude' => ProviderFactory::BASE_LATITUDE,
            'customer_longitude' => ProviderFactory::BASE_LONGITUDE,
            'description' => 'Flat rear-left tire on the shoulder of King Fahd Road.',
        ]);

        ServiceOffer::factory()->for($pendingRequest, 'serviceRequest')->create([
            'provider_id' => $providers[0]->id,
            'fee' => 65.00,
            'eta_minutes' => 12,
            'message' => 'Can be there in 12 minutes, carrying your tire size in stock.',
        ]);

        ServiceOffer::factory()->for($pendingRequest, 'serviceRequest')->create([
            'provider_id' => $providers[1]->id,
            'fee' => 55.00,
            'eta_minutes' => 20,
            'message' => 'Cheaper option, a little further out.',
        ]);

        // A completed, accepted request so the tracking/chat screen has history to render.
        $acceptedRequest = ServiceRequest::factory()->for($customer)->accepted()->create([
            'service_type' => ServiceType::EmergencyTow,
            'accepted_provider_id' => $providers[2]->id,
            'pickup_latitude' => ProviderFactory::BASE_LATITUDE + 0.01,
            'pickup_longitude' => ProviderFactory::BASE_LONGITUDE + 0.01,
            'customer_latitude' => ProviderFactory::BASE_LATITUDE + 0.01,
            'customer_longitude' => ProviderFactory::BASE_LONGITUDE + 0.01,
            'description' => 'Car won\'t start, needs a tow to the nearest garage.',
        ]);

        ServiceOffer::factory()->for($acceptedRequest, 'serviceRequest')->accepted()->create([
            'provider_id' => $providers[2]->id,
            'fee' => 120.00,
            'eta_minutes' => 15,
        ]);

        ServiceMessage::factory()->for($acceptedRequest, 'serviceRequest')->create([
            'sender_id' => $customer->id,
            'body' => 'Thanks for accepting, I\'m parked next to the blue sign.',
        ]);

        ServiceMessage::factory()->for($acceptedRequest, 'serviceRequest')->create([
            'sender_id' => $providers[2]->user_id,
            'body' => 'On my way, should be there in about 15 minutes.',
        ]);
    }
}
