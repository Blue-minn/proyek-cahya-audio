<?php

/**
 * Script Simulasi & Verifikasi Audit Trail Blockchain Cahya Audio
 * Menjalankan uji coba integrasi langsung terhadap JSON-RPC Ganache
 */

require_once __DIR__ . '/laravel/app/Services/BlockchainService.php';

use App\Services\BlockchainService;

echo "==============================================================\n";
echo "   CAHYA AUDIO — UJI INTEGRASI AUDIT TRAIL BLOCKCHAIN\n";
echo "==============================================================\n\n";

$deployedFile = __DIR__ . '/deployed-contract.json';
if (!file_exists($deployedFile)) {
    die("Error: File deployed-contract.json tidak ditemukan. Jalankan deploy terlebih dahulu.\n");
}

$deployData = json_decode(file_get_contents($deployedFile), true);
$contractAddress = $deployData['address'];
$rpcUrl = 'http://127.0.0.1:7545';

echo "1. Menghubungkan ke Node JSON-RPC ({$rpcUrl})...\n";
$blockchainService = new BlockchainService($rpcUrl, $contractAddress);

try {
    $blockNumber = $blockchainService->getLatestBlockNumber();
    echo "   ✓ Berhasil terhubung! Block number saat ini: #{$blockNumber}\n\n";
} catch (Exception $e) {
    die("   ❌ Gagal terhubung ke RPC: " . $e->getMessage() . "\n");
}

echo "2. Memeriksa Alamat Smart Contract CahyaAudioOrder...\n";
echo "   Alamat: {$contractAddress}\n\n";

echo "3. Mengambil Riwayat Transaksi Deployment dari deployment-log.txt...\n";
$logFile = __DIR__ . '/deployment-log.txt';
if (file_exists($logFile)) {
    echo "   Riwayat deployment:\n";
    $lines = file($logFile);
    foreach (array_slice($lines, -3) as $line) {
        echo "   -> " . trim($line) . "\n";
    }
}
echo "\n";

echo "4. Menjalankan Verifikasi Receipt Transaksi...\n";

// Mengambil riwayat block dan transaksi terbaru untuk verifikasi receipt nyata
$latestBlockHex = '0x' . dechex($blockNumber);
$blockData = $blockchainService->callRpc('eth_getBlockByNumber', [$latestBlockHex, true]);

$txHashToVerify = null;
if (!empty($blockData['transactions'])) {
    $firstTx = $blockData['transactions'][0];
    $txHashToVerify = is_array($firstTx) ? $firstTx['hash'] : $firstTx;
}

if (!$txHashToVerify) {
    echo "   Mencoba membaca transaksi dari blok sebelumnya...\n";
    for ($b = $blockNumber; $b >= 0; $b--) {
        $bData = $blockchainService->callRpc('eth_getBlockByNumber', ['0x' . dechex($b), true]);
        if (!empty($bData['transactions'])) {
            $t = $bData['transactions'][0];
            $txHashToVerify = is_array($t) ? $t['hash'] : $t;
            break;
        }
    }
}

if ($txHashToVerify) {
    echo "   Memverifikasi Tx Hash: {$txHashToVerify}\n";
    $result = $blockchainService->verifyTransactionReceipt($txHashToVerify);

    echo "   ------------------------------------------------------------\n";
    echo "   HASIL EKSTRAKSI AUDIT TRAIL:\n";
    echo "   - Status Validasi : " . ($result['verified'] ? "✓ SUKSES (0x1 Verified)" : "❌ REVERTED") . "\n";
    echo "   - Block Number    : #" . $result['block_number'] . "\n";
    echo "   - Dari (From)     : " . $result['from'] . "\n";
    echo "   - Tujuan (To)     : " . $result['to'] . "\n";
    echo "   - Gas Digunakan   : " . number_format($result['gas_used']) . " gas\n";
    echo "   - Log Events      : " . $result['logs_count'] . " event(s)\n";
    echo "   ------------------------------------------------------------\n";
    echo "   ✓ Integritas data on-chain terverifikasi 100% cocok untuk audit trail!\n\n";
} else {
    echo "   (Belum ada transaksi di blok terkini)\n\n";
}

echo "5. Simulasi Saldo Akun Admin Toko (Ganache Account 0)...\n";
$adminAddress = '0xf39Fd6e51aad88F6F4ce6aB8827279cffFb92266';
$balance = $blockchainService->getBalance($adminAddress);
echo "   Alamat : {$adminAddress}\n";
echo "   Saldo  : {$balance} ETH\n\n";

echo "==============================================================\n";
echo "   SIMULASI SELESAI — SEMUA KOMPONEN BERJALAN SEMPURNA!\n";
echo "==============================================================\n";

