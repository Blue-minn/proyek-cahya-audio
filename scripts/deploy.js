const hre = require("hardhat");
const fs = require("fs");

async function main() {
  console.log("Memulai deployment smart contract HSportOrder...");

  const HSportOrder = await hre.ethers.getContractFactory("HSportOrder");
  const hSportOrder = await HSportOrder.deploy();

  await hSportOrder.waitForDeployment();

  const contractAddress = await hSportOrder.getAddress();

  console.log("----------------------------------------");
  console.log("Smart Contract HSportOrder berhasil di-deploy!");
  console.log("Contract Address:", contractAddress);
  console.log("----------------------------------------");

  // Catat ke file deployment-log.txt
  const logContent = `[${new Date().toLocaleString()}] Contract: HSportOrder | Address: ${contractAddress}\n`;
  fs.appendFileSync("deployment-log.txt", logContent);
}

main().catch((error) => {
  console.error(error);
  process.exitCode = 1;
});