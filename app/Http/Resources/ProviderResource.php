<?php

namespace App\Http\Resources;

use App\Models\Provider;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Provider
 */
class ProviderResource extends JsonResource
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
            'name' => $this->user->name,
            'phone' => $this->user->phone,
            'service_types' => $this->service_types,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'distance_km' => $this->when(
                isset($this->distance_km),
                fn () => round($this->distance_km, 2)
            ),
            'is_available' => $this->is_available,
            'rating' => $this->rating,
            'vehicle_info' => $this->vehicle_info,
        ];
    }
}
