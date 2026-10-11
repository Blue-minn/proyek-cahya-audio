// SPDX-License-Identifier: MIT
pragma solidity ^0.8.24;

/// @title  HSportOrder - Audit trail order Cahya Audio
/// @notice Bagian ORDER CORE (Solidity Developer I): struktur data order,
///         ownership, daftar operator, createOrder(), getOrder(), isOperator().
/// @dev    Fungsi recordPayment(), updateOrderStatus(), cancelOrder(),
///         addOperator()/removeOperator() dan event lanjutan ditambahkan
///         oleh Solidity Developer II di bagian bawah kontrak ini.
///         Data pribadi pelanggan (nama, no. WA, alamat, detail mobil/item)
///         TIDAK disimpan on-chain; hanya hash-nya lewat externalOrderHash.
contract HSportOrder {
    // ------------------------------------------------------------------
    // Tipe data
    // ------------------------------------------------------------------

    /// @notice Status order sesuai panduan (bagian 4.2).
    /// @dev Urutan enum JANGAN diubah: Laravel membaca status sebagai angka
    ///      (0 = Created, 1 = AwaitingPayment, ..., 7 = Cancelled).
    enum OrderStatus {
        Created,          // 0
        AwaitingPayment,  // 1
        Paid,             // 2
        InProduction,     // 3  (untuk Cahya Audio: proses pemasangan)
        ReadyToShip,      // 4  (mobil siap diserahkan)
        Shipped,          // 5
        Completed,        // 6
        Cancelled         // 7
    }

    /// @notice Data minimum order yang dicatat on-chain.
    struct Order {
        uint256 id;                 // blockchainOrderId, mulai dari 1
        bytes32 externalOrderHash;  // keccak256 data order di MySQL
        address customer;           // wallet yang memanggil createOrder()
        uint256 totalAmount;        // total tagihan dalam Rupiah (tanpa desimal)
        OrderStatus status;         // state order saat ini
        bool paymentRecorded;       // true setelah recordPayment() (Dev II)
        uint256 createdAt;          // block.timestamp saat dibuat
        uint256 updatedAt;          // block.timestamp perubahan terakhir
    }

    // ------------------------------------------------------------------
    // Storage
    // ------------------------------------------------------------------

    /// @notice Alamat deployer; satu-satunya yang boleh mengelola operator.
    address public immutable owner;

    /// @notice Jumlah order yang pernah dibuat (juga ID order terakhir).
    uint256 public orderCount;

    /// @dev orders[id] -> data order. Private agar akses lewat getOrder()
    ///      yang memvalidasi keberadaan order.
    mapping(uint256 => Order) private orders;

    /// @notice orderIdByHash[hash] -> ID order. Nilai 0 berarti hash belum dipakai.
    /// @dev Dipakai untuk mencegah duplicate externalOrderHash dan untuk
    ///      mencari order dari sisi Laravel tanpa mengetahui ID-nya.
    mapping(bytes32 => uint256) public orderIdByHash;

    /// @dev operators[address] -> true jika akun boleh menjalankan fungsi operator.
    mapping(address => bool) internal operators;

    // ------------------------------------------------------------------
    // Event
    // ------------------------------------------------------------------

    /// @notice Dipancarkan setiap order baru berhasil dicatat.
    event OrderCreated(
        uint256 indexed orderId,
        bytes32 indexed externalOrderHash,
        address indexed customer,
        uint256 totalAmount
    );

    // ------------------------------------------------------------------
    // Custom error (lebih hemat gas dan mudah diuji daripada string)
    // ------------------------------------------------------------------

    error NotOwner(address caller);
    error NotOperator(address caller);
    error InvalidAmount();
    error InvalidOrderHash();
    error DuplicateOrderHash(bytes32 externalOrderHash, uint256 existingOrderId);
    error OrderNotFound(uint256 orderId);

    // ------------------------------------------------------------------
    // Modifier (access control dasar)
    // ------------------------------------------------------------------

    /// @dev Hanya owner. Dipakai Dev II untuk addOperator()/removeOperator().
    modifier onlyOwner() {
        if (msg.sender != owner) revert NotOwner(msg.sender);
        _;
    }

    /// @dev Hanya operator/admin toko. Dipakai Dev II untuk recordPayment()
    ///      dan updateOrderStatus().
    modifier onlyOperator() {
        if (!operators[msg.sender]) revert NotOperator(msg.sender);
        _;
    }

    /// @dev Order harus ada: ID 1..orderCount.
    modifier orderExists(uint256 orderId) {
        if (orderId == 0 || orderId > orderCount) revert OrderNotFound(orderId);
        _;
    }


    /// @notice 
    constructor() {
        owner = msg.sender;
        operators[msg.sender] = true;
    }


    /// @notice Mencatat order baru dari checkout Laravel.
    /// @param  externalOrderHash keccak256 dari data order yang disepakati
    ///         (kode_order | id_pelanggan | total | daftar_item | waktu_checkout).
    /// @param  totalAmount Total tagihan dalam Rupiah, harus > 0.
    /// @return orderId ID order on-chain yang baru dibuat.
    /// @dev    Revert: InvalidAmount (total = 0), InvalidOrderHash (hash kosong),
    ///         DuplicateOrderHash (hash sudah pernah dicatat).
    function createOrder(bytes32 externalOrderHash, uint256 totalAmount)
        external
        returns (uint256 orderId)
    {
        if (totalAmount == 0) revert InvalidAmount();
        if (externalOrderHash == bytes32(0)) revert InvalidOrderHash();

        uint256 existingId = orderIdByHash[externalOrderHash];
        if (existingId != 0) revert DuplicateOrderHash(externalOrderHash, existingId);

        orderId = ++orderCount;

        orders[orderId] = Order({
            id: orderId,
            externalOrderHash: externalOrderHash,
            customer: msg.sender,
            totalAmount: totalAmount,
            status: OrderStatus.Created,
            paymentRecorded: false,
            createdAt: block.timestamp,
            updatedAt: block.timestamp
        });
        orderIdByHash[externalOrderHash] = orderId;

        emit OrderCreated(orderId, externalOrderHash, msg.sender, totalAmount);
    }

/// @notice @param  orderId @return Data @dev    
    function getOrder(uint256 orderId)
        external
        view
        orderExists(orderId)
        returns (Order memory)
    {
        return orders[orderId];
    }

    /// @notice  @param account @return true 
    function isOperator(address account) external view returns (bool) {
        return operators[account];
    }
}
