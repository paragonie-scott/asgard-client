<?php
namespace ParagonIE\AsgardClient;

use \ParagonIE\AsgardClient\Storage\Adapters as StorageAdapters;

abstract class Utilities 
{
    /**
     * Make sure only valid characters make it in column/table names
     * 
     * @ref https://stackoverflow.com/questions/10573922/what-does-the-sql-standard-say-about-usage-of-backtick
     * 
     * @param string $string - table or column name
     * @param string $dbengine - database engine
     * @param boolean $quote - certain SQLs escape column names (i.e. mysql with `backticks`)
     * @return string
     */
    public static function escapeSqlIdentifier($string, $dbengine = 'sqlite', $quote = true)
    {
        // Force UTF-8
        // Strip out invalid characters
        $str = \preg_replace(
            '/[^0-9a-zA-Z_]/',
            '',
            $string
        );
        
        // The first character cannot be [0-9]:
        if (\preg_match('/^[0-9]/', $str)) {
            // FATAL ERROR
            throw  new \Exception(
                "Invalid identifier: Must begin with a letter or undescore."
            );
        }
        
        if ($quote) {
            switch ($dbengine) {
                case 'mssql':
                    return '['.$str.']';
                case 'mysql':
                    return '`'.$str.'`';
                default:
                    return '"'.$str.'"';
            }
        }
        return $str;
    }
    
    public static function getStorageAdapter($label, array $config)
    {
        $settings = null;
        foreach ($config['storage'] as $lbl => $data) {
            if ($lbl === $label) {
                $settings = $data;
            }
        }
        if (empty($settings)) {
            throw new Exception("Error parsing configuration");
        }
        
        $create = false;
        // Okay, now let's figure out the path
        switch ($settings['driver']) {
            case 'sqlite':
                if (preg_match('#^[a-zA-Z0-9_]#', $settings['path'][0])) {
                    $settings['path'] = ASGARD_ROOT.'/data/'.$settings['path'];
                }
                if (!\file_exists($settings['path'])) {
                    // Set a flag to determine whether or not to create tables
                    $create = true;
                }
                $adapter = new StorageAdapters\SqliteAdapter(
                    $settings['path']
                );
                break;
            default:
                throw new Exception("Storage Driver not implemented!");
        }
        
        // Do we need to creat the schemas
        if ($create) {
            if ($adapter instanceof StorageAdapters\DBAdapterInterface) {
                $adapter->createTables($label);
            }
        }
        return $adapter;
    }
    
    public static function challengeResponse(
        $serverPublicKey,
        $serverNonce,
        $challenge,
        $secretkey
    ) {
        $_serverPublicKey = \base64_decode($serverPublicKey);
        $_serverNonce = \base64_decode($serverNonce);
        $_challenge = \base64_decode($challenge);
        // Calculate symmetric encryption key
        $eBoxKey = \Sodium::crypto_box_keypair_from_secretkey_and_publickey(
            $secretkey,
            $_serverPublicKey
        );
        
        // Let's grab our solution
        $result = \Sodium::crypto_box_open(
            $_challenge,
            $_serverNonce,
            $eBoxKey
        );
        
        \Sodium::sodium_memzero($eBoxkey);
        return $result;
    }
}
