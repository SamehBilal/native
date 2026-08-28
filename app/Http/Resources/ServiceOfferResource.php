<?php

namespace App\Http\Resources;

use App\Models\ServiceOffer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ServiceOffer
 */
class ServiceOfferResource extends JsonResource
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
            'service_request_id' => $this->service_request_id,
            'provider' => new ProviderResource($this->whenLoaded('provider')),
            'fee' => $this->fee,
            'eta_minutes' => $this->eta_minutes,
            'message' => $this->message,
            'status' => $this->status,
            'created_at' => $this->created_at,
        ];
    }
}
