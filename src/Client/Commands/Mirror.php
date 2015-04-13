<?php
namespace ParagonIE\AsgardClient\Commands;

use \ParagonIE\AsgardClient as Base;

class Mirror extends Base\Command
{
    public $essential = false;
    public $display = 13;
    public $name = 'Mirror';
    public $description = 'Manage ASGard Mirrors. (Not implemented yet.)';

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
        
        echo $HTAB.'How to use this command:', "\n";
            echo $TAB, $this->c['cyan'], 'asgard mirror', $this->c[''], "\n";
            echo $TAB, $HTAB, 'List the mirrors.';
            echo "\n\n";
    }
    
    /**
     * Get a randomly selected mirror from our database
     * 
     * @return array
     */
    public function getRandomMirror()
    {
        $iCount = $this->db->selectOne('mirrors', 'count(id)');
        if ($iCount < 0) {
            throw new \Exception("There are no mirrors in the database.");
        }
        $iRandom = \Sodium::randombytes_uniform($iCount);
        return $this->db->selectRow('mirrors', [], [], [], [], $iRandom);
    }
    
    /**
     * Split this out
     * 
     * @param string $mirrorURL
     * @return array
     * @throws \Exception
     */
    public function getDomainAndPath($mirrorURL)
    {
        $matches = [];
        // $mirror['url']
        if (\preg_match('#^[A-Za-z]+://([^/]+)(/.*)?#', $mirrorURL, $matches)) {
            $domain = $matches[1];
            $basepath = isset($matches[2])
                ? $matches[2]
                : '';
        } elseif (\preg_match('#^([^/]+)(/.*)?#', $mirrorURL, $matches)) {
            $domain = $matches[1];
            $basepath = isset($matches[2])
                ? $matches[2]
                : '';
        } else {
            throw new \Exception("Invalid mirror URL");
        }
        return [$domain, $basepath];
    }
}
