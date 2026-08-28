<?php

namespace Database\Factories;

use App\Models\ServiceMessage;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceMessage>
 */
class ServiceMessageFactory extends Factory
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
            'sender_id' => User::factory(),
            'body' => fake()->sentence(),
        ];
    }
}
