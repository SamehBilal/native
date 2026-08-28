<?php

namespace App\Http\Resources;

use App\Models\ServiceMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ServiceMessage
 */
class ServiceMessageResource extends JsonResource
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
            'sender_id' => $this->sender_id,
            'body' => $this->body,
            'created_at' => $this->created_at,
        ];
    }
}
