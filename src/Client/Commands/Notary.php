<?php
namespace ParagonIE\AsgardClient\Commands;

use \ParagonIE\AsgardClient as Base;

class Notary extends Base\Command
{
    public $essential = true;
    public $display = 10;
    public $name = 'Notary Server';
    public $description = 'Operate a notary server to help others verify the distributed ledger.';
    
    private $server_config = [];

    /**
     * Fire the command!
     * 
     * @param array $args
     */
    public function fire(array $args = [])
    {
        if (\count($args) < 1) {
            return $this->usageInfo();
        }
        // Load the config
        $this->server_config = \json_decode(
            \file_get_contents(CONFIGROOT.'notary_server.json'),
            true
        );
        
        switch ($args[0]) {
            case 'up':
            case 'start':
                return $this->startServer();
            case 'dn':
            case 'down':
            case 'stop':
                return $this->stopServer();
            case 'restart':
            case 'reboot':
                $this->stopServer();
                return $this->startServer();
            case 'tag':
                return $this->getTag();
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
        
        echo $HTAB, "How to use this command:\n";
        
            echo $TAB, $this->c['cyan'], 'asgard notary start', $this->c[''], "\n";
            echo $TAB, $HTAB, 'Starts the server. If no signing key is available, a new one will be generated.';
            echo "\n\n";
    }
    
    public function generateKeys()
    {
        // Generate new keypairs...
        $skp = \Sodium::crypto_sign_keypair();
        // $bkp = \Sodium::crypto_box_keypair();
        
        $this->server_config['crypto']['signing_key'] = \base64_encode($skp);
        // $this->server_config['crypto']['box_key'] = \base64_encode($bkp);
        
        $this->saveConfig();
    }
    
    public function startServer()
    {
        if (!$this->server_config['enabled']) {
            echo 'Notary server started', "\n";
        }
        $this->server_config['enabled'] = true;
        if (empty($this->server_config['crypto'])) {
            $this->generateKeys();
            $this->getTag();
        } else {
            $this->saveConfig();
        }
    }
    
    public function stopServer()
    {
        if ($this->server_config['enabled']) {
            echo 'Notary server stopped', "\n";
        }
        $this->server_config['enabled'] = false;
        if (empty($this->server_config['crypto'])) {
            $this->generateKeys();
            $this->getTag();
        } else {
            $this->saveConfig();
        }
        
    }
    
    public function getTag()
    {
        if (empty($this->server_config['crypto']['signing_key'])) {
            echo "No keypair found. Keys are generated when you first start your notary.\n";
            echo "\nTry running this first:\n\tasgard notary start\n";
            exit;
        }
        $pubkey = \Sodium::crypto_sign_publickey(
            \base64_decode($this->server_config['crypto']['signing_key'])
        );
        $tag = 'http';
        if ($this->server_config['tls']['enabled']) {
            $tag .= 's';
        }
        $tag .= '://'.$this->server_config['host'];
        $tag .= ':'.$this->server_config['port'];
        $tag .= '/#';
        $tag .= \urlencode(\base64_encode($pubkey));
        echo $tag."\n";
    }
    
    public function saveConfig()
    {
        \file_put_contents(
            CONFIGROOT.'notary_server.json',
            \json_encode($this->server_config, JSON_PRETTY_PRINT)
        );
    }
}
