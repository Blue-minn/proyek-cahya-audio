<?php

namespace App\Http\Controllers;

use App\Models\BlockchainAuditTrail;
use App\Models\Order;
use App\Services\BlockchainService;
use Illuminate\View\View;

class BlockchainDashboardController extends Controller
{
    /**
     * Menampilkan visualisasi audit trail transaksi dan status verifikasi sistem
     */
    public function index(BlockchainService $blockchainService): View
    {
        $auditTrails = BlockchainAuditTrail::with('order')
            ->latest()
            ->paginate(15);

        $totalOrders = Order::count();
        $verifiedAudits = BlockchainAuditTrail::where('is_verified', true)->count();

        $systemInfo = [
            'node_status'      => 'Offline',
            'latest_block'     => 0,
            'contract_address' => $blockchainService->getContractAddress(),
            'total_orders'     => $totalOrders,
            'verified_audits'  => $verifiedAudits,
        ];

        try {
            $systemInfo['latest_block'] = $blockchainService->getLatestBlockNumber();
            $systemInfo['node_status']  = 'Connected (Ganache)';
        } catch (\Exception $e) {
            $systemInfo['node_status']  = 'Error: ' . $e->getMessage();
        }

        return view('blockchain.dashboard', compact('auditTrails', 'systemInfo'));
    }
}

