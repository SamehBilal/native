<?php

namespace App\Models;

use App\Geo;
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
 * @property array<int, string> $service_types
 * @property float $latitude
 * @property float $longitude
 * @property bool $is_available
 * @property float $rating
 * @property string|null $vehicle_info
 */
class Provider extends Model
{
    /** @use HasFactory<\Database\Factories\ProviderFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'service_types',
        'latitude',
        'longitude',
        'is_available',
        'rating',
        'vehicle_info',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'service_types' => 'array',
            'latitude' => 'float',
            'longitude' => 'float',
            'is_available' => 'boolean',
            'rating' => 'float',
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
     * @return HasMany<ServiceOffer, $this>
     */
    public function offers(): HasMany
    {
        return $this->hasMany(ServiceOffer::class);
    }

    public function offersService(ServiceType $type): bool
    {
        return in_array($type->value, $this->service_types, strict: true);
    }

    /**
     * Great-circle distance to the given coordinates, in kilometers.
     */
    public function distanceKmTo(float $latitude, float $longitude): float
    {
        return Geo::distanceKm($this->latitude, $this->longitude, $latitude, $longitude);
    }

    /**
     * @param  Builder<Provider>  $query
     * @return Builder<Provider>
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('is_available', true);
    }

    /**
     * @param  Builder<Provider>  $query
     * @return Builder<Provider>
     */
    public function scopeOffering(Builder $query, ServiceType $type): Builder
    {
        return $query->whereJsonContains('service_types', $type->value);
    }

    /**
     * Available providers offering the given service, ordered nearest-first
     * to the given coordinates, with `distance_km` populated on each.
     *
     * @return Collection<int, Provider>
     */
    public static function availableNear(ServiceType $type, float $latitude, float $longitude, int $limit = 20): Collection
    {
        return static::query()
            ->available()
            ->offering($type)
            ->get()
            ->each(fn (Provider $provider) => $provider->distance_km = $provider->distanceKmTo($latitude, $longitude))
            ->sortBy('distance_km')
            ->take($limit)
            ->values();
    }
}
