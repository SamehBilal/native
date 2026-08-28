<?php

namespace App\Http\Resources;

use App\Models\ServiceRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ServiceRequest
 */
class ServiceRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'customer' => $this->when($this->relationLoaded('user'), fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'phone' => $this->user->phone,
            ]),
            'service_type' => $this->service_type,
            'status' => $this->status,
            'pickup_latitude' => $this->pickup_latitude,
            'pickup_longitude' => $this->pickup_longitude,
            'customer_latitude' => $this->customer_latitude,
            'customer_longitude' => $this->customer_longitude,
            'description' => $this->description,
            'distance_km' => $this->when(
                isset($this->distance_km),
                fn () => round($this->distance_km, 2)
            ),
            'accepted_provider' => new ProviderResource($this->whenLoaded('acceptedProvider')),
            'offers' => ServiceOfferResource::collection($this->whenLoaded('offers')),
            'created_at' => $this->created_at,
        ];
    }
}
