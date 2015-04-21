<?php
namespace ParagonIE\AsgardClient\Commands;

use \ParagonIE\AsgardClient as Base;

class Get extends Base\Command
{
    public $essential = true;
    public $display = 2;
    public $name = 'Get';
    public $description = 'Download a package from ASGard.';
    public $tag = [
        'color' => 'green',
        'text' => 'Secure'
    ];

    /**
     * Fire the command!
     * 
     * @param array $args
     */
    public function fire(array $args = [])
    {
        if (empty($args[0])) {
            return $this->usageInfo();
        }
        
        // Synchronize the ledger
        $this->getCommandObject('sync')
            ->silentSync();
        
        // What updates are available; what packages are we requesting?
        echo 'Downloading updates...', "\n";
        
        // Without printing too much, let's download the file from a mirror
        list($updates, $pkg, $license) = $this->getCommandObject('download')
            ->silentFetch($args[0]);
        
        if (empty($updates[0])) {
            echo 'Already up to date!', "\n";
        }
        
        // Let's verify the block with our notaries
        $blocks = $this->getCommandObject('verify')
            ->check(
                $args[0],
                $pkg
            );
        
        if (!empty($blocks)) {
            // Well, let's install then!
            $this->install(
                $pkg,
                $updates,
                $blocks,
                $license,
                $args[0]
            );
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
            echo $TAB, $this->c['cyan'], 'asgard get [package]', $this->c[''], "\n";
            echo $TAB, $HTAB, 'Download, verify, and install a package.';
            echo "\n\n";
    }
    
    /**
     * Install a package, given a destination
     * 
     * @param string $pkg_file  Temporary file location
     * @param array $updates    
     * @param array $blocks     
     * @param string $publickey 
     * @param string $selected  
     * 
     * @todo figure this out
     */
    private function install($pkg_file, $updates, $blocks, $publickey, $selected)
    {
        $block = $blocks['extracted'];
        
        // If it's already installed, we need to update it instead!
        $configTree = self::$userConfig->get(['packages']);
        if (!empty($configTree)) {
            foreach ($configTree as $pkg) {
                if ($pkg['name'] === $selected) {
                    
                    // The package is already installed. Update it instead!
                    return $this->getCommandObject('Update')
                        ->fire($selected);
                }
            }
        }
        
        $serverInfo = null;
        foreach ($updates as $upd) {
            if ($upd['packagename'] === $selected) {
                // This is the array data we need
                $serverInfo = $upd;
                break;
            }
        }
        
        // We shouldn't have this happen
        if (empty($serverInfo)) {
            return false;
        }
        
        $now = new \DateTime('NOW');
        
        // Now let's install it!
        $destination = $this->installOne($pkg_file, $block[0]);
        $save = [
            'name' => $selected,
            'location' => $destination,
            'publickey' => $publickey,
            'blockhash' => $blocks['merkleroot'],
            'version' => isset($block[0]['version']) ? $block[0]['version'] : '',
            'date' => $now->format('c')
        ];
        
        // Save this data in the JSON configuration
        self::$userConfig->append(['packages'], $save);
        self::$userConfig->save();
    }
    
    /**
     * Install one package
     * 
     * @param string $pkg_file Temporary file location
     * @param array $block
     * @return type
     */
    private function installOne($pkg_file, $block)
    {
        $destination = $_SERVER['PWD'].'/'.$block['filename'];
        \rename($pkg_file, $destination);
        return $destination;
    }
}
