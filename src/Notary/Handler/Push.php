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
            $message = \base64_decode($_POST['message']);
            if (\Sodium::crypto_sign_verify_detached(
                \base64_decode($_POST['signature']),
                $message,
                \base64_decode(\ParagonIE\AsgardClient\MetaData::AUTHORIZED_PUBLICKEY)
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
        $tail = $this->db->selectRow(
            'blocks',
            ['id', 'hash'],
            [],
            [ ['id', DESC] ]
        );
        if (!\hash_equals($tail['hash'], $prevhash)) {
            echo \json_encode(
                [
                    'error' => 'Blockchain tail hash mismatch!'
                ],
                JSON_PRETTY_PRINT
            );
            exit;
        }
        
        $this->db->update('blocks', [
            'nexthash' => $merkleroot,
            'nextblock' => $blockId
        ], [
            'id' => $tail['id']
        ]);
        
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
