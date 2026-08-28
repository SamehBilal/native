<?php

namespace App\Models;

use App\ServiceOfferStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $service_request_id
 * @property int $provider_id
 * @property float $fee
 * @property int $eta_minutes
 * @property string|null $message
 * @property ServiceOfferStatus $status
 */
class ServiceOffer extends Model
{
    /** @use HasFactory<\Database\Factories\ServiceOfferFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'service_request_id',
        'provider_id',
        'fee',
        'eta_minutes',
        'message',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ServiceOfferStatus::class,
            'fee' => 'float',
            'eta_minutes' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<ServiceRequest, $this>
     */
    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    /**
     * @return BelongsTo<Provider, $this>
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    /**
     * @param  Builder<ServiceOffer>  $query
     * @return Builder<ServiceOffer>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', ServiceOfferStatus::Pending);
    }
}
