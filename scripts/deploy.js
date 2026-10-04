const hre = require("hardhat");
const fs = require("fs");

async function main() {
  console.log("Memulai deployment smart contract...");

  const umkmOrder = await hre.ethers.deployContract("umkmOrder");
  await umkmOrder.waitForDeployment();
  const contractAddress = await umkmOrder.getAddress();

  console.log("----------------------------------------");
  console.log("Smart Contract berhasil di-deploy!");
  console.log("Contract Address:", contractAddress);
  console.log("----------------------------------------");

  const logData = `Deployed at: ${new Date().toISOString()}\nAddress: ${contractAddress}\nNetwork: Ganache\n\n`;
  fs.appendFileSync("deployment-log.txt", logData);
}

main().catch((error) => {
  console.error(error);
  process.exitCode = 1;
});
