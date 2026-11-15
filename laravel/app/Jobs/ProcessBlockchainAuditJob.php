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

    /** Coba ulang jika receipt belum tersedia (transaksi belum di-mining) */
    public int $tries = 5;
    public int $backoff = 3;

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

        if (!isset($verification['raw'])) {
            // Receipt belum ada: lempar ulang ke antrean (jika memakai queue async)
            $this->release($this->backoff);
            return;
        }

        if (!$verification['verified']) {
            Log::warning("Audit Trail ditolak: Transaksi {$this->txHash} reverted.", [
                'order_code' => $order->order_code
            ]);
            return;
        }

        // Transaksi harus ditujukan ke kontrak CahyaAudioOrder yang aktif
        $contractAddress = strtolower($blockchainService->getContractAddress());
        if ($verification['to'] !== $contractAddress) {
            Log::warning("Audit Trail ditolak: Transaksi {$this->txHash} bukan ke kontrak aktif.", [
                'order_code' => $order->order_code,
                'to'         => $verification['to'],
                'expected'   => $contractAddress,
            ]);
            return;
        }

        // Nama event dan status dibaca dari log receipt (sumber kebenaran on-chain),
        // bukan dari data yang dikirim frontend.
        $event = $blockchainService->decodeOrderEvent($verification['raw']);
        if ($event['event_name'] === null) {
            Log::warning("Audit Trail ditolak: Transaksi {$this->txHash} tidak memancarkan event order.", [
                'order_code' => $order->order_code
            ]);
            return;
        }

        $onchainStatus = $event['status'] ?? $this->intendedStatus;
        if ($onchainStatus !== $this->intendedStatus) {
            Log::notice("Status dari frontend ({$this->intendedStatus}) berbeda dengan event on-chain ({$onchainStatus}); memakai data on-chain.", [
                'order_code' => $order->order_code
            ]);
        }

        // Simpan bukti transaksi ke database lokal
        BlockchainAuditTrail::updateOrCreate(
            ['tx_hash' => $this->txHash],
            [
                'order_id'       => $order->id,
                'block_number'   => $verification['block_number'],
                'from_address'   => $verification['from'],
                'to_address'     => $verification['to'],
                'event_name'     => $event['event_name'],
                'onchain_status' => $onchainStatus,
                'gas_used'       => $verification['gas_used'],
                'raw_receipt'    => $verification['raw'],
                'is_verified'    => true,
                'verified_at'    => now(),
            ]
        );

        // Sinkronisasi status di database pesanan utama
        $order->update([
            'status' => $onchainStatus
        ]);

        Log::info("Audit Trail berhasil disimpan: Order #{$order->order_code} ({$event['event_name']}) pada Block #{$verification['block_number']}");
    }
}
