<?php

use App\Models\Provider;
use App\Models\User;

test('a provider can push their live location', function () {
    $providerUser = User::factory()->provider()->create();
    $provider = Provider::factory()->for($providerUser)->create();

    $this->actingAs($providerUser, 'sanctum')
        ->postJson('/api/v1/provider/location', ['latitude' => 24.8, 'longitude' => 46.9])
        ->assertOk()
        ->assertJsonPath('data.latitude', 24.8)
        ->assertJsonPath('data.longitude', 46.9);

    expect($provider->fresh())
        ->latitude->toBe(24.8)
        ->longitude->toBe(46.9);
});

test('a customer cannot push a provider location', function () {
    $customer = User::factory()->create();

    $this->actingAs($customer, 'sanctum')
        ->postJson('/api/v1/provider/location', ['latitude' => 24.8, 'longitude' => 46.9])
        ->assertForbidden();
});
