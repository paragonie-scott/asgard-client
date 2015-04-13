<?php
namespace ParagonIE\AsgardClient\Commands;

use \ParagonIE\AsgardClient as Base;

class Package extends Base\Command
{
    public $essential = false;
    public $display = 11;
    public $name = 'Package Info.';
    public $description = 'Get information about a particular package.';

    /**
     * Execute the update command
     *
     * @param array $args - CLI arguments
     * @echo
     * @return null
     */
    public function fire(array $args = [])
    {

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
        
        echo $HTAB, 'How to use this command:', "\n";
            echo $TAB, $this->c['cyan'], 'asgard package [package name]', $this->c[''], "\n";
            echo $TAB, $HTAB, 'Gets information about a particular package.';
            echo "\n\n";
    }
}
