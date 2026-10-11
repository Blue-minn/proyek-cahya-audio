const hre = require("hardhat");
const fs = require("fs");
const path = require("path");

async function main() {
  console.log("==================================================");
  console.log("Memulai deployment smart contract CahyaAudioOrder...");
  console.log("Jaringan target:", hre.network.name);
  console.log("==================================================");

  const CahyaAudioOrder = await hre.ethers.getContractFactory("CahyaAudioOrder");
  const contract = await CahyaAudioOrder.deploy();

  await contract.waitForDeployment();

  const contractAddress = await contract.getAddress();

  console.log("--------------------------------------------------");
  console.log("✓ Smart Contract CahyaAudioOrder berhasil di-deploy!");
  console.log("Contract Address:", contractAddress);
  console.log("--------------------------------------------------");

  // Buat contoh order awal untuk demo pengujian
  try {
    const sampleOrderCode = "CA-20261011-0001";
    const sampleAmount = 4500000; // Rp 4.500.000
    const sampleHash = hre.ethers.keccak256(hre.ethers.toUtf8Bytes(sampleOrderCode + "-demo-order"));
    
    console.log(`Mendaftarkan contoh order inisial: ${sampleOrderCode}...`);
    const tx = await contract.createOrder(sampleOrderCode, sampleAmount, sampleHash);
    await tx.wait();
    console.log(`✓ Contoh order ${sampleOrderCode} berhasil dicatat on-chain! Tx: ${tx.hash}`);
  } catch (err) {
    console.log("Catatan: Inisialisasi contoh order dilewati:", err.message);
  }

  // Ambil artifact ABI untuk disimpan
  const artifact = await hre.artifacts.readArtifact("CahyaAudioOrder");

  const deployedInfo = {
    contractName: "CahyaAudioOrder",
    address: contractAddress,
    network: hre.network.name,
    chainId: hre.network.config.chainId || 1337,
    deployedAt: new Date().toISOString(),
    abi: artifact.abi
  };

  // Simpan data deployment ke JSON agar bisa dibaca langsung oleh Frontend dan Laravel
  fs.writeFileSync(
    path.join(__dirname, "..", "deployed-contract.json"),
    JSON.stringify(deployedInfo, null, 2)
  );

  // Catat riwayat ke deployment-log.txt
  const logContent = `[${new Date().toISOString()}] Contract: CahyaAudioOrder | Address: ${contractAddress} | Network: ${hre.network.name}\n`;
  fs.appendFileSync(path.join(__dirname, "..", "deployment-log.txt"), logContent);

  console.log("✓ Informasi contract dan ABI disimpan ke deployed-contract.json");
}

main().catch((error) => {
  console.error("Terjadi kesalahan saat deployment:", error);
  process.exitCode = 1;
});