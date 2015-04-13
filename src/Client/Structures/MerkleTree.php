<?php
namespace ParagonIE\AsgardClient\Structures;

class MerkleTree
{
    private $leaves = [];
    private $root;
    
    /**
     * @param array $data An array of strings
     */
    public function __construct($data = [])
    {
        foreach ($data as $node) {
            if (!\is_string($node)) {
                $node = \json_encode($node);
            }
            $this->leaves[] = [
                'data' => $node,
                'hash' => \Sodium::crypto_generichash($node)
            ];
        }
        $this->root = $this->calculateRoot();
    }
    
    /**
     * Calculate the Merkle root of the existing data
     * 
     * @return string
     */
    public function calculateRoot()
    {
        $numLeaves = count($this->leaves);
        // get the next 2^N larger than our dataset
        $baseIterate = (int) pow(2, ceil(log($numLeaves, 2)));
        $hashes = [];
        // Initial population
        for ($i = 0; $i < $baseIterate; ++$i) {
            if ($i >= $numLeaves) {
                // Following Bitcoin's lead; keep hashing the remainder
                $hashes[] = $this->leaves[$numLeaves - 1]['hash'];
            } else {
                $hashes[] = $this->leaves[$i]['hash'];
            }
        }
        
        // Let's hash together until we have one node left
        do {
            $tmp = [];
            // Iterate through the first level of the tree
            $j = 0;
            for ($i = 0; $i < $baseIterate; $i += 2) {
                if (empty($hashes[$i + 1])) {
                    $tmp[$j] = \Sodium::crypto_generichash($hashes[$i].$hashes[$i]);
                } else {
                    $tmp[$j] = \Sodium::crypto_generichash($hashes[$i].$hashes[$i + 1]);
                }
                ++$j;
            }
            $hashes = $tmp;
            $baseIterate = $baseIterate >> 1;
        } while($baseIterate > 1);
        return \array_shift($hashes);
    }
    
    
    /**
     * Calculate the hash tree for a given dataset:
     *     0 => Merkle Root
     *     1 => Left Child
     *     2 => Right Child,
     *     3 => LL, 4 => LR
     *     5 => RL, 6 => RR,
     *     ...
     * 
     * @return array
     */
    public function getTree()
    {
        $numLeaves = count($this->leaves);
        // get the next 2^N larger than our dataset
        $baseIterate = (int) pow(2, ceil(log($numLeaves, 2)));
        $hashes = [];
        // Initial population
        for ($i = 0; $i < $baseIterate; ++$i) {
            if ($i >= $numLeaves) {
                // Following Bitcoin's lead; keep hashing the remainder
                $hashes[] = $this->leaves[$numLeaves - 1]['hash'];
            } else {
                $hashes[] = $this->leaves[$i]['hash'];
            }
        }
        $tree = $hashes;
        
        // Let's hash together until we have one node left
        do {
            $tmp = [];
            // Iterate through the first level of the tree
            $j = 0;
            for ($i = 0; $i < $baseIterate; $i += 2) {
                $tmp[$j] = \Sodium::crypto_generichash($hashes[$i].$hashes[$i + 1]);
                \array_unshift($tree, $tmp[$j]);
                ++$j;
            }
            $hashes = $tmp;
            $baseIterate = $baseIterate >> 1;
        } while($baseIterate > 1);
        return $tree;
    }
    
    /**
     * Get the hex-encoded root hash for this Merkle tree
     * 
     * @return string
     */
    public function getRoot()
    {
        return \bin2hex($this->root);
    }
    
    /**
     * Is this the proper root hash for this block?
     * 
     * @param string $hash (binary)
     * @return boolean
     */
    public function isValidRoot($hash)
    {
        return \hash_equals($this->root, $hash);
    }
}
