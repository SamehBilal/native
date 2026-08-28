<?php

use App\Models\Provider;
use App\Models\ServiceRequest;
use App\Models\User;

test('chat is closed until the request is accepted', function () {
    $customer = User::factory()->create();
    $request = ServiceRequest::factory()->for($customer)->create();

    $this->actingAs($customer, 'sanctum')
        ->postJson("/api/v1/service-requests/{$request->id}/messages", ['body' => 'Hello?'])
        ->assertForbidden();
});

test('the customer and accepted provider can exchange messages', function () {
    $customer = User::factory()->create();
    $providerUser = User::factory()->provider()->create();
    $provider = Provider::factory()->for($providerUser)->create();

    $request = ServiceRequest::factory()->for($customer)->accepted()->create([
        'accepted_provider_id' => $provider->id,
    ]);

    $this->actingAs($customer, 'sanctum')
        ->postJson("/api/v1/service-requests/{$request->id}/messages", ['body' => 'On my way?'])
        ->assertCreated();

    $this->actingAs($providerUser, 'sanctum')
        ->postJson("/api/v1/service-requests/{$request->id}/messages", ['body' => 'Yes, 10 minutes.'])
        ->assertCreated();

    $response = $this->actingAs($customer, 'sanctum')
        ->getJson("/api/v1/service-requests/{$request->id}/messages")
        ->assertOk();

    expect($response->json('data'))->toHaveCount(2);
});

test('a stranger cannot read or send messages on a request they are not part of', function () {
    $customer = User::factory()->create();
    $providerUser = User::factory()->provider()->create();
    $provider = Provider::factory()->for($providerUser)->create();
    $request = ServiceRequest::factory()->for($customer)->accepted()->create([
        'accepted_provider_id' => $provider->id,
    ]);

    $stranger = User::factory()->create();

    $this->actingAs($stranger, 'sanctum')
        ->getJson("/api/v1/service-requests/{$request->id}/messages")
        ->assertForbidden();

    $this->actingAs($stranger, 'sanctum')
        ->postJson("/api/v1/service-requests/{$request->id}/messages", ['body' => 'Hi'])
        ->assertForbidden();
});
