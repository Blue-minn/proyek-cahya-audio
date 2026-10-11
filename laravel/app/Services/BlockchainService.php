<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;

/**
 * Class BlockchainService
 * Jembatan integrasi JSON-RPC antara Laravel dan Smart Contract di Ganache.
 */
class BlockchainService
{
    protected string $rpcUrl;
    protected string $contractAddress;
    protected int $chainId;

    public function __construct(?string $rpcUrl = null, ?string $contractAddress = null, ?int $chainId = null)
    {
        if ($rpcUrl) {
            $this->rpcUrl = $rpcUrl;
        } elseif (function_exists('config') && config('blockchain.rpc_url')) {
            $this->rpcUrl = config('blockchain.rpc_url');
        } else {
            $this->rpcUrl = getenv('GANACHE_RPC_URL') ?: 'http://127.0.0.1:7545';
        }

        if ($contractAddress) {
            $this->contractAddress = $contractAddress;
        } elseif (function_exists('config') && config('blockchain.contract_address')) {
            $this->contractAddress = config('blockchain.contract_address');
        } else {
            $this->contractAddress = getenv('CONTRACT_ADDRESS') ?: '0x5FbDB2315678afecb367f032d93F642f64180aa3';
        }

        if ($chainId) {
            $this->chainId = $chainId;
        } elseif (function_exists('config') && config('blockchain.chain_id')) {
            $this->chainId = (int) config('blockchain.chain_id');
        } else {
            $this->chainId = (int) (getenv('GANACHE_CHAIN_ID') ?: 1337);
        }
    }

    /**
     * Mengirim payload standard JSON-RPC 2.0 ke node Ganache
     *
     * @param string $method Metode RPC (misal: eth_blockNumber, eth_getTransactionReceipt)
     * @param array $params Parameter untuk metode RPC
     * @return mixed Hasil result dari RPC response
     * @throws Exception
     */
    public function callRpc(string $method, array $params = []): mixed
    {
        $payload = json_encode([
            'jsonrpc' => '2.0',
            'id'      => time() . mt_rand(100, 999),
            'method'  => $method,
            'params'  => $params,
        ]);

        $ch = curl_init($this->rpcUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);

        if ($curlError) {
            $msg = "cURL RPC Error: " . $curlError;
            if (class_exists(Log::class)) { Log::error($msg); }
            throw new Exception($msg);
        }

        if ($httpCode !== 200) {
            $msg = "RPC Endpoint HTTP Status {$httpCode}: {$response}";
            if (class_exists(Log::class)) { Log::error($msg); }
            throw new Exception($msg);
        }

        $decoded = json_decode($response, true);

        if (isset($decoded['error'])) {
            $errorMsg = is_array($decoded['error']) ? ($decoded['error']['message'] ?? json_encode($decoded['error'])) : $decoded['error'];
            throw new Exception("RPC Node Error: " . $errorMsg);
        }

        return $decoded['result'] ?? null;
    }

    /**
     * Mendapatkan nomor blok terbaru (latest block number)
     */
    public function getLatestBlockNumber(): int
    {
        $hex = $this->callRpc('eth_blockNumber');
        return hexdec($hex);
    }

    /**
     * Mengambil Receipt Transaksi lengkap berdasarkan txHash
     */
    public function getTransactionReceipt(string $txHash): ?array
    {
        return $this->callRpc('eth_getTransactionReceipt', [$txHash]);
    }

    /**
     * Mengambil detail transaksi berdasarkan txHash
     */
    public function getTransactionByHash(string $txHash): ?array
    {
        return $this->callRpc('eth_getTransactionByHash', [$txHash]);
    }

    /**
     * Memverifikasi apakah transaksi valid dan berhasil di-mining on-chain
     *
     * @param string $txHash
     * @return array [verified => bool, block_number => int, from => string, to => string, gas_used => int, raw => array]
     */
    public function verifyTransactionReceipt(string $txHash): array
    {
        $receipt = $this->getTransactionReceipt($txHash);

        if (!$receipt) {
            return [
                'verified' => false,
                'message'  => 'Receipt transaksi belum ditemukan di blockchain (pending/unmined).'
            ];
        }

        // Status 0x1 berarti transaksi sukses, 0x0 berarti revert/gagal
        $isSuccess = ($receipt['status'] === '0x1');
        $blockNumber = hexdec($receipt['blockNumber']);
        $gasUsed = hexdec($receipt['gasUsed']);

        return [
            'verified'     => $isSuccess,
            'tx_hash'      => $txHash,
            'block_number' => $blockNumber,
            'from'         => strtolower($receipt['from']),
            'to'           => strtolower($receipt['to'] ?? ''),
            'gas_used'     => $gasUsed,
            'logs_count'   => count($receipt['logs'] ?? []),
            'raw'          => $receipt
        ];
    }

    /**
     * Mengambil saldo ETH akun tertentu (dalam satuan ETH)
     */
    public function getBalance(string $walletAddress): float
    {
        $hexWei = $this->callRpc('eth_getBalance', [$walletAddress, 'latest']);
        $wei = hexdec($hexWei);
        return $wei / 1e18;
    }

    /**
     * Mengambil alamat smart contract yang sedang aktif
     */
    public function getContractAddress(): string
    {
        return $this->contractAddress;
    }
}
