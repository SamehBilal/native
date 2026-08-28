<?php

use App\Models\Provider;
use App\Models\ServiceOffer;
use App\Models\ServiceRequest;
use App\Models\User;
use App\ServiceOfferStatus;
use App\ServiceRequestStatus;
use App\ServiceType;

test('a matching provider can submit an offer on a pending request', function () {
    $request = ServiceRequest::factory()->create(['service_type' => ServiceType::TireExchange]);
    $provider = User::factory()->provider()->create();
    Provider::factory()->for($provider)->offering(ServiceType::TireExchange)->create();

    $response = $this->actingAs($provider, 'sanctum')
        ->postJson("/api/v1/service-requests/{$request->id}/offers", [
            'fee' => 80,
            'eta_minutes' => 15,
            'message' => 'On my way',
        ]);

    $response->assertCreated()->assertJsonPath('data.status', 'pending');
});

test('a provider cannot offer on a request for a service they do not provide', function () {
    $request = ServiceRequest::factory()->create(['service_type' => ServiceType::EmergencyTow]);
    $provider = User::factory()->provider()->create();
    Provider::factory()->for($provider)->offering(ServiceType::TireExchange)->create();

    $this->actingAs($provider, 'sanctum')
        ->postJson("/api/v1/service-requests/{$request->id}/offers", [
            'fee' => 80,
            'eta_minutes' => 15,
        ])
        ->assertForbidden();
});

test('a provider cannot submit two offers on the same request', function () {
    $request = ServiceRequest::factory()->create(['service_type' => ServiceType::TireExchange]);
    $provider = User::factory()->provider()->create();
    $providerProfile = Provider::factory()->for($provider)->offering(ServiceType::TireExchange)->create();

    ServiceOffer::factory()->for($request, 'serviceRequest')->create(['provider_id' => $providerProfile->id]);

    $this->actingAs($provider, 'sanctum')
        ->postJson("/api/v1/service-requests/{$request->id}/offers", [
            'fee' => 80,
            'eta_minutes' => 15,
        ])
        ->assertForbidden();
});

test('a customer accepting an offer rejects the others and locks in the provider', function () {
    $customer = User::factory()->create();
    $request = ServiceRequest::factory()->for($customer)->create(['service_type' => ServiceType::TireExchange]);

    $offerA = ServiceOffer::factory()->for($request, 'serviceRequest')->create();
    $offerB = ServiceOffer::factory()->for($request, 'serviceRequest')->create();

    $this->actingAs($customer, 'sanctum')
        ->postJson("/api/v1/offers/{$offerA->id}/accept")
        ->assertOk()
        ->assertJsonPath('data.status', 'accepted')
        ->assertJsonPath('data.accepted_provider.id', $offerA->provider_id);

    expect($request->fresh()->status)->toBe(ServiceRequestStatus::Accepted)
        ->and($offerA->fresh()->status)->toBe(ServiceOfferStatus::Accepted)
        ->and($offerB->fresh()->status)->toBe(ServiceOfferStatus::Rejected);
});

test('only the requesting customer can accept an offer', function () {
    $request = ServiceRequest::factory()->create();
    $offer = ServiceOffer::factory()->for($request, 'serviceRequest')->create();
    $stranger = User::factory()->create();

    $this->actingAs($stranger, 'sanctum')
        ->postJson("/api/v1/offers/{$offer->id}/accept")
        ->assertForbidden();
});

test('an offer cannot be accepted twice', function () {
    $customer = User::factory()->create();
    $request = ServiceRequest::factory()->for($customer)->accepted()->create();
    $offer = ServiceOffer::factory()->for($request, 'serviceRequest')->accepted()->create();

    $this->actingAs($customer, 'sanctum')
        ->postJson("/api/v1/offers/{$offer->id}/accept")
        ->assertForbidden();
});
