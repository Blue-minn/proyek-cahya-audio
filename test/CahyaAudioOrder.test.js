const { expect } = require("chai");
const { ethers } = require("hardhat");

// Urutan enum OrderStatus di CahyaAudioOrder.sol
const Status = { Created: 0n, Paid: 1n, InProcess: 2n, Completed: 3n, Cancelled: 4n };

describe("CahyaAudioOrder", function () {
  let contract, owner, operator, customer, stranger;
  const CODE = "CA-20261109-0001";
  const AMOUNT = 4500000n;
  const HASH = ethers.id("demo-order-data");

  beforeEach(async function () {
    [owner, operator, customer, stranger] = await ethers.getSigners();
    const Factory = await ethers.getContractFactory("CahyaAudioOrder");
    contract = await Factory.deploy();
    await contract.waitForDeployment();
    await contract.connect(customer).createOrder(CODE, AMOUNT, HASH);
  });

  describe("createOrder", function () {
    it("mencatat order dengan wallet pemanggil sebagai pelanggan", async function () {
      const o = await contract.getOrder(CODE);
      expect(o.customerWallet).to.equal(customer.address);
      expect(o.totalAmount).to.equal(AMOUNT);
      expect(o.status).to.equal(Status.Created);
      expect(o.dataHash).to.equal(HASH);
      expect(await contract.totalOrders()).to.equal(1n);
    });

    it("memancarkan event OrderCreated", async function () {
      await expect(contract.connect(customer).createOrder("CA-2", AMOUNT, HASH))
        .to.emit(contract, "OrderCreated");
    });

    it("menolak kode order duplikat", async function () {
      await expect(contract.connect(customer).createOrder(CODE, AMOUNT, HASH))
        .to.be.revertedWith("Order ID sudah ada");
    });

    it("menolak input kosong atau nol", async function () {
      await expect(contract.createOrder("", AMOUNT, HASH)).to.be.revertedWith("Kode order wajib diisi");
      await expect(contract.createOrder("CA-X", 0, HASH)).to.be.revertedWith("Total harus lebih dari 0");
      await expect(contract.createOrder("CA-X", AMOUNT, ethers.ZeroHash)).to.be.revertedWith("Hash data wajib diisi");
    });
  });

  describe("operator", function () {
    it("deployer otomatis menjadi operator", async function () {
      expect(await contract.isOperator(owner.address)).to.equal(true);
      expect(await contract.isOperator(customer.address)).to.equal(false);
    });

    it("hanya owner yang bisa menambah dan mencabut operator", async function () {
      await expect(contract.connect(stranger).addOperator(operator.address))
        .to.be.revertedWith("Hanya pemilik/admin yang diizinkan");

      await expect(contract.addOperator(operator.address))
        .to.emit(contract, "OperatorAdded").withArgs(operator.address);
      expect(await contract.isOperator(operator.address)).to.equal(true);

      await expect(contract.removeOperator(operator.address))
        .to.emit(contract, "OperatorRemoved").withArgs(operator.address);
      expect(await contract.isOperator(operator.address)).to.equal(false);
    });

    it("owner tidak bisa dicabut", async function () {
      await expect(contract.removeOperator(owner.address)).to.be.revertedWith("Owner tidak bisa dicabut");
    });
  });

  describe("updateStatus", function () {
    it("menolak perubahan status dari non-operator (termasuk pelanggan)", async function () {
      await expect(contract.connect(customer).updateStatus(CODE, Status.Paid))
        .to.be.revertedWith("Hanya operator bengkel yang diizinkan");
      await expect(contract.connect(stranger).updateStatus(CODE, Status.Completed))
        .to.be.revertedWith("Hanya operator bengkel yang diizinkan");
    });

    it("mengikuti alur Created -> Paid -> InProcess -> Completed", async function () {
      await expect(contract.updateStatus(CODE, Status.Paid)).to.emit(contract, "OrderStatusUpdated");
      await contract.updateStatus(CODE, Status.InProcess);
      await contract.updateStatus(CODE, Status.Completed);
      expect((await contract.getOrder(CODE)).status).to.equal(Status.Completed);
    });

    it("menolak lompatan dan mundur status", async function () {
      await expect(contract.updateStatus(CODE, Status.Completed)).to.be.revertedWith("Perubahan status tidak valid");
      await contract.updateStatus(CODE, Status.Paid);
      await expect(contract.updateStatus(CODE, Status.Created)).to.be.revertedWith("Perubahan status tidak valid");
    });

    it("status akhir tidak bisa diubah lagi", async function () {
      await contract.updateStatus(CODE, Status.Paid);
      await contract.updateStatus(CODE, Status.InProcess);
      await contract.updateStatus(CODE, Status.Completed);
      await expect(contract.updateStatus(CODE, Status.Cancelled)).to.be.revertedWith("Perubahan status tidak valid");
    });

    it("operator bisa membatalkan order yang sedang diproses", async function () {
      await contract.updateStatus(CODE, Status.Paid);
      await contract.updateStatus(CODE, Status.Cancelled);
      expect((await contract.getOrder(CODE)).status).to.equal(Status.Cancelled);
    });

    it("menolak order yang tidak ada", async function () {
      await expect(contract.updateStatus("TIDAK-ADA", Status.Paid)).to.be.revertedWith("Order tidak ditemukan");
    });
  });

  describe("cancelOrder", function () {
    it("pelanggan bisa membatalkan order miliknya yang masih Created", async function () {
      await contract.connect(customer).cancelOrder(CODE);
      expect((await contract.getOrder(CODE)).status).to.equal(Status.Cancelled);
    });

    it("pihak lain tidak bisa membatalkan order pelanggan", async function () {
      await expect(contract.connect(stranger).cancelOrder(CODE))
        .to.be.revertedWith("Hanya pemilik order yang bisa membatalkan");
    });

    it("pelanggan tidak bisa membatalkan order yang sudah dibayar", async function () {
      await contract.updateStatus(CODE, Status.Paid);
      await expect(contract.connect(customer).cancelOrder(CODE))
        .to.be.revertedWith("Order sudah diproses, hubungi bengkel");
    });
  });
});
