<?php

namespace Database\Factories;

use App\Models\Provider;
use App\Models\ServiceOffer;
use App\Models\ServiceRequest;
use App\ServiceOfferStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceOffer>
 */
class ServiceOfferFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'service_request_id' => ServiceRequest::factory(),
            'provider_id' => Provider::factory(),
            'fee' => fake()->randomFloat(2, 30, 250),
            'eta_minutes' => fake()->numberBetween(5, 45),
            'message' => fake()->optional()->sentence(),
            'status' => ServiceOfferStatus::Pending,
        ];
    }

    public function accepted(): static
    {
        return $this->state(['status' => ServiceOfferStatus::Accepted]);
    }

    public function rejected(): static
    {
        return $this->state(['status' => ServiceOfferStatus::Rejected]);
    }
}
