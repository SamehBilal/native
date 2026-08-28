<?php

namespace Database\Factories;

use App\Models\Provider;
use App\Models\User;
use App\ServiceType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Provider>
 */
class ProviderFactory extends Factory
{
    /**
     * Demo base coordinate (Riyadh, Saudi Arabia) that seeded providers scatter around.
     */
    const BASE_LATITUDE = 24.7136;

    const BASE_LONGITUDE = 46.6753;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->provider(),
            'service_types' => fake()->randomElement([
                [ServiceType::TireExchange->value],
                [ServiceType::EmergencyTow->value],
                [ServiceType::TireExchange->value, ServiceType::EmergencyTow->value],
            ]),
            'latitude' => self::BASE_LATITUDE + fake()->randomFloat(6, -0.3, 0.3),
            'longitude' => self::BASE_LONGITUDE + fake()->randomFloat(6, -0.3, 0.3),
            'is_available' => true,
            'rating' => fake()->randomFloat(2, 3.5, 5.0),
            'vehicle_info' => fake()->randomElement(['Flatbed Truck', 'Tow Truck', 'Service Van']).' - '.fake()->regexify('[A-Z]{3}-[0-9]{4}'),
        ];
    }

    /**
     * Place the provider a specific distance (roughly) from the demo base coordinate.
     */
    public function atDistanceKm(float $km, float $bearingDegrees = 0): static
    {
        $bearing = deg2rad($bearingDegrees);
        $angularDistance = $km / 6371.0;
        $latRad = deg2rad(self::BASE_LATITUDE);

        $newLat = asin(sin($latRad) * cos($angularDistance) + cos($latRad) * sin($angularDistance) * cos($bearing));
        $newLng = deg2rad(self::BASE_LONGITUDE) + atan2(
            sin($bearing) * sin($angularDistance) * cos($latRad),
            cos($angularDistance) - sin($latRad) * sin($newLat)
        );

        return $this->state([
            'latitude' => rad2deg($newLat),
            'longitude' => rad2deg($newLng),
        ]);
    }

    /**
     * @param  ServiceType  ...$types
     */
    public function offering(ServiceType ...$types): static
    {
        return $this->state([
            'service_types' => array_map(fn (ServiceType $type) => $type->value, $types),
        ]);
    }

    public function unavailable(): static
    {
        return $this->state(['is_available' => false]);
    }
}
