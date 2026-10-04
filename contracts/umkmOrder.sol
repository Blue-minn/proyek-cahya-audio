// SPDX-License-Identifier: MIT
pragma solidity ^0.8.24;

contract umkmOrder {
    string public orderStatus = "Created";

    function updateStatus(string memory _status) public {
        orderStatus = _status;
    }
}
