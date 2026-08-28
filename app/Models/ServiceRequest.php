<?php

namespace App\Models;

use App\Geo;
use App\ServiceRequestStatus;
use App\ServiceType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $accepted_provider_id
 * @property ServiceType $service_type
 * @property ServiceRequestStatus $status
 * @property float $pickup_latitude
 * @property float $pickup_longitude
 * @property float|null $customer_latitude
 * @property float|null $customer_longitude
 * @property string|null $description
 */
class ServiceRequest extends Model
{
    /** @use HasFactory<\Database\Factories\ServiceRequestFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'accepted_provider_id',
        'service_type',
        'status',
        'pickup_latitude',
        'pickup_longitude',
        'customer_latitude',
        'customer_longitude',
        'description',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'service_type' => ServiceType::class,
            'status' => ServiceRequestStatus::class,
            'pickup_latitude' => 'float',
            'pickup_longitude' => 'float',
            'customer_latitude' => 'float',
            'customer_longitude' => 'float',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Provider, $this>
     */
    public function acceptedProvider(): BelongsTo
    {
        return $this->belongsTo(Provider::class, 'accepted_provider_id');
    }

    /**
     * @return HasMany<ServiceOffer, $this>
     */
    public function offers(): HasMany
    {
        return $this->hasMany(ServiceOffer::class);
    }

    /**
     * @return HasMany<ServiceMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ServiceMessage::class);
    }

    public function isAccepted(): bool
    {
        return $this->status === ServiceRequestStatus::Accepted;
    }

    /**
     * @param  Builder<ServiceRequest>  $query
     * @return Builder<ServiceRequest>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', ServiceRequestStatus::Pending);
    }

    /**
     * Pending requests of the given service type within range of the given
     * coordinates, excluding ones the given provider already offered on,
     * ordered nearest-first with `distance_km` populated on each.
     *
     * @return Collection<int, ServiceRequest>
     */
    public static function pendingNear(
        ServiceType $type,
        float $latitude,
        float $longitude,
        int $excludingOffersFromProviderId,
        float $radiusKm = 50,
        int $limit = 20,
    ): Collection {
        return static::query()
            ->pending()
            ->where('service_type', $type)
            ->whereDoesntHave('offers', fn (Builder $query) => $query->where('provider_id', $excludingOffersFromProviderId))
            ->get()
            ->each(function (ServiceRequest $request) use ($latitude, $longitude) {
                $request->distance_km = Geo::distanceKm($latitude, $longitude, $request->pickup_latitude, $request->pickup_longitude);
            })
            ->filter(fn (ServiceRequest $request) => $request->distance_km <= $radiusKm)
            ->sortBy('distance_km')
            ->take($limit)
            ->values();
    }
}
