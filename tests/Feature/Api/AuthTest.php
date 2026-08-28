<?php

use App\Models\User;
use App\ServiceType;
use App\UserRole;

test('a customer can register and receives a token', function () {
    $response = $this->postJson('/api/v1/register', [
        'name' => 'Amina Rider',
        'email' => 'amina@example.com',
        'password' => 'password',
        'role' => 'customer',
    ]);

    $response->assertCreated()
        ->assertJsonPath('user.email', 'amina@example.com')
        ->assertJsonPath('user.role', 'customer')
        ->assertJsonStructure(['user', 'token']);

    expect(User::where('email', 'amina@example.com')->first())
        ->role->toBe(UserRole::Customer);
});

test('a provider must supply a starting location and services offered to register', function () {
    $response = $this->postJson('/api/v1/register', [
        'name' => 'Speedy Tires',
        'email' => 'speedy@example.com',
        'password' => 'password',
        'role' => 'provider',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['service_types', 'latitude', 'longitude']);
});

test('a provider registers with a provider profile', function () {
    $response = $this->postJson('/api/v1/register', [
        'name' => 'Speedy Tires',
        'email' => 'speedy@example.com',
        'password' => 'password',
        'role' => 'provider',
        'service_types' => ['tire_exchange'],
        'latitude' => 24.7,
        'longitude' => 46.6,
    ]);

    $response->assertCreated();

    $user = User::where('email', 'speedy@example.com')->first();

    expect($user->provider)->not->toBeNull()
        ->and($user->provider->offersService(ServiceType::TireExchange))->toBeTrue();
});

test('a user can log in with correct credentials', function () {
    $user = User::factory()->create(['email' => 'jane@example.com', 'password' => 'password']);

    $response = $this->postJson('/api/v1/login', [
        'email' => 'jane@example.com',
        'password' => 'password',
        'device_name' => 'iphone',
    ]);

    $response->assertOk()->assertJsonStructure(['user', 'token']);
});

test('login fails with incorrect credentials', function () {
    User::factory()->create(['email' => 'jane@example.com', 'password' => 'password']);

    $response = $this->postJson('/api/v1/login', [
        'email' => 'jane@example.com',
        'password' => 'wrong-password',
        'device_name' => 'iphone',
    ]);

    $response->assertUnauthorized();
});

test('an authenticated user can fetch their own profile', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/me')
        ->assertOk()
        ->assertJsonPath('id', $user->id);
});

test('guests cannot access protected endpoints', function () {
    $this->getJson('/api/v1/me')->assertUnauthorized();
});
