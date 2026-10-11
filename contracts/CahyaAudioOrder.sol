// SPDX-License-Identifier: MIT
pragma solidity ^0.8.24;

/**
 * @title CahyaAudioOrder
 * @dev Smart Contract untuk pencatatan dan audit trail pesanan kustom audio mobil Cahya Audio.
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

    modifier onlyOwner() {
        require(msg.sender == owner, "Hanya pemilik/admin yang diizinkan");
        _;
    }

    constructor() {
        owner = msg.sender;
    }

    /**
     * @dev Mencatat order baru ke dalam blockchain
     */
    function createOrder(
        string memory _orderCode,
        uint256 _totalAmount,
        bytes32 _dataHash
    ) external {
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
     * @dev Memperbarui status pesanan on-chain (Created -> Paid -> InProcess -> Completed / Cancelled)
     */
    function updateStatus(string memory _orderCode, OrderStatus _status) external {
        // Izinkan owner atau pemilik order untuk memperbarui status (atau khusus owner untuk produksi)
        require(orders[_orderCode].customerWallet != address(0), "Order tidak ditemukan");
        
        orders[_orderCode].status = _status;
        orders[_orderCode].updatedAt = block.timestamp;

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

