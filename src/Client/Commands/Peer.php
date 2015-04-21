<?php
namespace ParagonIE\AsgardClient\Commands;

use \ParagonIE\AsgardClient as Base;

class Peer extends Base\Command
{
    public $essential = true;
    public $display = 9;
    public $name = 'Peers';
    public $description = 'Manage peers (third-party notaries) that verify the correctness of the distributed ledger.';
    public $label = [
        'peerlist' => "Peer Notaries"
    ];

    public function fire(array $args = [])
    {
        $argc = \count($args);
        if ($argc < 1) {
            return $this->usageInfo();
        }
        
        switch ($args[0]) {
            case 'ls':
            case 'list':
                return $this->listPeers();
            case 'add':
                if ($argc < 2) {
                    return $this->usageInfo();
                }
                if ($argc >= 3) {
                    return $this->add($args[1], $args[2]);
                }
                return $this->add($args[1]);
            case 'rm':
                if ($argc < 2) {
                    return $this->usageInfo();
                }
                return $this->remove($args[1]);
            case 'flush-untrusted':
                return $this->flushUntrusted();
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
        
        echo $HTAB, 'How to use this command:', "\n";
        
            echo $TAB, $this->c['cyan'], 'asgard peer list', $this->c[''], "\n";
            echo $TAB, $HTAB, 'Displays which peers are already in the pool.';
            echo "\n\n";
            
            echo $TAB, $this->c['cyan'], 'asgard peer add [tag] [name]', $this->c[''], "\n";
            echo $TAB, $HTAB, 'Add (and trust) a peer notary server to the pool. Nickname is optional.';
            echo "\n\n";
            
            echo $TAB, $this->c['cyan'], 'asgard peer rm [name]', $this->c[''], "\n";
            echo $TAB, $HTAB, 'Remove a peer notary server from the pool.';
            echo "\n\n";
    }
    
    private function listPeers()
    {
        $TAB = str_repeat(' ', self::TAB_SIZE);
        $HTAB = str_repeat(' ', ceil(self::TAB_SIZE / 2));
        
        // Column width
        $columns = [4, 5, 4, 4, 10];
        $peers = [];
        
        foreach ($this->db->select('notaries') as $peer) {
            $peers[] = [
                'nickname' => $peer['nickname'],
                'https' => !empty($peer['https'])
                    ? '  Y  '
                    : '  N  ',
                'host' => $peer['host'],
                'port' => $peer['port'],
                'trust' => $peer['trust'],
                'publickey' => $peer['publickey']
            ];
            
            if (\strlen($peer['nickname']) > $columns[0]) {
                $columns[0] = \strlen($peer['nickname']);
            }
            if (\strlen($peer['host']) > $columns[2]) {
                $columns[2] = \strlen($peer['host']);
            }
            if (\strlen($peer['port']) > $columns[3]) {
                $columns[3] = \strlen($peer['port']);
            }
            if (\strlen($peer['publickey']) > $columns[4]) {
                $columns[4] = \strlen($peer['publickey']);
            }
        }
        $width = $this->getScreenSize()['width'];
        
        // Prevent wrapping because of newline characters
        --$columns[4];
        
        $header = $this->c['blue'].
            $TAB.
            str_pad('Name', $columns[0], ' ', STR_PAD_RIGHT).
                $HTAB.
            str_pad('HTTPS', $columns[1], ' ', STR_PAD_RIGHT).
                $HTAB.
            str_pad('Host', $columns[2], ' ', STR_PAD_RIGHT).
                $HTAB.
            str_pad('Port', $columns[3], ' ', STR_PAD_RIGHT).
                $HTAB.
            str_pad('Public Key', $columns[4], ' ', STR_PAD_RIGHT).
            $this->c['silver'].
            "\n".
            $TAB . str_repeat('=', $width - self::TAB_SIZE - 1)."\n";
        echo $this->label['peerlist'];
        echo " (";
            echo "\033[0;91m", "RED", $this->c[''], ' = Pre-Installed, '; 
            echo "\033[1;92m", "GREEN", $this->c[''], ' = Added'; 
        echo ")", "\n", str_repeat('_', $width - 1), "\n";
        
        echo $header;
        $nl = false;
        foreach ($peers as $p) {
            if ($nl) {
                echo "\n", $TAB, str_repeat('-', $width - self::TAB_SIZE - 1), "\n";
            }
            echo $TAB;
            if ($p['trust'] > 0) {
                echo "\033[1;92m";
            } else {
                echo "\033[0;91m";
            }
            echo str_pad($p['nickname'], $columns[0], ' ', STR_PAD_RIGHT)."\033[0;39m";
            echo $HTAB;
            echo str_pad($p['https'], $columns[1], ' ', STR_PAD_RIGHT);
            echo $HTAB;
            echo str_pad($p['host'], $columns[2], ' ', STR_PAD_RIGHT);
            echo $HTAB;
            echo str_pad($p['port'], $columns[3], ' ', STR_PAD_LEFT);
            echo $HTAB;
            echo str_pad($p['publickey'], $columns[4], ' ', STR_PAD_RIGHT);
            $nl = true;
        }
        echo "\n", str_repeat('_', $width - 1), "\n";
    }
    
    /**
     * Add a peer to the notary pool.
     * 
     * @param string $tag
     * @param string $nickname
     */
    private function add($tag, $nickname = '')
    {
        if (\preg_match('~^http(s)?://([^:]+):([0-9]+)/#([A-Za-z0-9%]+)~', $tag, $m)) {
            $taginfo = [
                'https' => $m[1] === 's',
                'host' => $m[2],
                'port' => $m[3],
                'pubkey' => \urldecode($m[4])
            ];
            if (empty($nickname)) {
                $nickname = preg_replace('#[^A-Za-z0-9\-]#', '-', $taginfo['host']);
            }
            $nn = $nickname;
            $incr = 1;
            do {
                $exists = $this->db->selectOne(
                    'notaries',
                    'COUNT(id)',
                    [
                        'nickname' => $nn
                    ]
                );
                if ($exists > 0) {
                    // Increasing tag.
                    $nn = $nickname.'-'.++$incr;
                }
            } while($exists > 0);
            
            $exists = $this->db->selectOne(
                'notaries',
                'COUNT(id)',
                [
                    'publickey' => $taginfo['pubkey'],
                    'host' => $taginfo['host'],
                ]
            );
            if (!empty($exists)) {
                die("Peer already exists.\n");
            }
            $ins = $this->db->insert(
                'notaries', [
                    'nickname' => $nn,
                    'https' => $taginfo['https'] ? 1 : 0,
                    'publickey' => $taginfo['pubkey'],
                    'host' => $taginfo['host'],
                    'port' => $taginfo['port'],
                    'trust' => 1,
                    'created' => date('Y-m-d\TH:i:s'),
                    'modified' => date('Y-m-d\TH:i:s')
                ]
            );
            if ($ins) {
                echo "\t{$nn} added successfully!\n";
            }
        } else {
            die("Tag format not recognized!\n");
        }
    }
    
    private function remove($nickname)
    {
        return $this->db->delete(
            'notaries',
            [
                'nickname' => $nickname
            ]
        );
    }
    
    private function flushUntrusted()
    {
        return $this->db->delete(
            'notaries',
            [
                'trust' => 0
            ]
        );
    }
}
