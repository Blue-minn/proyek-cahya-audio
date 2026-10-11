# Cahya Audio — Sistem Pemesanan & Audit Trail Web3

Sistem integrasi pemesanan dan pembayaran audio mobil **Cahya Audio** berbasis arsitektur **Hybrid Web + Blockchain (Ganache)**. Data pribadi pelanggan tersimpan di database toko (Laravel), sedangkan riwayat transaksi, hash sidik jari pesanan, total biaya, dan status pengerjaan dicatat secara transparan di smart contract blockchain.

---

## 🌟 Fitur Utama
1. **Smart Contract `CahyaAudioOrder` (Solidity):**
   - Pencatatan pesanan on-chain (`createOrder`).
   - Pembaruan status dinamis (`updateStatus`): `Created` ➔ `Paid` ➔ `InProcess` ➔ `Completed` / `Cancelled`.
   - Event emisi real-time: `OrderCreated` & `OrderStatusUpdated`.
2. **dApp Frontend Berbasis Web3.js:**
   - Wizard alur 4-langkah (Pilih Paket ➔ Koneksi MetaMask ➔ Catat On-Chain ➔ Pantau Pengerjaan).
   - Deteksi jaringan otomatis ke Ganache Local (Chain ID `5777` / `1337`) dengan tombol 1-klik beralih jaringan.
   - Status bar pengerjaan real-time dan kontrol simulasi bengkel.
3. **Laravel Blockchain Service & Audit Trail:**
   - `BlockchainService.php`: Integrasi JSON-RPC native ke node Ganache.
   - Ekstraksi receipt transaksi, validasi status `0x1`, nomor blok, dan gas fee.
   - Halaman dashboard verifikasi bukti transaksi on-chain (`dashboard.php`).

---

## 🚀 Panduan Memulai (Quick Start)

### 1. Prasyarat Sistem
* **Node.js** (v18+) & **npm**
* **PHP** (8.1+) & **Composer**
* **Ganache GUI** (Server RPC: `http://127.0.0.1:7545`, Chain ID: `5777` atau `1337`, Default Balance: `100 ETH`)
* **MetaMask** Browser Extension

### 2. Instalasi Dependensi
```bash
git clone https://github.com/Blue-minn/proyek-cahya-audio.git
cd proyek-cahya-audio
npm install
cp .env.example .env
```

### 3. Kompilasi & Deploy Smart Contract ke Ganache
Pastikan aplikasi **Ganache GUI** sedang berjalan di port `7545`:
```bash
# Kompilasi smart contract
npx hardhat compile

# Deploy ke jaringan Ganache
npx hardhat run scripts/deploy.js --network ganache
```
*Hasil deploy akan otomatis menyimpan alamat contract dan ABI ke `deployed-contract.json`.*

### 4. Menjalankan Antarmuka Web
Jalankan web server lokal:
```bash
php -S 127.0.0.1:8000
```
Buka browser:
* **Halaman Pemesanan Web3**: [http://127.0.0.1:8000](http://127.0.0.1:8000)
* **Dashboard Verifikasi Audit Trail**: [http://127.0.0.1:8000/dashboard.php](http://127.0.0.1:8000/dashboard.php)

### 5. Pengujian Integrasi Otomatis (PHP Test)
```bash
php simulate-audit-trail.php
```

---

## 📁 Struktur Berkas Utama
```
proyek-cahya-audio/
├── contracts/
│   ├── CahyaAudioOrder.sol     # Smart contract utama
│   └── umkmOrder.sol            # Wrapper inheritance
├── scripts/
│   └── deploy.js                # Skrip deployment Hardhat
├── laravel/
│   ├── app/
│   │   ├── Services/BlockchainService.php       # Jembatan JSON-RPC
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