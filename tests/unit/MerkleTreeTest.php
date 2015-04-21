<?php

use \ParagonIE\AsgardClient\Structures as Structs;

class MerkleTreeTest extends PHPUnit_Framework_TestCase
{
    public function testHash()
    {
        $tree1 = new Structs\MerkleTree([
            'a', 'b', 'c', 'd', 'e'
        ]);
        $tree2 = new Structs\MerkleTree([
            'a', 'b', 'c', 'd', 'e', 'e', 'e', 'e'
        ]);
        
        $this->assertTrue(
            \hash_equals(
                $tree1->getRoot(),
                $tree2->getRoot()
            )
        );
    }
}
