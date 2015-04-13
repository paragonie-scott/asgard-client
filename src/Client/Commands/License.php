<?php
namespace ParagonIE\AsgardClient\Commands;

use \ParagonIE\AsgardClient as Base;

class License extends Base\Command
{
    public $essential = true;
    public $display = 8;
    public $name = 'Licenses';
    public $description = 'Manage your ASGard license keys.';

    /**
     * Fire the command!
     * 
     * @param array $args
     */
    public function fire(array $args = [])
    {
        if (count($args) < 1) {
            return $this->usageInfo();
        }
        switch ($args[0]) {
            case 'add':
                return $this->add(
                    \array_values(\array_splice($args, 1))
                );
            case 'ls':
            case 'list':
                return $this->showAll();
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
            echo $TAB, $this->c['cyan'], 'asgard license add [licensekey]', $this->c[''], "\n";
            echo $TAB, $HTAB, 'Add a license (required for access to private packages)';
            echo "\n\n";
            
            echo $TAB, $this->c['cyan'], 'asgard license ls', $this->c[''], "\n";
            echo $TAB, $this->c['cyan'], 'asgard license list', $this->c[''], "\n";
            echo $TAB, $HTAB, 'Display information about your saved license keys';
            echo "\n\n";
    }
    
    public function add($args = [])
    {
        
    }
    
    public function showAll()
    {
        $aLicenses = $this->db->select('licenses');
        
    }
}
