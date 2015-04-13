<?php
namespace ParagonIE\AsgardClient\Commands;

use \ParagonIE\AsgardClient as Base;

class Flush extends Base\Command
{
    public $essential = false;
    public $display = 9;
    public $name = 'Flush';
    public $description = 'Clears the distributed ledger. Only recommended for advanced users.';

    /**
     * Fire the command!
     * 
     * @param array $args
     */
    public function fire(array $args = [])
    {
        \unlink(ASGARD_ROOT.'/data/ddl/sqlite_blockchain.sql');
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
            echo $TAB, $this->c['cyan'], 'asgard flush', $this->c[''], "\n";
            echo $TAB, $HTAB, 'Clears out the distributed ledger.';
            echo "\n\n";
    }
}
