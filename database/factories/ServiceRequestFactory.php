<?php

namespace Database\Factories;

use App\Models\ServiceRequest;
use App\Models\User;
use App\ServiceRequestStatus;
use App\ServiceType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceRequest>
 */
class ServiceRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $latitude = ProviderFactory::BASE_LATITUDE + fake()->randomFloat(6, -0.05, 0.05);
        $longitude = ProviderFactory::BASE_LONGITUDE + fake()->randomFloat(6, -0.05, 0.05);

        return [
            'user_id' => User::factory(),
            'service_type' => fake()->randomElement(ServiceType::cases()),
            'status' => ServiceRequestStatus::Pending,
            'pickup_latitude' => $latitude,
            'pickup_longitude' => $longitude,
            'customer_latitude' => $latitude,
            'customer_longitude' => $longitude,
            'description' => fake()->optional()->sentence(),
        ];
    }

    public function accepted(): static
    {
        return $this->state(['status' => ServiceRequestStatus::Accepted]);
    }

    public function completed(): static
    {
        return $this->state(['status' => ServiceRequestStatus::Completed]);
    }
}
