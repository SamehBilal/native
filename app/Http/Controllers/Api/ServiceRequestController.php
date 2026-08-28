<?php

namespace App\Http\Controllers\Api;

use App\Geo;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreServiceRequestRequest;
use App\Http\Requests\Api\UpdateLocationRequest;
use App\Http\Resources\ProviderResource;
use App\Http\Resources\ServiceRequestResource;
use App\Models\Provider;
use App\Models\ServiceRequest;
use App\ServiceRequestStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceRequestController extends Controller
{
    public function store(StoreServiceRequestRequest $request): ServiceRequestResource
    {
        $data = $request->validated();

        $serviceRequest = $request->user()->serviceRequests()->create([
            ...$data,
            'status' => ServiceRequestStatus::Pending,
            'customer_latitude' => $data['pickup_latitude'],
            'customer_longitude' => $data['pickup_longitude'],
        ]);

        return new ServiceRequestResource($serviceRequest);
    }

    public function show(Request $request, ServiceRequest $serviceRequest): ServiceRequestResource
    {
        $this->authorize('view', $serviceRequest);

        return new ServiceRequestResource(
            $serviceRequest->load(['user', 'offers.provider.user', 'acceptedProvider.user'])
        );
    }

    /**
     * Available providers offering this request's service, nearest first.
     */
    public function nearbyProviders(Request $request, ServiceRequest $serviceRequest): JsonResponse
    {
        $this->authorize('view', $serviceRequest);

        $providers = Provider::availableNear(
            $serviceRequest->service_type,
            $serviceRequest->pickup_latitude,
            $serviceRequest->pickup_longitude,
        );

        return response()->json([
            'data' => ProviderResource::collection($providers),
        ]);
    }

    /**
     * The current position of both parties for the shared tracking map.
     */
    public function track(Request $request, ServiceRequest $serviceRequest): JsonResponse
    {
        $this->authorize('view', $serviceRequest);

        $serviceRequest->loadMissing('acceptedProvider');

        $provider = $serviceRequest->acceptedProvider;

        return response()->json([
            'status' => $serviceRequest->status,
            'customer' => [
                'latitude' => $serviceRequest->customer_latitude,
                'longitude' => $serviceRequest->customer_longitude,
            ],
            'provider' => $provider ? [
                'latitude' => $provider->latitude,
                'longitude' => $provider->longitude,
            ] : null,
            'distance_km' => $provider
                ? round(Geo::distanceKm(
                    $serviceRequest->customer_latitude,
                    $serviceRequest->customer_longitude,
                    $provider->latitude,
                    $provider->longitude,
                ), 2)
                : null,
        ]);
    }

    /**
     * The customer's live position, pushed while a request is active.
     */
    public function updateLocation(UpdateLocationRequest $request, ServiceRequest $serviceRequest): ServiceRequestResource
    {
        $this->authorize('update', $serviceRequest);

        $serviceRequest->update([
            'customer_latitude' => $request->float('latitude'),
            'customer_longitude' => $request->float('longitude'),
        ]);

        return new ServiceRequestResource($serviceRequest);
    }
}
