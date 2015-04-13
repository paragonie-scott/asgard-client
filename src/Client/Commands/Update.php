<?php
namespace ParagonIE\AsgardClient\Commands;

use \ParagonIE\AsgardClient as Base;

class Update extends Base\Command
{
    public $essential = true;
    public $display = 3;
    public $name = 'Update';
    public $description = 'Update ASGard-protected packages.';
    public $tag = [
        'color' => 'green',
        'text' => 'Secure'
    ];

    /**
     * Execute the update command
     *
     * @param array $args - CLI arguments
     * @echo
     * @return null
     */
    public function fire(array $args = [])
    {
        $this->getCommandObject('sync')->silentSync();
        $installed = self::$userConfig->get(['packages']);
        
        $packages = [];
        $publickeys = [];
        foreach ($installed as $pkg) {
            $packages[] = $pkg['name'];
            $publickeys[] = $pkg['publickey'];
        }
        
        if (!empty($args)) {
            // Update one package
            if (!\in_array($args[0], $packages)) {
                // We need to install it!
                return $this->getCommandObject('Get')->fire($args[0]);
            }
            list($updates, $file, $licenses) = $this->getCommandObject('download')->silentFetchAll(
                [
                    $args[0]
                ]
            );
        } else {
            // Let's get updates for every package
            list($updates, $file, $licenses) = $this->getCommandObject('download')->silentFetchAll(
                $packages, $publickeys
            );
        }
        
        $num = \count($updates);
        for ($i = 0; $i < $num; ++$i) {
            $this->upgrade($file[$i], $updates[$i], $licenses[$i], $packages[$i]);
        }
        
    }

    /**
     * Display the usage information for this command.
     * 
     * @echo
     * @return null
     */
    public function usageInfo()
    {
        $TAB = str_repeat(' ', self::TAB_SIZE);
        $HTAB = str_repeat(' ', ceil(self::TAB_SIZE / 2));
        
        echo $HTAB.'How to use this command:', "\n";
            echo $TAB, $this->c['cyan'], 'asgard update', $this->c[''], "\n";
            echo $TAB, $HTAB, 'Securely update all packages downloaded with ASGard.';
            echo "\n\n";
            
            echo $TAB, $this->c['cyan'], 'asgard update [package]', $this->c[''], "\n";
            echo $TAB, $HTAB, "Securely update a single package.";
            echo "\n\n";
    }
    
    /**
     * Install a package, given a destination
     * 
     * @param string $pkg_file
     * @param string $updates
     * @param array  $pubkey
     * @param string $select
     */
    private function upgrade($pkg_file, $updates, $pubkey, $select = null)
    {
        $id = null;
        $pack = false;
        // If it's not already installed, we need to install it instead!
        foreach (self::$userConfig->get(['packages']) as $key => $pkg) {
            if ($pkg['name'] === $select) {
                $id = $key;
                $pack = $pkg;
                break;
            }
        }
        $serverInfo = null;
        foreach ($updates as $upd) {
            if ($upd['name'] === $select) {
                $serverInfo = $upd;
                break;
            }
        }
        if (!$pack) {
            // Nope.
            return $this->getCommandObject('Install')->fire($select);
        }
        $now = new \DateTime('NOW');
        
        $block = $this->getCommandObject('verify')->check($select, $pack);
        
        $pack['version'] = $block['version'];
        $pack['blockhash'] = $block['hash'];
        $pack['date'] = $now->format('c');
        self::$userConfig->get(['packages'][$id], $pack);
        
        $oldFile = \tmpfile(ASGARD_LOCAL_CONFIG.'/tmp');
        \rename($pack['location'], $oldFile);
        \rename($pkg_file, $pack['location']);
    }
}
