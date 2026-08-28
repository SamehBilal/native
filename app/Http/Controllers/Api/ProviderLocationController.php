<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdateLocationRequest;
use App\Http\Resources\ProviderResource;

class ProviderLocationController extends Controller
{
    /**
     * Push the authenticated provider's current position, polled
     * periodically by the mobile app while they're available or on a job.
     */
    public function update(UpdateLocationRequest $request): ProviderResource
    {
        abort_unless($request->user()->isProvider(), 403);

        $provider = $request->user()->provider;

        $provider->update([
            'latitude' => $request->float('latitude'),
            'longitude' => $request->float('longitude'),
        ]);

        return new ProviderResource($provider);
    }
}
