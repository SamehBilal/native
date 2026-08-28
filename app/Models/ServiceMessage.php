<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $service_request_id
 * @property int $sender_id
 * @property string $body
 */
class ServiceMessage extends Model
{
    /** @use HasFactory<\Database\Factories\ServiceMessageFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'service_request_id',
        'sender_id',
        'body',
    ];

    /**
     * @return BelongsTo<ServiceRequest, $this>
     */
    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
