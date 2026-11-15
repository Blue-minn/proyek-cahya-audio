# Cahya Audio — Sistem Pemesanan & Audit Trail Web3

Sistem integrasi pemesanan dan pembayaran audio mobil **Cahya Audio** berbasis arsitektur **Hybrid Web + Blockchain (Ganache)**. Data pribadi pelanggan tersimpan di database toko (Laravel), sedangkan riwayat transaksi, hash sidik jari pesanan, total biaya, dan status pengerjaan dicatat secara transparan di smart contract blockchain.

---

## 🌟 Fitur Utama
1. **Smart Contract `CahyaAudioOrder` (Solidity):**
   - Pencatatan pesanan on-chain (`createOrder`) dengan validasi kode, total, dan hash data.
   - Pembaruan status (`updateStatus`) **khusus operator bengkel** dengan urutan yang divalidasi: `Created` ➔ `Paid` ➔ `InProcess` ➔ `Completed`; `Cancelled` hanya dari `Created`/`Paid`/`InProcess`.
   - Pelanggan dapat membatalkan pesanannya sendiri selama masih `Created` (`cancelOrder`).
   - Manajemen operator: `addOperator`, `removeOperator`, `isOperator` (deployer otomatis menjadi operator).
   - Event emisi real-time: `OrderCreated`, `OrderStatusUpdated`, `OperatorAdded`, `OperatorRemoved`.
2. **dApp Frontend Berbasis Web3.js:**
   - Wizard alur 4-langkah (Pilih Paket ➔ Koneksi MetaMask ➔ Catat On-Chain ➔ Pantau Pengerjaan).
   - Alamat & ABI kontrak dibaca otomatis dari `deployed-contract.json` (hasil deploy terakhir).
   - Deteksi jaringan otomatis ke Ganache GUI (Chain ID `1337`; Network ID `5777` bukan Chain ID) dengan tombol 1-klik beralih jaringan.
   - Hash data pesanan = `keccak256(abi.encodePacked(kode, nama, WA, mobil, paket, catatan, total))`.
   - Status bar pengerjaan real-time dan kontrol status bengkel (aktif hanya untuk akun operator).
3. **Laravel Blockchain Service & Audit Trail:**
   - `BlockchainService.php`: Integrasi JSON-RPC native ke node Ganache.
   - Ekstraksi receipt transaksi, validasi status `0x1`, alamat tujuan = kontrak aktif, nomor blok, dan gas fee.
   - Nama event dan status audit trail dibaca dari log receipt on-chain (bukan dari data frontend).
   - Verifikasi hash data pesanan (`OrderHasher`, membutuhkan `kornrunner/keccak`).
   - Halaman dashboard verifikasi bukti transaksi on-chain (`dashboard.php`).

---

## 🚀 Panduan Memulai (Quick Start)

### 1. Prasyarat Sistem
* **Node.js** (v18+) & **npm**
* **PHP** (8.1+) & **Composer**
* **Ganache GUI** (Server RPC: `http://127.0.0.1:7545`, Chain ID: `1337`, Default Balance: `100 ETH`)
* **MetaMask** Browser Extension

### 2. Instalasi Dependensi
```bash
git clone https://github.com/Blue-minn/proyek-cahya-audio.git
cd proyek-cahya-audio
npm install
cp .env.example .env
```

### 3. Kompilasi, Uji, & Deploy Smart Contract ke Ganache
Pastikan aplikasi **Ganache GUI** sedang berjalan di port `7545`:
```bash
# Kompilasi smart contract
npx hardhat compile

# Jalankan unit test (jaringan Hardhat in-memory, tidak butuh Ganache)
npm test

# Deploy ke jaringan Ganache
npx hardhat run scripts/deploy.js --network ganache
```
*Hasil deploy akan otomatis menyimpan alamat contract dan ABI ke `deployed-contract.json`. Salin alamatnya ke `CONTRACT_ADDRESS` di `.env` (dipakai Laravel).*

> **Penting:** setiap kali `CahyaAudioOrder.sol` berubah atau Ganache di-reset, jalankan deploy ulang agar `deployed-contract.json` berisi alamat & ABI terbaru.

