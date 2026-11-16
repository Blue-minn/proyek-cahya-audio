const hre = require("hardhat");
const fs = require("fs");
const path = require("path");

// Deploy HSportOrder (Order Core) secara terpisah dari CahyaAudioOrder.
async function main() {
  console.log("Memulai deployment HSportOrder ke jaringan:", hre.network.name);

  const Factory = await hre.ethers.getContractFactory("HSportOrder");
  const contract = await Factory.deploy();
  await contract.waitForDeployment();
  const address = await contract.getAddress();

  console.log("✓ HSportOrder berhasil di-deploy:", address);
  console.log("  Isi HSPORT_ORDER_ADDRESS di .env dengan alamat di atas, lalu jalankan: npm run interact:hsport");

  const artifact = await hre.artifacts.readArtifact("HSportOrder");
  fs.writeFileSync(
    path.join(__dirname, "..", "HSportOrder_ABI.json"),
    JSON.stringify(artifact.abi, null, 2)
  );

  const logContent = `[${new Date().toISOString()}] Contract: HSportOrder | Address: ${address} | Network: ${hre.network.name}\n`;
  fs.appendFileSync(path.join(__dirname, "..", "deployment-log.txt"), logContent);
}

main().catch((error) => {
  console.error("Terjadi kesalahan saat deployment HSportOrder:", error);
  process.exitCode = 1;
});
