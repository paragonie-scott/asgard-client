<?php
namespace ParagonIE\AsgardClient\Commands;

use \ParagonIE\AsgardClient as Base;

class Download extends Base\Command
{
    public $essential = false;
    public $display = 4;
    public $name = 'Download';
    public $description = "Download a package from ASGard without verifying its integrity.";
    public $tag = [
        'color' => 'red',
        'text' => 'INSECURE'
    ];
    
    # Note to self: the constant is ASGARD_USER_HOME
    
    public function __construct()
    {
        if (!\is_dir(ASGARD_LOCAL_CONFIG.'/bin')) {
            \mkdir(ASGARD_LOCAL_CONFIG.'/bin', 0770);
        }
        if (!\is_dir(ASGARD_LOCAL_CONFIG.'/tmp')) {
            \mkdir(ASGARD_LOCAL_CONFIG.'/tmp', 0770);
        }
    }
    
    public function setup()
    {
        $oMirror = $this->getCommandObject('Mirror');
        $mirror = $oMirror->getRandomMirror();
        list($domain, $basepath) = $oMirror->getDomainAndPath($mirror['url']);
        
        $this->http = new \ParagonIE\AsgardClient\HTTPS($domain);
        $this->basepath = $basepath;
    }

    /**
     * Execute the download command
     *
     * @param array $args - CLI arguments
     * @echo
     * @return null
     */
    public function fire(array $args = [])
    {
        if (empty($args)) {
            die("You did not specify a package name\n");
        }
        $this->setup();
        echo "I hope you know what you're doing...\n";
        
        $licenses = $this->db->select('licenses');
        if (empty($licenses)) {
            return $this->unlicensedGet(
                $args[0]
            );
        }
        foreach ($licenses as $lic) {
            echo 'Fetching updates..';
            $updates = $this->updatePackageInfo(
                $lic['publickey'],
                $lic['secretkey'],
                $args[0]
            );
            echo '.', "\n";
            if (empty($updates)) {
                continue;
            }
            echo 'Trying public key ', $lic['publickey'], "\n";
            $file = $this->download(
                $args[0],
                $lic['publickey'],
                $lic['secretkey'],
                true
            );
            if (!empty($file)) {
                echo $file;
            }
        }
    }
    
    /**
     * Silently fetch a file (for use in other commands)
     * 
     * @param string $package name of package
     * @param boolean $setup set up random mirror?
     * 
     * @return array [updates, file name, license public key]
     */
    public function silentFetch($package, $setup = true)
    {
        if ($setup) {
            $this->setup();
        }
        $licenses = $this->db->select('licenses');
        if (empty($licenses)) {
            
            $updates = $this->unlicensedGet(
                $package
            );
            $file = $this->downloadFile(
                $package
            );
            return [$updates, $file, null];
        }
        foreach ($licenses as $lic) {
            $updates = $this->updatePackageInfo(
                $lic['publickey'],
                $lic['secretkey'],
                $package
            );
            if (empty($updates)) {
                continue;
            }
            $file = $this->downloadFile(
                $package,
                $lic['publickey'],
                $lic['secretkey']
            );
            
            if (!empty($file)) {
                return [$updates, $file, $lic['publickey']];
            }
        }
        return false;
    }
    
    /**
     * Silently fetch files (for use in other commands)
     * 
     * @param array $packages   List of package names
     * @param array $publickeys  Which license key to use (by public key)
     * @return array [updates, file name, publickeys]
     */
    public function silentFetchAll($packages, $publickeys)
    {
        $this->setup();
        $result = [];
        foreach ($packages as $key => $pkg) {
            $publickey = $publickeys[$key];
            $secretkey = $this->db->selectOne(
                'licenses',
                'secretkey',
                ['publickey' => $publickey]
            );
            if (empty($secretkey)) {
                continue;
            }
            
            // Let's fetch the updates from the server for this package
            $updates = $this->updatePackageInfo(
                $publickey,
                $secretkey,
                $pkg
            );
            if (empty($updates)) {
                // There were no updates for this package.
                continue;
            }
            $file = $this->downloadFile(
                $pkg,
                $publickey,
                $secretkey
            );
            $result[0][$key] = $updates;
            $result[1][$key] = $file;
            $result[2][$key] = $publickey;
        }
        return $result;
    }
    
