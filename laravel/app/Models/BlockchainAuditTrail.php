<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlockchainAuditTrail extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'tx_hash',
        'block_number',
        'from_address',
        'to_address',
        'event_name',
        'onchain_status',
        'gas_used',
        'raw_receipt',
        'is_verified',
        'verified_at',
    ];

    protected $casts = [
        'raw_receipt' => 'array',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'block_number' => 'integer',
        'gas_used' => 'integer',
    ];

    /**
     * Relasi ke data Order lokal
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}

