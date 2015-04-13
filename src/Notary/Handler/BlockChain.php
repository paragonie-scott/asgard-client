<?php
namespace ParagonIE\AsgardNotary\Handler;

use \ParagonIE\AsgardNotary as Base;

class BlockChain extends Base\AbstractHandler
{
    private $db;
    
    public function get()
    {
        $args = \func_get_args();
        if (\func_num_args() === 1) {
            return $this->blocksSince($args[0]);
        }
        return $this->allBlocks();
    }
    
    /**
     * Get all blocks since a given hash
     * 
     * @param string $hash
     */
    public function blocksSince($hash = '')
    {
        $begin = $this->db->selectOne(
            'blocks', 
            'id', 
            ['hash' => $hash]
        );
        
        $blocks = $this->db->run(
            "SELECT id, hash, prevhash, nexthash, tailhash FROM blocks WHERE id > ?",
            [$begin]
        );
        
        \header("Content-Type: application/json");
        echo $this->notarizeBlocks($blocks);
        exit;
    }
    
    /**
     * Display all blocks in the block chain, in order.
     */
    public function allBlocks()
    {
        $blocks = $this->db->run(
            "SELECT id, hash, prevhash, nexthash FROM blocks ORDER BY id ASC"
        );
        
        \header("Content-Type: application/json");
        echo $this->notarizeBlocks($blocks);
        exit;
    }
    
    /**
     * Attest to the legitimacy of a particular block
     * 
     * @param array $blocks
     */
    private function notarizeBlocks($blocks)
    {
        $message = \json_encode([
            'datetime' => \date('c'),
            'blocks' => $blocks
        ]);
        $skey = \Sodium::crypto_sign_secretkey(
            \base64_decode(
                $this->config['crypto']['signing_key']
            )
        );
        $signature = \Sodium::crypto_sign_detached($message, $skey);
        
        return \json_encode(
            [
                'message' => \base64_encode($message),
                'signature' => \base64_encode($signature)
            ],
            JSON_PRETTY_PRINT
        );
    }
}
