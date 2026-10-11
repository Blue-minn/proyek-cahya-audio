const { ethers } = require("hardhat");
require("dotenv").config();

async function main() {
  const contractAddress = process.env.CONTRACT_ADDRESS;
  console.log("--------------------------------------------------");
  console.log("Menguji transaksi pembuatan order pada HSportOrder...");
  console.log("Alamat Kontrak:", contractAddress);

  const HSportOrder = await ethers.getContractFactory("HSportOrder");
  const contract = await HSportOrder.attach(contractAddress);

  const [signer] = await ethers.getSigners();
  console.log("Menggunakan Akun Pengirim:", signer.address);

  // 1. Buat dummy externalOrderHash (misal keccak256 dari 'ORDER-2026-001')
  const orderCode = "ORDER-2026-001";
  const externalOrderHash = ethers.id(orderCode); 
  const totalAmount = 500000; // Rp 500.000

  console.log(`\n1. Memanggil createOrder() dengan kode: ${orderCode}`);
  console.log(`   - Hash: ${externalOrderHash}`);
  console.log(`   - Total Amount: Rp ${totalAmount}`);

  // 2. Eksekusi transaksi createOrder
  const tx = await contract.createOrder(externalOrderHash, totalAmount);
  console.log(`   - Transaction Hash: ${tx.hash}`);
  console.log("   - Menunggu konfirmasi block di Ganache...");
  
  const receipt = await tx.wait();
  console.log(`   - Berhasil di-mined pada Block Number: ${receipt.blockNumber}`);

  // 3. Baca data order dari blockchain via getOrder()
  const currentOrderCount = await contract.orderCount();
  console.log(`\n2. Membaca data Order ID #${currentOrderCount} dari Blockchain...`);

  const orderData = await contract.getOrder(currentOrderCount);
  console.log("   - ID Order On-Chain :", orderData.id.toString());
  console.log("   - Customer Address  :", orderData.customer);
  console.log("   - Total Amount      : Rp", orderData.totalAmount.toString());
  console.log("   - Status Enum       :", orderData.status.toString(), "(0 = Created)");
  console.log("--------------------------------------------------");
}

main()
  .then(() => process.exit(0))
  .catch((error) => {
    console.error("Terjadi Kesalahan:", error.message);
    process.exit(1);
  });
