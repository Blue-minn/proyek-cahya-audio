const { expect } = require("chai");
const { ethers } = require("hardhat");

describe("HSportOrder (Order Core)", function () {
  let contract, owner, customer;
  const HASH = ethers.id("CA-20261109-0001|pelanggan|4500000");

  beforeEach(async function () {
    [owner, customer] = await ethers.getSigners();
    const Factory = await ethers.getContractFactory("HSportOrder");
    contract = await Factory.deploy();
    await contract.waitForDeployment();
  });

  it("owner otomatis menjadi operator", async function () {
    expect(await contract.owner()).to.equal(owner.address);
    expect(await contract.isOperator(owner.address)).to.equal(true);
    expect(await contract.isOperator(customer.address)).to.equal(false);
  });

  it("createOrder memberi ID berurutan dan menyimpan data", async function () {
    await expect(contract.connect(customer).createOrder(HASH, 4500000))
      .to.emit(contract, "OrderCreated")
      .withArgs(1n, HASH, customer.address, 4500000n);

    expect(await contract.orderCount()).to.equal(1n);
    expect(await contract.orderIdByHash(HASH)).to.equal(1n);

    const o = await contract.getOrder(1);
    expect(o.id).to.equal(1n);
    expect(o.customer).to.equal(customer.address);
    expect(o.totalAmount).to.equal(4500000n);
    expect(o.status).to.equal(0n); // Created
    expect(o.paymentRecorded).to.equal(false);
  });

  it("menolak total 0", async function () {
    await expect(contract.createOrder(HASH, 0)).to.be.revertedWithCustomError(contract, "InvalidAmount");
  });

  it("menolak hash kosong", async function () {
    await expect(contract.createOrder(ethers.ZeroHash, 100))
      .to.be.revertedWithCustomError(contract, "InvalidOrderHash");
  });

  it("menolak hash duplikat", async function () {
    await contract.createOrder(HASH, 100);
    await expect(contract.createOrder(HASH, 100))
      .to.be.revertedWithCustomError(contract, "DuplicateOrderHash")
      .withArgs(HASH, 1n);
  });

  it("getOrder menolak ID yang tidak ada", async function () {
    await expect(contract.getOrder(0)).to.be.revertedWithCustomError(contract, "OrderNotFound").withArgs(0n);
    await expect(contract.getOrder(5)).to.be.revertedWithCustomError(contract, "OrderNotFound").withArgs(5n);
  });
});
