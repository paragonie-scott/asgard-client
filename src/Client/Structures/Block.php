<?php
namespace ParagonIE\AsgardClient\Structures;

/**
 * Part of a BlockChain
 */
class Block
{
    private $merkleTree;
    private $previousHash;
    private $currentHash;
    private $nextHash;
    private $tailHash;
    
    /**
     * @param \ParagonIE\AsgardClient\Structures\MerkleTree $tree
     * @param string $prevhash
     * @param string $nexthash
     */
    public function __construct( 
        MerkleTree $tree,
        $prevhash = null,
        $nexthash = null
    ) {
        $this->merkleTree = $tree;
        $this->currentHash = $tree->getRoot();
        $this->previousHash = $prevhash;
        $this->nextHash = $nexthash;
        if (empty($prevhash)) {
            $this->tailHash = $this->currentHash;
        } else {
            $this->tailHash = \Sodium::crypto_generichash(
                \bin2hex($this->previousHash).
                \bin2hex($this->currentHash)
            );
        }
    }
    
    /**
     * Get the current hash
     * 
     * @return string
     */
    public function getHash()
    {
        return $this->currentHash;
    }
    
    /**
     * Get the current tail hash
     * 
     * @return string
     */
    public function getTailHash()
    {
        return $this->tailHash;
    }
    
    /**
     * Set the hash of the next block in the block chain
     * 
     * @param string $hash
     */
    public function setNextHash($hash) {
        $this->nextHash = $hash;
    }
    
}
