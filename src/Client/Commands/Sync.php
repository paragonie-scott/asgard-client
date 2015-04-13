<?php
namespace ParagonIE\AsgardClient\Commands;

use \ParagonIE\AsgardClient as Base;

class Sync extends Base\Command
{
    public $essential = false;
    public $display = 7;
    public $name = 'Synchronize';
    public $description = 'Download updates to the distributed ledger.';

    /**
     * Execute the sync command
     *
     * @param array $args - CLI arguments
     * @echo
     * @return null
     */
    public function fire(array $args = [])
    {
        $lastBlock = $this->getLastBlock();
        if (!empty($lastBlock)) {
            $lastBlockId = $lastBlock['id'];
            echo "Last Block: ".$lastBlock['hash']."\n";
        } else {
            $lastBlockId = 0;
            echo "Genesis block!\n";
        }
        $mirror = $this->getRandomMirror();
        echo "Mirror: ".$mirror['url']."\n";
        
        // We don't really need to consider $args for syncing...
        $newBlocks = $this->getNewBlocksFromMirror($mirror, $lastBlockId, true);
        
        // Okay, now let's look at our peer notaries!
        if (!empty($newBlocks)) {
            if ($this->peerPressure($newBlocks, true)) {
                $this->insertBlocks($newBlocks, true);
            }
            echo "\nSynchronizing complete!\n";
        }
        echo "\nNo updates available.\n";
    }
    
    /**
     * Get information about the last block in our blockchain
     * 
     * @return array
     */
    public function getLastBlock()
    {
        return $this->bc->selectRow(
            'blocks', 
            '*', 
            [], 
            [
                ['id', 'DESC']
            ],
            [],
            0,
            1
        );
    }
    
    /**
     * Download new blocks
     * 
     * @param array $mirror
     * @param int $lastBlockId
     * @param bool $echo
     * @return int
     */
    public function getNewBlocksFromMirror($mirror, $lastBlockId, $echo = false)
    {
        $matches = [];
        // $mirror['url']
        if (\preg_match('#^[A-Za-z]+://([^/]+)(/.*)?#', $mirror['url'], $matches)) {
            $domain = $matches[1];
            $basepath = isset($matches[2])
                ? $matches[2]
                : '';
        } elseif (\preg_match('#^([^/]+)(/.*)?#', $mirror['url'], $matches)) {
            $domain = $matches[1];
            $basepath = isset($matches[2])
                ? $matches[2]
                : '';
        } else {
            throw new \Exception("Invalid mirror URL");
        }
        $net = new Base\HTTPS($domain);
        
        echo 'Synchronizing...';
        $apiResponse = $net->get(
            $basepath.self::$api['blockchain']['new'].$lastBlockId
        );
        echo "\n";
        if (!empty($apiResponse)) {
            if ($echo) {
                echo $this->c['green'], ' Complete.', $this->c[''], "\n";
            }
            return \json_decode($apiResponse, true);
        }
        if ($echo) {
            echo $this->c['red'], ' FAILED!', $this->c[''], "\n";
        }
        throw new \Exception("API response error!");
    }
    
    /**
     * Use this entry point from methods to suppress output.
     */
    public function silentSync()
    {
        $lastBlock = $this->getLastBlock();
        if (!empty($lastBlock)) {
            $lastBlockId = $lastBlock['id'];
        } else {
            $lastBlockId = 0;
        }
        $mirror = $this->getCommandObject('Mirror', true)->getRandomMirror();
        
        // We don't really need to consider $args for syncing...
        $newBlocks = $this->getNewBlocksFromMirror($mirror, $lastBlockId, false);
        
        // Okay, now let's look at our peer notaries!
        if (!empty($newBlocks)) {
            if ($this->peerPressure($newBlocks, false)) {
                $this->insertBlocks($newBlocks, false);
            }
        }
    }

    /**
     * Display usage information for this command.
     * 
     * @echo
     * @return null
     */
    public function usageInfo()
    {
        $TAB = str_repeat(' ', self::TAB_SIZE);
        $HTAB = str_repeat(' ', ceil(self::TAB_SIZE / 2));
       
        echo $HTAB.$this->name."\n";
        echo $TAB.$this->description."\n\n";
        echo $HTAB."How to use this command:\n";
            echo $TAB.$this->c['cyan']."asgard sync".$this->c['']."\n";
            echo $TAB.$HTAB."Download updates to the blockchain (distributed ledger).";
            echo "\n";
    }
    
    /**
     * Did this
     * 
     * @param array $peerResponse
     * @param array $newBlocks
     * @param array $peer
     * @param boolean $echo
     * @return boolean
     */
    private function analyzePeerResponse($peerResponse, $newBlocks, $peer, $echo)
    {
        // Date and Time calculation:
        $ourTime = new \DateTime('now');
        $notaryTime = new \DateTime($peerResponse['timestamp']);
        $interval = $ourTime->diff($notaryTime);
        
        // We don't care which direction, only its magnitude
        $elapsed = \abs($interval->format('%s'));

        if ($elapsed < \ParagonIE\AsgardClient\MetaData::SYNC_MAX_DELTA) {
            // At this point, we also know it's a fresh message too!
            if ($this->compareBlocks($newBlocks, $peerResponse['blocks'], $echo)) {
                return true;
            } elseif ($echo) {
                echo $this->c['red'], 'Peer notary disagrees with blockchain: ', $peer['nickname'], $this->c[''], "\n";
            }
        } elseif ($echo) {
            echo $this->c['red'], 'Peer notary signature timeout: ', $peer['nickname'], $this->c[''], "\n";
        }
        return false;
    }
    
