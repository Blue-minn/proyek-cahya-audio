<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_code',
        'customer_name',
        'whatsapp_number',
        'car_model',
        'custom_notes',
        'total_amount',
        'customer_wallet_address',
        'status',
        'data_hash',
    ];

    /**
     * Relasi ke seluruh riwayat audit trail blockchain transaksi ini
     */
    public function auditTrails(): HasMany
    {
        return $this->hasMany(BlockchainAuditTrail::class);
    }

    /**
     * Audit trail terbaru
     */
    public function latestAuditTrail(): HasOne
    {
        return $this->hasOne(BlockchainAuditTrail::class)->latestOfMany();
    }
}

