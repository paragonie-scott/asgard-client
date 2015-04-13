<?php
namespace ParagonIE\AsgardClient;

abstract class Command
{
    const TAB_SIZE = 8;
    
    public $essential = false;
    public $display = 65535;
    public $name = 'CommandName';
    public $description = 'CLI description';
    public $tag = [
        'color' => '',
        'text' => ''
    ];
    public static $cache = []; // Cache references to other commands
    public static $userConfig; // Current user's configuration
    
    public static $api = [
        'blockchain' => [
            'new' => '/blockchain/since/',
            'hash' => '/blockchain/hash/',
            'id' => '/blockchain/id/'
        ],
        'package' => [
            'latest' => '/latest/',
            'block' => '/lastblock/',
            'download' => '/download/'
        ],
        'notaries' => [
            'publish' => '/notaries/publish/',
            'unpublish' => '/notaries/unpublish/'
        ]
    ];
    
    // Database adapter
    protected $db;
    
    // Blockchain storage adapter
    protected $bc;
    
    // BASH COLORS
    protected $c = [
        '' => "\033[0;39m",
        'red'       => "\033[0;31m",
        'green'     => "\033[0;32m",
        'blue'      => "\033[1;34m",
        'cyan'      => "\033[1;36m",
        'silver'    => "\033[0;37m",
        'yellow'    => "\033[0;93m"
    ];
    
    /**
     * Execute a command
     */
    abstract public function fire(array $args = []);

    /**
     * Display command options
     */
    abstract public function usageInfo();


    /**
     * Return the size of hte current terminal window
     *
     * @return array (int, int)
     */
    public function getScreenSize()
    {
        $output = [];
        \preg_match_all(
            "/rows.([0-9]+);.columns.([0-9]+);/",
            \strtolower(\exec('stty -a |grep columns')),
            $output
        );
        if (\sizeof($output) === 3) {
           return [
               'width' => $output[2][0],
               'height' => $output[1][0]
           ];
        }
    }

    /**
     * Set the storage device used for this command (often not used)
     * 
     * @param \ParagonIE\AsgardClient\Storage\Adapters\AdapterInterface $db
     * @param \ParagonIE\AsgardClient\Storage\Adapters\AdapterInterface $bc
     */
    public function setStorage(
        \ParagonIE\AsgardClient\Storage\Adapters\AdapterInterface $db,
        \ParagonIE\AsgardClient\Storage\Adapters\AdapterInterface $bc = null
    ) {
        $this->db = $db;
        if (isset($bc)) {
            // Are we storing a blockchain adapter?
            $this->bc = $bc;
        }
        if (empty(self::$userConfig)) {
            self::$userConfig = new \ParagonIE\AsgardClient\Storage\JSON(
                ASGARD_LOCAL_CONFIG.'/asgard.json'
            );
        }
    }
    
    /**
     * Return a command
     * 
     * @param string $name
     * @param boolean $cache
     */
    public function getCommandObject($name, $cache = true)
    {
        $obj = self::getCommandStatic($name, $cache);
        if (!empty($this->db) && !empty($this->bc)) {
            $obj->setStorage(
                $this->db,
                $this->bc
            );
        }
        return $obj;
    }
    
    /**
     * Return a command (statically callable)
     * 
     * @param string $name
     * @param boolean $cache
     * @return \ParagonIE\AsgardClient\Command
     */
    public static function getCommandStatic($name, $cache = true)
    {
        $_name = '\\ParagonIE\\AsgardClient\\Commands\\'.\ucfirst($name);
        if (!empty(self::$cache[$name])) {
            return self::$cache[$name];
        }
        if ($cache) {
            self::$cache[$name] = new $_name;
            return self::$cache[$name];
        }
        return new $_name;
    }
}
