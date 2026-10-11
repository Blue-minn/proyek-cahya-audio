const { ethers } = require("hardhat");
require("dotenv").config();

async function main() {
  const contractAddress = process.env.CONTRACT_ADDRESS;
  console.log("--------------------------------------------------");
  console.log("Menguji interaksi dengan Smart Contract HSportOrder...");
  console.log("Alamat Kontrak:", contractAddress);

  const HSportOrder = await ethers.getContractFactory("HSportOrder");
  const contract = await HSportOrder.attach(contractAddress);

  const [signer] = await ethers.getSigners();
  console.log("Menggunakan Akun Pengirim:", signer.address);

  try {
    console.log("Status: Berhasil terhubung ke smart contract di Ganache!");
    console.log("--------------------------------------------------");
  } catch (error) {
    console.error("Gagal berinteraksi dengan contract:", error.message);
  }
}

main()
  .then(() => process.exit(0))
  .catch((error) => {
    console.error(error);
    process.exit(1);
  });
