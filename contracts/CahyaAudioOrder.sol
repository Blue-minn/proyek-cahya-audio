// SPDX-License-Identifier: MIT
pragma solidity ^0.8.24;

/**
 * @title CahyaAudioOrder
 * @dev Smart Contract untuk pencatatan dan audit trail pesanan kustom audio mobil Cahya Audio.
 *      - Data pribadi pelanggan TIDAK disimpan on-chain, hanya hash-nya (dataHash).
 *      - Perubahan status hanya boleh dilakukan operator/admin bengkel.
 *      - Urutan status divalidasi: Created -> Paid -> InProcess -> Completed,
 *        dan Cancelled hanya dari Created, Paid, atau InProcess.
 */
contract CahyaAudioOrder {
    address public owner;

    enum OrderStatus { Created, Paid, InProcess, Completed, Cancelled }

    struct OrderRecord {
        string orderCode;
        address customerWallet;
        uint256 totalAmount;
        OrderStatus status;
        bytes32 dataHash;
        uint256 createdAt;
        uint256 updatedAt;
    }

    // Mapping orderCode => OrderRecord
    mapping(string => OrderRecord) public orders;
    string[] public orderCodes;

    // Daftar operator (admin/teknisi bengkel) yang boleh mengubah status
    mapping(address => bool) private operators;

    event OrderCreated(
        string indexed orderCode,
        address indexed customer,
        uint256 totalAmount,
        bytes32 dataHash,
        uint256 timestamp
    );

    event OrderStatusUpdated(
        string indexed orderCode,
        OrderStatus indexed status,
        uint256 timestamp
    );

    event OperatorAdded(address indexed account);
    event OperatorRemoved(address indexed account);

    modifier onlyOwner() {
        require(msg.sender == owner, "Hanya pemilik/admin yang diizinkan");
        _;
    }

    modifier onlyOperator() {
        require(operators[msg.sender], "Hanya operator bengkel yang diizinkan");
        _;
    }

    modifier orderExists(string memory _orderCode) {
        require(orders[_orderCode].customerWallet != address(0), "Order tidak ditemukan");
        _;
    }

    constructor() {
        owner = msg.sender;
        operators[msg.sender] = true;
        emit OperatorAdded(msg.sender);
    }

    // ------------------------------------------------------------------
    // Manajemen operator
    // ------------------------------------------------------------------

    /// @notice Menambahkan akun operator (admin/teknisi bengkel).
    function addOperator(address _account) external onlyOwner {
        require(_account != address(0), "Alamat operator tidak valid");
        require(!operators[_account], "Akun sudah menjadi operator");
        operators[_account] = true;
        emit OperatorAdded(_account);
    }

    /// @notice Mencabut hak operator dari sebuah akun.
    function removeOperator(address _account) external onlyOwner {
        require(_account != owner, "Owner tidak bisa dicabut");
        require(operators[_account], "Akun bukan operator");
        operators[_account] = false;
        emit OperatorRemoved(_account);
    }

    /// @notice Mengecek apakah sebuah akun adalah operator.
    function isOperator(address _account) external view returns (bool) {
        return operators[_account];
    }

    // ------------------------------------------------------------------
    // Order
    // ------------------------------------------------------------------

    /**
     * @dev Mencatat order baru ke dalam blockchain.
     *      Wallet pemanggil dicatat sebagai pemilik order (customerWallet).
     */
    function createOrder(
        string memory _orderCode,
        uint256 _totalAmount,
        bytes32 _dataHash
    ) external {
        require(bytes(_orderCode).length > 0, "Kode order wajib diisi");
        require(_totalAmount > 0, "Total harus lebih dari 0");
        require(_dataHash != bytes32(0), "Hash data wajib diisi");
        require(orders[_orderCode].customerWallet == address(0), "Order ID sudah ada");

        orders[_orderCode] = OrderRecord({
            orderCode: _orderCode,
            customerWallet: msg.sender,
            totalAmount: _totalAmount,
            status: OrderStatus.Created,
            dataHash: _dataHash,
            createdAt: block.timestamp,
            updatedAt: block.timestamp
        });

        orderCodes.push(_orderCode);

        emit OrderCreated(_orderCode, msg.sender, _totalAmount, _dataHash, block.timestamp);
    }

    /**
     * @dev Memperbarui status pesanan on-chain. Hanya operator bengkel,
     *      dan hanya mengikuti urutan status yang sah.
     */
    function updateStatus(string memory _orderCode, OrderStatus _status)
        external
        onlyOperator
        orderExists(_orderCode)
    {
        _setStatus(_orderCode, _status);
    }

    /**
     * @dev Pelanggan dapat membatalkan pesanan miliknya sendiri selama
     *      pesanan masih berstatus Created (belum dibayar).
     */
    function cancelOrder(string memory _orderCode) external orderExists(_orderCode) {
        OrderRecord storage o = orders[_orderCode];
        require(msg.sender == o.customerWallet, "Hanya pemilik order yang bisa membatalkan");
        require(o.status == OrderStatus.Created, "Order sudah diproses, hubungi bengkel");
        _setStatus(_orderCode, OrderStatus.Cancelled);
    }

    /**
     * @dev Mengecek apakah perpindahan status dari `_from` ke `_to` diizinkan.
     */
    function isValidTransition(OrderStatus _from, OrderStatus _to) public pure returns (bool) {
        if (_to == OrderStatus.Cancelled) {
            return _from == OrderStatus.Created || _from == OrderStatus.Paid || _from == OrderStatus.InProcess;
        }
        if (_from == OrderStatus.Created) return _to == OrderStatus.Paid;
        if (_from == OrderStatus.Paid) return _to == OrderStatus.InProcess;
        if (_from == OrderStatus.InProcess) return _to == OrderStatus.Completed;
        return false; // Completed dan Cancelled adalah status akhir
    }

    function _setStatus(string memory _orderCode, OrderStatus _status) internal {
        OrderRecord storage o = orders[_orderCode];
        require(isValidTransition(o.status, _status), "Perubahan status tidak valid");

        o.status = _status;
        o.updatedAt = block.timestamp;

        emit OrderStatusUpdated(_orderCode, _status, block.timestamp);
    }

    /**
     * @dev Mengambil detail pesanan berdasarkan orderCode
     */
    function getOrder(string memory _orderCode) external view returns (
        address customerWallet,
        uint256 totalAmount,
        OrderStatus status,
        bytes32 dataHash,
        uint256 createdAt,
        uint256 updatedAt
    ) {
        OrderRecord memory o = orders[_orderCode];
        require(o.customerWallet != address(0), "Order tidak ditemukan");
        return (o.customerWallet, o.totalAmount, o.status, o.dataHash, o.createdAt, o.updatedAt);
    }

    /**
     * @dev Mendapatkan total order terdaftar
     */
    function totalOrders() external view returns (uint256) {
        return orderCodes.length;
    }
}
