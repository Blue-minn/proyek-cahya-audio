<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Jobs\ProcessBlockchainAuditJob;
use App\Services\BlockchainService;
use App\Services\OrderHasher;
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
            'order_code'      => 'required|string|max:64',
            'tx_hash'         => 'required|string|regex:/^0x([A-Fa-f0-9]{64})$/',
            'customer_wallet' => 'required|string|regex:/^0x([A-Fa-f0-9]{40})$/',
            'status'          => 'required|in:Created,Paid,InProcess,Completed,Cancelled',

            // Data pesanan off-chain: wajib saat order dibuat (status Created)
            'customer_name'   => 'required_if:status,Created|nullable|string|max:120',
            'whatsapp_number' => ['required_if:status,Created', 'nullable', 'string', 'regex:/^(\+62|62|0)8[0-9]{7,12}$/'],
            'car_model'       => 'required_if:status,Created|nullable|string|max:120',
            'package_name'    => 'required_if:status,Created|nullable|string|max:150',
            'custom_notes'    => 'nullable|string|max:1000',
            'total_amount'    => 'required_if:status,Created|nullable|integer|min:1',
            'data_hash'       => ['required_if:status,Created', 'nullable', 'string', 'regex:/^0x([A-Fa-f0-9]{64})$/'],
        ]);

        $order = Order::where('order_code', $validated['order_code'])->first();

        if (!$order) {
            if ($validated['status'] !== 'Created') {
                return response()->json([
                    'success' => false,
                    'message' => "Order {$validated['order_code']} belum terdaftar di database toko.",
                ], 404);
            }

            // Verifikasi bahwa hash yang dicatat on-chain memang berasal dari data form ini
            $computedHash = OrderHasher::hash($validated);
            if ($computedHash !== null && strtolower($computedHash) !== strtolower($validated['data_hash'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Hash data pesanan tidak cocok dengan data yang dikirim.',
                ], 422);
            }

            $order = Order::create([
                'order_code'              => $validated['order_code'],
                'customer_name'           => $validated['customer_name'],
                'whatsapp_number'         => $validated['whatsapp_number'],
                'car_model'               => $validated['car_model'],
                'package_name'            => $validated['package_name'],
                'custom_notes'            => $validated['custom_notes'] ?? null,
                'total_amount'            => $validated['total_amount'],
                'customer_wallet_address' => strtolower($validated['customer_wallet']),
                'data_hash'               => strtolower($validated['data_hash']),
                'status'                  => 'Created',
            ]);
        }

        // Dispatch job untuk verifikasi receipt dan pencatatan audit trail.
        // Status final diambil dari event on-chain di dalam job.
        ProcessBlockchainAuditJob::dispatch(
            $order->id,
            $validated['tx_hash'],
            $validated['status']
        );

        return response()->json([
            'success'    => true,
            'message'    => 'Transaksi diterima dan dijadwalkan untuk verifikasi audit trail on-chain.',
            'order_code' => $order->order_code,
            'tx_hash'    => $validated['tx_hash'],
            'hash_checked' => OrderHasher::isAvailable(),
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
                'package_name'    => $order->package_name,
                'total_amount'    => $order->total_amount,
                'status'          => $order->status,
                'customer_wallet' => $order->customer_wallet_address,
            ],
            'audit_trail' => $latestAudit,
            'blockchain_verification' => $onchainVerification
        ]);
    }
}

