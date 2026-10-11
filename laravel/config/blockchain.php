<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Konfigurasi Node Blockchain JSON-RPC (Ganache)
    |--------------------------------------------------------------------------
    */
    'rpc_url' => env('BLOCKCHAIN_RPC_URL', env('GANACHE_RPC_URL', 'http://127.0.0.1:7545')),
    'chain_id' => (int) env('BLOCKCHAIN_CHAIN_ID', env('GANACHE_CHAIN_ID', 1337)),
    'contract_address' => env('CONTRACT_ADDRESS', '0x5FbDB2315678afecb367f032d93F642f64180aa3'),

    /*
    |--------------------------------------------------------------------------
    | Kunci Privat Admin Bengkel Cahya Audio
    |--------------------------------------------------------------------------
    | Digunakan untuk menandatangani transaksi status update secara otomatis
    | jika dieksekusi dari sisi backend. JANGAN PERNAH diekspos ke frontend.
    */
    'admin_private_key' => env('PRIVATE_KEY'),
];