    /**
     * Do our blocks match?
     * 
     * @param array $newBlocks
     * @param array $peerBlocks
     * @param boolean $echo
     * @return boolean
     */
    private function compareBlocks($newBlocks, $peerBlocks, $echo)
    {
        $size = [\count($newBlocks), \count($peerBlocks)];
        if ($size[0] < $size[1]) { 
            // Erm, no. Hell no.
            if ($echo) {
                echo "Error: Peer is ahead of the ledger. This should never happen!\n";
            }
            return false;
        } elseif ($size[0] > $size[1]) {
            // Uh oh. Peer is out of date. :(
            if ($echo) {
                echo "Error: Peer is out of date with the ledger!\n";
            }
            return false;
        }
        
        // Just, no.
        $nb = \array_values($newBlocks);
        $pb = \array_values($peerBlocks);
        
        for ($i = 0; $i < $size[0]; ++$i) {
            if (!\hash_equals($pb['tailhash'], $nb['tailhash'])) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Having checked the signature from the mirror, and verified the 
     * correctness and integrity of these blocks with our peer notaries,
     * we can now insert these blocks into our local blockchain.
     * 
     * @param array $newBlocks
     * @param bool $echo
     */
    private function insertBlocks($newBlocks, $echo)
    {
        $tail = $this->bc->selectRow(
            'blocks',
            ['id', 'hash'],
            [],
            [ ['id', DESC] ]
        );
        $lastId = $tail['id'];
        $lastHash = $tail['hash'];
        
        foreach ($newBlocks as $b) {
            $this->bc->update(
                'blocks', 
                [
                    'nextblock' => $b['blockid'],
                    'nexthash' => $b['merkleroot']
                ], 
                [
                    'id' => $lastId
                ]
            );
            $this->bc->insert('blocks', [
                'id'         => $b['blockid'],
                'hash'       => $b['merkleroot'],
                'prevhash'   => $lastHash,
                'verified'   => 1,
                'prevblock'  => $lastId,
                'contents'   => $b['blockdata'],
            ]);
            $lastId = \intval($b['blockid']);
            $lastHash = $b['merkleroot'];
        }
        if ($echo) {
            $numBlocks = \count($newBlocks);
            echo "\t", $this->c['green'], 'SYNC SUCCESSFUL! ', $this->c[''],
                $numBlocks, ' new block', ($newBlocks === 1 ? '' : 's'), ' added.', "\n";
        }
        return true;
    }
    
    /**
     * Verify new blocks with our peers
     * 
     * @param array $newBlocks
     * @param boolean $echo
     */
    private function peerPressure($newBlocks, $echo = false)
    {
        if ($echo) {
            echo "\n\tVerifying new blocks...\n";
        }
        $lasthash = $this->bc->single("SELECT hash FROM blocks ORDER BY id DESC");
        $notaries = $this->db->select('notaries');
        
        $consensus = [
            'fail' => 0,
            'pass' => 0
        ];
        
        foreach ($notaries as $peer) {
            $hostname = $peer['https'] > 0 
                ? 'https://'.$peer['host'].':'.$peer['port']
                : 'http://'.$peer['host'].':'.$peer['port'];
            
            $net = new Base\HTTPS($hostname);
            
            $body = $net->get(
                '/blockchain/hash/'.$lasthash
            );
            
            if (empty($body)) {
                $response = \json_decode($body, true);
            } else {
                $response = null;
            }
            if (!empty($response['message']) && !empty($response['signature'])) {
                $msg = \base64_decode($response['message']);
                $sig = \base64_decode($response['signature']);
                $pubkey = \base64_decode($peer['publickey']);
                
                if (\Sodium::crypto_sign_verify_detached($sig, $msg, $pubkey)) {
                    // At this point, we know the signature was valid.
                    if ($this->analyzePeerResponse(
                        \json_decode($msg, true), 
                        $newBlocks,
                        $peer,
                        $echo
                    )) {
                        ++$consensus['pass'];
                        continue;
                    }
                } elseif ($echo) {
                    echo $this->c['red'], 'Peer signature failed: ', $peer['nickname'], $this->c[''], "\n";
                }
            } elseif ($echo) {
                echo $this->c['red'], 'Peer connection failed or response invalid: ', $peer['nickname'], $this->c[''], "\n";
            }
            // When something fails, increment it here.
            ++$consensus['fail'];
        }
        
        // After the foreach loop runs, decide whether or not to proceed.
        if ($consensus['fail'] === 0 && $consensus['pass'] > 0) {
            // No failures, at least 1 success
            return true;
        }
        if ($echo) {
            echo $this->c['red'], 'An error has occurred!', $this->c[''], "\n",
                'Attempting to synchronize the distributed ledger failed!', "\n";
        }
        return false;
    }
}
