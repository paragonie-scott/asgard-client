<?php
namespace ParagonIE\AsgardClient\Storage;

use \ParagonIE\AsgardClient as Asgard;
use \ParagonIE\AsgardClient\Storage\Adapters as Adapt;
use \ParagonIE\AsgardClient\Structures as Structs;

class BlockChain 
{
    private $db;
    private $loaded = false;
    /**
     * @param \ParagonIE\AsgardClient\Storage\Adapters\AdapterInterface $storage
     */
    public function __construct(Adapt\AdapterInterface $storage)
    {
        $this->db = $storage;
    }
    
    /**
     * Load the block chain stored in the appropriate adapter
     * 
     * @return \ParagonIE\AsgardClient\Structures\BlockChain
     */
    public function loadBlockChain()
    {
        $aRows = $this->db->select('licenses');
        $aBlocks = array();
        foreach ($aRows as $aRow) {
            
        }
        
        $oBChain = new \ParagonIE\AsgardClient\Structures\BlockChain();
    }
}
