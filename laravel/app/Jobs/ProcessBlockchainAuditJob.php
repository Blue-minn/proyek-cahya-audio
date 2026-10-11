<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\BlockchainAuditTrail;
use App\Services\BlockchainService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessBlockchainAuditJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $orderId,
        public string $txHash,
        public string $intendedStatus
    ) {}

    /**
     * Eksekusi ekstraksi receipt transaksi dan pencatatan audit trail
     */
    public function handle(BlockchainService $blockchainService): void
    {
        $order = Order::findOrFail($this->orderId);

        // Ambil dan verifikasi receipt dari RPC Ganache
        $verification = $blockchainService->verifyTransactionReceipt($this->txHash);

        if (!$verification['verified']) {
            Log::warning("Audit Trail ditolak: Transaksi {$this->txHash} tidak valid/reverted.", [
                'order_code' => $order->order_code
            ]);
            return;
        }

        // Simpan bukti transaksi ke database lokal
        BlockchainAuditTrail::updateOrCreate(
            ['tx_hash' => $this->txHash],
            [
                'order_id'       => $order->id,
                'block_number'   => $verification['block_number'],
                'from_address'   => $verification['from'],
                'to_address'     => $verification['to'],
                'event_name'     => 'OrderStatusUpdated',
                'onchain_status' => $this->intendedStatus,
                'gas_used'       => $verification['gas_used'],
                'raw_receipt'    => $verification['raw'],
                'is_verified'    => true,
                'verified_at'    => now(),
            ]
        );

        // Sinkronisasi status di database pesanan utama
        $order->update([
            'status' => $this->intendedStatus
        ]);

        Log::info("Audit Trail berhasil disimpan: Order #{$order->order_code} pada Block #{$verification['block_number']}");
    }
}

