<?php

/**
 * Cahya Audio — Blockchain Verification & Audit Trail Dashboard (Live RPC)
 */

require_once __DIR__ . '/laravel/app/Services/BlockchainService.php';

use App\Services\BlockchainService;

$rpcUrl = 'http://127.0.0.1:7545';
$deployedFile = __DIR__ . '/deployed-contract.json';
$contractAddress = '0xBDa512B71a3bd723661A0b286bdDf44112531e90';

if (file_exists($deployedFile)) {
    $deployData = json_decode(file_get_contents($deployedFile), true);
    if (!empty($deployData['address'])) {
        $contractAddress = $deployData['address'];
    }
}

$service = new BlockchainService($rpcUrl, $contractAddress);

$nodeStatus = 'Offline';
$latestBlock = 0;
$transactions = [];

try {
    $latestBlock = $service->getLatestBlockNumber();
    $nodeStatus = 'Connected (Ganache)';

    // Baca seluruh riwayat transaksi dari blok-blok yang sudah di-mining
    for ($b = $latestBlock; $b >= 1; $b--) {
        $blockData = $service->callRpc('eth_getBlockByNumber', ['0x' . dechex($b), true]);
        if (!empty($blockData['transactions'])) {
            foreach ($blockData['transactions'] as $tx) {
                $txHash = is_array($tx) ? $tx['hash'] : $tx;
                $receipt = $service->getTransactionReceipt($txHash);

                $statusText = 'Verified (0x1)';
                if ($receipt && $receipt['status'] === '0x0') {
                    $statusText = 'Failed (Out of Gas / Revert)';
                }

                $transactions[] = [
                    'block' => $b,
                    'tx_hash' => $txHash,
                    'from' => is_array($tx) ? $tx['from'] : ($receipt['from'] ?? '—'),
                    'to' => is_array($tx) ? ($tx['to'] ?? 'Contract Deploy') : ($receipt['to'] ?? 'Contract Deploy'),
                    'gas_used' => $receipt ? hexdec($receipt['gasUsed']) : 0,
                    'status' => $statusText,
                    'is_success' => $receipt && $receipt['status'] === '0x1',
                ];
            }
        }
    }
} catch (Exception $e) {
    $nodeStatus = 'Error: ' . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cahya Audio — Dashboard Verifikasi Audit Trail Blockchain</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Lora:wght@600&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0E1A17;
            --surface: #15241F;
            --surface-2: #1B2B25;
            --ink: #EAE7DB;
            --muted: #9CA79E;
            --border: #263A32;
            --primary: #6FBFA9;
            --accent: #D9A94D;
            --success: #2E7D5B;
            --danger: #E0825E;
        }
        body {
            margin: 0;
            background: var(--bg);
            color: var(--ink);
            font-family: 'Inter', system-ui, sans-serif;
            padding: 32px 24px;
        }
        .container { max-width: 1200px; margin: 0 auto; }
        header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 28px; border-bottom: 1px solid var(--border); padding-bottom: 20px;
        }
        .brand { font-family: 'Lora', serif; font-size: 22px; display: flex; align-items: center; gap: 10px; }
        .dot { width: 8px; height: 8px; border-radius: 50%; background: var(--accent); }
        .nav-back {
            color: var(--primary); text-decoration: none; font-size: 13.5px;
            padding: 8px 16px; border: 1px solid var(--border); border-radius: 8px;
            background: var(--surface-2);
        }
        .nav-back:hover { background: var(--border); }

        .stats-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 16px; margin-bottom: 32px;
        }
        .stat-card {
            background: var(--surface); border: 1px solid var(--border);
            border-radius: 12px; padding: 20px;
        }
        .stat-card .label { font-size: 11.5px; color: var(--muted); text-transform: uppercase; letter-spacing: 0.05em; }
        .stat-card .val {
            font-size: 22px; font-weight: 600; font-family: 'JetBrains Mono', monospace;
            margin-top: 8px; word-break: break-all;
        }
        .badge {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 4px 10px; border-radius: 999px; font-size: 11.5px; font-weight: 600;
        }
        .badge.online { background: rgba(46, 125, 91, 0.2); color: #6FBFA9; border: 1px solid var(--success); }
        .badge.verified { background: rgba(46, 125, 91, 0.2); color: #6FBFA9; border: 1px solid var(--success); }
        .badge.failed { background: rgba(224, 130, 94, 0.2); color: var(--danger); border: 1px solid var(--danger); }

        .table-card {
            background: var(--surface); border: 1px solid var(--border);
            border-radius: 14px; overflow: hidden;
        }
        .table-header {
            padding: 18px 24px; border-bottom: 1px solid var(--border);
            display: flex; justify-content: space-between; align-items: center;
        }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th, td { padding: 14px 20px; text-align: left; border-bottom: 1px solid var(--border); }
        th { background: var(--surface-2); color: var(--muted); font-size: 11px; text-transform: uppercase; }
        tr:hover { background: rgba(255, 255, 255, 0.02); }
        .mono { font-family: 'JetBrains Mono', monospace; font-size: 12.5px; }
    </style>
</head>
<body>

<div class="container">
    <header>
        <div class="brand">
            <span class="dot"></span>
            Cahya Audio — Dashboard Verifikasi Audit Trail
        </div>
        <a href="/" class="nav-back">← Kembali ke Halaman Pesanan</a>
    </header>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="label">Node Blockchain</div>
            <div class="val"><span class="badge online"><?= htmlspecialchars($nodeStatus) ?></span></div>
        </div>
        <div class="stat-card">
            <div class="label">Block Terkini di Ganache</div>
            <div class="val" style="color: var(--accent);">#<?= number_format($latestBlock) ?></div>
        </div>
        <div class="stat-card">
            <div class="label">Smart Contract CahyaAudioOrder</div>
            <div class="val mono" style="font-size: 13px;">
                <?= substr($contractAddress, 0, 10) . '…' . substr($contractAddress, -6) ?>
            </div>
        </div>
        <div class="stat-card">
            <div class="label">Total Bukti Transaksi</div>
            <div class="val"><?= count($transactions) ?> Transaksi On-Chain</div>
        </div>
    </div>

    <div class="table-card">
        <div class="table-header">
            <strong style="font-size: 15px;">Daftar Bukti Transaksi Nyata On-Chain (Audit Trail)</strong>
            <span style="color: var(--muted); font-size: 13px;">Tercatat permanen di blockchain Ganache</span>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Block</th>
                    <th>Transaction Hash (Tx)</th>
                    <th>Dari (Pengirim / Pelanggan)</th>
                    <th>Tujuan (Smart Contract)</th>
                    <th>Gas Digunakan</th>
                    <th>Status Validasi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transactions)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; color: var(--muted); padding: 32px;">
                            Belum ada riwayat transaksi yang terdeteksi.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($transactions as $t): ?>
                        <tr>
                            <td class="mono"><strong>#<?= $t['block'] ?></strong></td>
                            <td class="mono">
                                <span title="<?= htmlspecialchars($t['tx_hash']) ?>" style="color: var(--primary);">
                                    <?= substr($t['tx_hash'], 0, 10) . '…' . substr($t['tx_hash'], -8) ?>
                                </span>
                            </td>
                            <td class="mono">
                                <span title="<?= htmlspecialchars($t['from']) ?>">
                                    <?= substr($t['from'], 0, 8) . '…' . substr($t['from'], -6) ?>
                                </span>
                            </td>
                            <td class="mono">
                                <span title="<?= htmlspecialchars($t['to']) ?>">
                                    <?= is_null($t['to']) || $t['to'] === 'Contract Deploy' ? '<em>Contract Deployment</em>' : substr($t['to'], 0, 8) . '…' . substr($t['to'], -6) ?>
                                </span>
                            </td>
                            <td class="mono"><?= number_format($t['gas_used']) ?> gas</td>
                            <td>
                                <?php if ($t['is_success']): ?>
                                    <span class="badge verified">✓ <?= $t['status'] ?></span>
                                <?php else: ?>
                                    <span class="badge failed">❌ <?= $t['status'] ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>

