<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreServiceOfferRequest;
use App\Http\Resources\ServiceOfferResource;
use App\Http\Resources\ServiceRequestResource;
use App\Models\ServiceOffer;
use App\Models\ServiceRequest;
use App\ServiceOfferStatus;
use App\ServiceRequestStatus;
use Illuminate\Support\Facades\DB;

class ServiceOfferController extends Controller
{
    public function store(StoreServiceOfferRequest $request, ServiceRequest $serviceRequest): ServiceOfferResource
    {
        $this->authorize('create', [ServiceOffer::class, $serviceRequest]);

        $offer = $serviceRequest->offers()->create([
            ...$request->validated(),
            'provider_id' => $request->user()->provider->id,
            'status' => ServiceOfferStatus::Pending,
        ]);

        return new ServiceOfferResource($offer->load('provider.user'));
    }

    public function accept(ServiceOffer $offer): ServiceRequestResource
    {
        $this->authorize('accept', $offer);

        $serviceRequest = DB::transaction(function () use ($offer) {
            $offer->serviceRequest()->update([
                'status' => ServiceRequestStatus::Accepted,
                'accepted_provider_id' => $offer->provider_id,
            ]);

            $offer->update(['status' => ServiceOfferStatus::Accepted]);

            $offer->serviceRequest->offers()
                ->whereKeyNot($offer->id)
                ->where('status', ServiceOfferStatus::Pending)
                ->update(['status' => ServiceOfferStatus::Rejected]);

            return $offer->serviceRequest;
        });

        return new ServiceRequestResource($serviceRequest->fresh(['user', 'offers.provider.user', 'acceptedProvider.user']));
    }
}