    /**
     * Download a package
     * 
     * @param string $package Package name
     * @param string $publickey Public key (base64 encoded)
     * @param string $secretkey Secret key (base64 encoded)
     * @param boolean $echo Display verbose information?
     * 
     * @return boolean|string
     */
    public function downloadFile($package, $publickey = null, $secretkey = null, $echo = false)
    {
        $request_uri = $this->basepath.self::$api['package']['download'].\urlencode($package);
        if (empty($publickey)) {
            $initial = $this->http->post(
                $request_uri
            );
        } else {
            $initial = $this->http->post(
                $request_uri,
                [
                    'publickey' => $publickey
                ]
            );
        }
        $chal = \json_decode($initial, true);
        // Is this a free package? If so, we download it immediately!
        if (!empty($chal['url'])) {
            $file = \file_get_contents($chal['url']);
            $filename = $this->saveTemp($file);
            if ($echo) {
                echo 'Saving temporary file to ', $filename, "\n";
            }
            return $filename;
        }
        if (empty($publickey) || empty($secretkey)) {
            if ($echo) {
                echo 'No license key.', "\n\n";
            }
            return false;
        }
        if (!empty($chal['error'])) {
            if ($echo) {
                echo 'Server Response:', "\n", $chal['error'], "\n\n";
            }
            return false;
        }
        
        if (empty($chal['challenge']) || empty($chal['nonce']) || empty($chal['publickey'])) {
            if ($echo) {
                echo $initial;
            }
            return false;
        }
        
        // We use base64 for JSON transport
        $solution = Base\Utilities::challengeResponse(
            $chal['public_key'],
            $chal['nonce'],
            $chal['challenge'],
            $secretkey
        );
        
        // Second HTTP request
        $solved = $this->http->post(
            $request_uri,
            [
                'publickey' => $publickey,
                'response' => \base64_encode($solution)
            ]
        );
        // Was it JSON?
        if ($this->http->lastHeader('Content-Type') !== 'application/json') {
            $filename = $this->saveTemp($solved);
            if ($echo) {
                echo 'Saving temporary file to ', $filename, "\n";
            }
            return $filename;
        }
        
        // If we are still here, we had an error.
        $srv = \json_decode($solved, true);
        if (!empty($srv['error'])) {
            if ($echo) {
                echo 'Server Response:', "\n", $srv['error'], "\n\n";
            }
            return false;
        }
        if ($echo) {
            echo $solved, "\n";
        }
        return false;
    }
    
    /**
     * Save text to a temporary file, return filename
     * 
     * @param string $filedata
     * @return string
     */
    public function saveTemp($filedata)
    {
        $file = \tempnam(ASGARD_LOCAL_CONFIG.'/tmp', 'pkg-');
        \file_put_contents($file, $filedata);
        return $file;
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
        
        echo $HTAB. "How to use this command:\n";
            echo $TAB, $this->c['cyan']."asgard download [package]", $this->c[''], "\n";
            echo $TAB, $HTAB, $this->c['red'], "NOT RECOMMENDED! FOR ADVANCED USERS ONLY!", $this->c[''], "\n";
            echo $TAB, $HTAB, $this->c['yellow'], "USE ", $this->c['green']."asgard get [package]", $this->c['yellow'], " INSTEAD!", $this->c[''], "\n";
            echo $TAB, $HTAB, "Simply downloads a package. Does not perform any verification.\n\n";
            echo $TAB, $HTAB, "Please don't use this command alone without a very good reason.";
            echo "\n\n";
    }
    
    /**
     * Let's get the latest package information from the mirror
     * 
     * @param string $publickey Public key (base64 encoded)
     * @param string $secretkey Secret key (base64 encoded)
     * @param string $package (optional; defaults to grabbing all)
     * @return array
     */
    public function updatePackageInfo($publickey, $secretkey, $package = null)
    {
        $requesting = [];
        if (!empty($package)) {
            // We just need the one
            $requesting[] = $package;
        } else {
            // Let's grab all the names
            foreach (self::$userConfig->get(['packages']) as $pkg) {
                $requesting[] = $pkg['name'];
            }
        }
        
        
        // We send off our public key, and get an encrypted response back.
        $apiResponse = $this->http->post(
            $this->basepath.self::$api['package']['latest'],
            [
                'packages' => $requesting,
                'publickey' => $publickey
            ]
        );
        
        $api = \json_decode($apiResponse, true);
        if (!empty($api['error']) || empty($api['packages']) || empty($api['nonce']) || empty($api['publickey'])) {
            return false;
        }
        
        $serverPublicKey = \base64_decode($api['publickey']);
        $serverNonce = \base64_decode($api['nonce']);
        $ciphertext = \base64_decode($api['packages']);
        
        // Calculate symmetric encryption key
        $eBoxKey = \Sodium::crypto_box_keypair_from_secretkey_and_publickey(
            $secretkey,
            \base64_decode($serverPublicKey)
        );
        
        // Let's grab our solution
        $updates = \Sodium::crypto_box_open(
            $ciphertext,
            $serverNonce,
            $eBoxKey
        );
        
        return \json_decode($updates, true);
    }
    
    
    /**
     * Let's get the latest package information from the mirror
     * 
     * @param string $package (optional; defaults to grabbing all)
     * @return array
     */
    public function unlicensedGet($package = null)
    {
        $requesting = [];
        if (!empty($package)) {
            // We just need the one
            $requesting[] = $package;
        } else {
            // Let's grab all the names
            foreach (self::$userConfig->get(['packages']) as $pkg) {
                $requesting[] = $pkg['name'];
            }
        }
        
        // We send off our public key, and get an encrypted response back.
        $apiResponse = $this->http->post(
            $this->basepath.self::$api['package']['latest'],
            [
                'packages' => $requesting
            ]
        );
        
        $api = \json_decode($apiResponse, true);
        
        $pkg = \json_decode(\base64_decode($api['packages']), true);
        return $pkg;
    }
}
