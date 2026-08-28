<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceOfferResource;
use App\Http\Resources\ServiceRequestResource;
use App\Models\ServiceRequest;
use App\ServiceType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProviderRequestController extends Controller
{
    /**
     * Pending requests near this provider matching what they offer,
     * nearest first, excluding ones they've already offered on.
     */
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->isProvider(), 403);

        $provider = $request->user()->provider;

        $requests = collect();

        foreach ($provider->service_types as $type) {
            $requests = $requests->merge(
                ServiceRequest::pendingNear(
                    ServiceType::from($type),
                    $provider->latitude,
                    $provider->longitude,
                    $provider->id,
                )
            );
        }

        $requests = $requests->unique('id')->sortBy('distance_km')->values();

        return response()->json([
            'data' => ServiceRequestResource::collection($requests),
        ]);
    }

    /**
     * This provider's own submitted offers, across all requests.
     */
    public function offers(Request $request): JsonResponse
    {
        abort_unless($request->user()->isProvider(), 403);

        $offers = $request->user()->provider
            ->offers()
            ->with(['serviceRequest', 'provider.user'])
            ->latest()
            ->get();

        return response()->json([
            'data' => ServiceOfferResource::collection($offers),
        ]);
    }
}
