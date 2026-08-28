<?php

namespace App\Policies;

use App\Models\ServiceOffer;
use App\Models\ServiceRequest;
use App\Models\User;
use App\ServiceRequestStatus;
use Illuminate\Auth\Access\Response;

class ServiceOfferPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ServiceOffer $serviceOffer): bool
    {
        return false;
    }

    /**
     * Determine whether the provider can submit an offer on the given request.
     */
    public function create(User $user, ServiceRequest $serviceRequest): bool
    {
        $provider = $user->provider;

        return $user->isProvider()
            && $provider !== null
            && $provider->offersService($serviceRequest->service_type)
            && $serviceRequest->status === ServiceRequestStatus::Pending
            && ! $serviceRequest->offers()->where('provider_id', $provider->id)->exists();
    }

    /**
     * Determine whether the customer can accept this offer.
     */
    public function accept(User $user, ServiceOffer $serviceOffer): bool
    {
        return $user->id === $serviceOffer->serviceRequest->user_id
            && $serviceOffer->serviceRequest->status === ServiceRequestStatus::Pending;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ServiceOffer $serviceOffer): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ServiceOffer $serviceOffer): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ServiceOffer $serviceOffer): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ServiceOffer $serviceOffer): bool
    {
        return false;
    }
}