**Akun operator bengkel:** akun deployer (`PRIVATE_KEY`) otomatis menjadi operator. Impor akun yang sama ke MetaMask untuk menguji tombol "Kontrol Status Bengkel", atau isi `OPERATOR_ADDRESS` di `.env` sebelum deploy untuk menambah operator lain. Akun pelanggan biasa hanya bisa membuat dan membatalkan pesanan berstatus `Created`.

**HSportOrder (Order Core):**
```bash
npm run deploy:hsport      # lalu isi HSPORT_ORDER_ADDRESS di .env
npm run interact:hsport
```

### 4. Menjalankan Antarmuka Web
Jalankan web server lokal:
```bash
php -S 127.0.0.1:8000
```
Buka browser:
* **Halaman Pemesanan Web3**: [http://127.0.0.1:8000](http://127.0.0.1:8000)
* **Dashboard Verifikasi Audit Trail**: [http://127.0.0.1:8000/dashboard.php](http://127.0.0.1:8000/dashboard.php)

### 5. Menjalankan Backend Laravel (API Audit Trail)
Folder `laravel/` berisi file aplikasi saja (tanpa kerangka Laravel). Pasang ke proyek Laravel baru:
```bash
composer create-project laravel/laravel cahya-backend
cd cahya-backend
php artisan install:api                # Laravel 11+: mengaktifkan routes/api.php (jalankan sebelum menyalin)
composer require kornrunner/keccak     # untuk verifikasi hash data pesanan
cp -r ../laravel/app ../laravel/config ../laravel/database ../laravel/resources ../laravel/routes .
# .env: atur DB_*, CONTRACT_ADDRESS, BLOCKCHAIN_RPC_URL=http://127.0.0.1:7545, BLOCKCHAIN_CHAIN_ID=1337, QUEUE_CONNECTION=sync
php artisan migrate
cp ../index.html ../deployed-contract.json public/
php artisan serve                      # buka http://127.0.0.1:8000/index.html
```
Dengan cara ini dApp dan API `/api/blockchain/*` berada di origin yang sama. Jika dApp disajikan dari server lain, isi `API_BASE` di `index.html` dan aktifkan CORS di Laravel.

### 6. Pengujian Integrasi Otomatis (PHP Test)
```bash
php simulate-audit-trail.php
```

---

## 📁 Struktur Berkas Utama
```
proyek-cahya-audio/
├── contracts/
│   ├── CahyaAudioOrder.sol     # Smart contract utama (dipakai dApp & Laravel)
│   ├── HSportOrder.sol          # Order Core (Solidity Developer I)
│   └── umkmOrder.sol            # Wrapper inheritance
├── scripts/
│   ├── deploy.js                # Deploy CahyaAudioOrder
│   ├── deploy-hsport.js         # Deploy HSportOrder
│   └── interact.js              # Uji createOrder/getOrder HSportOrder
├── test/                        # Unit test Hardhat + Chai
├── laravel/
│   ├── app/
│   │   ├── Services/BlockchainService.php       # Jembatan JSON-RPC + decode event
│   │   ├── Services/OrderHasher.php             # Hitung ulang hash data pesanan
│   │   ├── Jobs/ProcessBlockchainAuditJob.php   # Ekstraksi receipt async
│   │   └── Http/Controllers/                   # API & Dashboard Controller
│   ├── database/migrations/                     # Migrasi tabel orders & audit
│   └── resources/views/blockchain/dashboard.blade.php
├── index.html                   # Antarmuka frontend dApp (Web3.js)
├── dashboard.php                # Dashboard audit trail live RPC
├── simulate-audit-trail.php     # Pengujian integrasi mandiri
└── deployed-contract.json       # Metadata & ABI contract yang di-deploy
```

---

## 🛡️ Keamanan & Privasi
* Data kontak dan privasi kendaraan disimpan off-chain di database toko (Laravel) sesuai standar GDPR / UU PDP.
* Kunci privat (*Private Key*) tidak pernah diekspos ke frontend.