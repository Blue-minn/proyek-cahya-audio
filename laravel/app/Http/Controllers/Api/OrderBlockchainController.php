<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Jobs\ProcessBlockchainAuditJob;
use App\Services\BlockchainService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class OrderBlockchainController extends Controller
{
    /**
     * Menerima kiriman Tx Hash dari frontend setelah user bertransaksi di MetaMask
     */
    public function recordTransaction(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_code'      => 'required|string',
            'tx_hash'         => 'required|string|regex:/^0x([A-Fa-f0-9]{64})$/',
            'customer_wallet' => 'required|string|regex:/^0x([A-Fa-f0-9]{40})$/',
            'status'          => 'required|in:Created,Paid,InProcess,Completed,Cancelled',
        ]);

        $order = Order::firstOrCreate(
            ['order_code' => $validated['order_code']],
            [
                'customer_name'   => 'Pelanggan Demo',
                'whatsapp_number' => '081717131489',
                'car_model'       => 'Toyota Innova Zenix',
                'total_amount'    => 4500000,
                'status'          => 'Created',
            ]
        );

        $order->update([
            'customer_wallet_address' => $validated['customer_wallet']
        ]);

        // Dispatch background job untuk verifikasi dan ekstraksi receipt
        ProcessBlockchainAuditJob::dispatch(
            $order->id,
            $validated['tx_hash'],
            $validated['status']
        );

        return response()->json([
            'success' => true,
            'message' => 'Transaksi berhasil diterima dan dijadwalkan untuk audit trail on-chain.',
            'order_code' => $order->order_code,
            'tx_hash' => $validated['tx_hash'],
        ]);
    }

    /**
     * Memeriksa integritas order dan mencocokkan data lokal vs on-chain receipt
     */
    public function verifyOrder(string $orderCode, BlockchainService $blockchainService): JsonResponse
    {
        $order = Order::with('auditTrails')->where('order_code', $orderCode)->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => "Order {$orderCode} tidak ditemukan di database lokal."
            ], 404);
        }

        $latestAudit = $order->auditTrails()->latest()->first();
        $onchainVerification = null;

        if ($latestAudit) {
            $onchainVerification = $blockchainService->verifyTransactionReceipt($latestAudit->tx_hash);
        }

        return response()->json([
            'success' => true,
            'order' => [
                'order_code'      => $order->order_code,
                'customer_name'   => $order->customer_name,
                'car_model'       => $order->car_model,
                'total_amount'    => $order->total_amount,
                'status'          => $order->status,
                'customer_wallet' => $order->customer_wallet_address,
            ],
            'audit_trail' => $latestAudit,
            'blockchain_verification' => $onchainVerification
        ]);
    }
}

