# The Distributed Ledger

## Package update format

Each package update will, in the ledger, looks like:

```json
{
    "message": "alongbase64encodedstringhere",
    "signature": "ed25519signature"
}

After verifying the signature, the `message` unpacks to something more meaningful:

```json
{
    "name": "vendor/package-name",
    "client": [],
    "filename": "deliverable.phar",
    "version": "1.2.3",
    "checksums": {
        "sha256": "8ad1bfe4f1da38a8c3f842813fdee458e2d6e782683176caef942af3e9c47d6f",
        "BLAKE2b": "33ef1c07649bc1e135e73f0944415753233faf803cff493006f8bd9e744ff1f4"
    },
    "timestamp": "2015-04-11T03:02:29",
    "reproducible": true,
    "diff": "",
    "comments": "Successfully reproduced from build using phardiff -- @rdterjesen"
}
```

## Blocks

A block is a batch of updates for various packages. Each block is a Merkle Tree,
based on the [BLAKE2b](https://blake2.net/) hash function. Each one of the 
updates detailed in the previous section is stored as a leaf.

## The Blockchain

Every block is stored with:

* The block data
* The Merkle root of the block
* A tail hash

### What is a tail hash?

A tail hash is the BLAKE2b hash of the previous block's tail hash and the Merkle
root for the current block. For the first (Genesis) block in our ledger, it's
simply the BLAKE2b hash of the Merkle root and itself.

The tail hash allows for an optimized verification, to save users from having
to construct hash trees out of every block and then calculate and verify every
hash when ever there is an update.
