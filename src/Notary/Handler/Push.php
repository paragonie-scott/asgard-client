<?php
namespace ParagonIE\AsgardNotary\Handler;

use \ParagonIE\AsgardNotary as Base;
use \ParagonIE\AsgardClient\Structures as Structs;

class BlockChain extends Base\BaseHandler
{
    private $db;

    public function get()
    {
        echo \json_encode(
            [
                'error' => 'Method GET not allowed!'
            ],
            JSON_PRETTY_PRINT
        );
        exit;
    }

    public function post()
    {
        \header('Content-Type: application/json');
        if (
            !empty($_POST['message']) &&
            !empty($_POST['signature']) &&
            !empty($_POST['prevhash']) &&
            !empty($_POST['merkleroot']) &&
            !empty($_POST['block'])
        ) {
            // Plausibly a message from the mother ship? Let's check our 
            $signature = \base64_decode($_POST['signature']);
            $message = \base64_decode($_POST['message']);
            $pubkey = \base64_decode(
                \ParagonIE\AsgardClient\MetaData::AUTHORIZED_PUBLICKEY
            );
            if (\Sodium::crypto_sign_verify_detached(
                $signature,
                $message,
                $pubkey
            )) {
                // Should be the same as a new message
                $re_encode = \json_encode(
                    [
                        'signature' => $_POST['signature'],
                        'message' => $_POST['message']
                    ],
                    JSON_PRETTY_PRINT
                );
                return $this->insertBlock(
                    $re_encode,
                    $_POST['merkleroot'],
                    $_POST['block'],
                    $_POST['prevhash']
                );
            }
        }
        
        // Usual case: You are not l33t enough to grab our priv8 key
        echo \json_encode(
            [
                'error' => 'Access denied.'
            ],
            JSON_PRETTY_PRINT
        );
        exit;
    }
    
    /**
     * Let's insert a block into our blockchain.
     * 
     * @param stirng $jsondata
     * @param string $merkleroot
     * @param int $blockId
     * @param string $prevhash
     */
    private function insertBlock($jsondata, $merkleroot, $blockId, $prevhash)
    {
        $msg = \json_decode($jsondata, true);
        
        // This still ought to be a JSON encoded string:
        $blockdata = \base64_decode($msg['blockdata']);
        
        $mtree = new Structs\MerkleTree(
                \json_decode($blockdata, true)
        );
        
        /* 1. Verify merkle root (both should be hex encoded) */
        if (!\hash_equals($merkleroot, $mtree->getRoot())) {
            echo \json_encode(
                [
                    'error' => 'Invalid Merkle Root.'
                ],
                JSON_PRETTY_PRINT
            );
            exit;
        }
        
        // Grab previous tailhash:
        $tail = $this->db->selectRow(
            'blocks',
            ['id', 'hash'],
            [],
            [ ['id', DESC] ]
        );
        // If our previous hash does not equal the pointer to the prevhash in 
        // the current block, something screwy is going on.
        if (!\hash_equals($tail['hash'], $prevhash)) {
            echo \json_encode(
                [
                    'error' => 'Blockchain tail hash mismatch!'
                ],
                JSON_PRETTY_PRINT
            );
            exit;
        }
        
        // Update previous tail block to point to the new addition
        $this->db->update('blocks', [
            'nexthash' => $merkleroot,
            'nextblock' => $blockId
        ], [
            'id' => $tail['id']
        ]);
        
        // Insert a new block        
        return $this->db->insert('blocks', [
            'id' => $blockId,
            'hash' => $merkleroot,
            'prevblock' => $tail['id'],
            'prevhash' => $tail['hash'],
            'contents' => $blockdata,
            'verified' => 0
        ]);
    }
}
