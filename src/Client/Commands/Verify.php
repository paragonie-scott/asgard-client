<?php
namespace ParagonIE\AsgardClient\Commands;

use \ParagonIE\AsgardClient as Base;

class Verify extends Base\Command
{
    public $essential = false;
    public $display = 5;
    public $name = 'Verify';
    public $description = 'Check the signature for a package and verify its consistency in the distributed ledger.';

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
        
        echo $HTAB, "How to use this command:\n";
            echo $TAB, $this->c['cyan'], "asgard verify [package]", $this->c[''], "\n";
            echo $TAB, $HTAB, "Verify a previously downloaded pacakge.";
            echo "\n\n";
    }
    
    /**
     * Given a desired package name and a file stored locally, let's verify it!
     * 
     * @param string $pkg_name
     * @param string $tmp_file
     */
    public function check($pkg_name, $tmp_file)
    {
        // For each license key, attempt to get the block for a package name.
        $validLicense = false;
        $licenses = $this->db->select('licenses');
        if (empty($licenses)) {
            $block = $this->getBlockForPackage(
                $pkg_name
            );
        } else {
            foreach ($licenses as $lic) {
                $block = $this->getBlockForPackage(
                    $pkg_name,
                    $lic['publickey'],
                    $lic['secretkey']
                );
                if (!empty($block)) {
                    $validLicense = $lic;
                    break;
                }
            }
        }
        
        // If we get nothing, we should not proceed.
        if (empty($block)) {
            return false;
        }
        // Then, verify the signature on each leaf in the Merkle tree.
        $signed = $this->verifySignature(
            \json_decode($block['blockdata'], true)
        );
        if (!$signed) {
            return false;
        }
        
        // Check the ledger!
        if (!$this->verifyMerkleRoot($signed, $block['merkleroot'])) {
            return false;
        }
        
        // Finally, let's validate the checksums on the file.
        $blockData = $this->extractBlockData(
            $signed,
            $validLicense
        );
        
        if (empty($blockData)) {
            return false;
        }
        
        // Do all the blockchained checksums match the file?
        foreach ($blockData as $aBlock) {
            $checked = $this->verifyChecksums(
                $aBlock[0],
                $tmp_file
            );
            if (!$checked) {
                return false;
            }
        }
        $block['extracted'] = $blockData;
        return $block;
    }
    
    /**
     * Get a block from a random mirror
     * 
     * @param string $pkg_name
     * @param string $publickey
     * @param string $secretkey
     */
    private function getBlockForPackage($pkg_name, $publickey = null, $secretkey = null)
    {
        if (empty($this->http)) {
            $this->setup();
        }
        $request_uri = $this->basepath.self::$api['package']['block'].\urlencode($pkg_name);
        
        // Make the first request. Free packages will return here.
        if (empty($publickey)) {
            $postresp = $this->http->post($request_uri);
            $initial = \json_decode(
                $postresp, 
                true
            );
        } else {
            $initial = \json_decode(
                $this->http->post(
                    $request_uri,
                    [
                        'publickey' => $publickey
                    ]
                ), 
                true
            );
        }
        
        if (!empty($initial['block'])) {
            return $initial['block'];
        }
        
        if (empty($publickey) || empty($secretkey)) {
            return false;
        }
        
        // Challenge-response below
        
        $solution = Base\Utilities::challengeResponse(
            $initial['public_key'],
            $initial['nonce'],
            $initial['challenge'],
            $secretkey
        );
        
        // Second HTTP request
        $solved = \json_decode(
            $this->http->post(
                $request_uri,
                [
                    'publickey' => $publickey,
                    'response' => \base64_encode($solution)
                ],
            true)
        );
        if (!empty($initial['block'])) {
            return $solved['block'];
        }
    }
    
    /**
     * 
     * 
     * @param array $block
     * @param array $validLicense
     */
    private function extractBlockData($block, $validLicense)
    {
        if (\is_string($block) && \preg_match('#^p:#', $block)) {
            // Private package!
                // 'p' (for Private)
                // Client Public Key
                // Ephemeral Public Key
                // Nonce
                // Authenticated Ciphertext
            list( , $clientPublic, $serverPublic, $nonce, $ciphertext) =
                \explode(':', $block);

            if (!\hash_equals($clientPublic, $validLicense['publickey'])) {
                die("Public key didn't match. Decryption will fail!");
            }
            $secretkey = \base64_decode($validLicense['secretkey']);
            $eBoxKey = \Sodium::crypto_box_keypair_from_secretkey_and_publickey(
                $secretkey,
                \base64_decode($serverPublic)
            );

            $message = \Sodium::crypto_box_open(
                \base64_decode($ciphertext),
                \base64_decode($nonce),
                $eBoxKey
            );
            \Sodium::sodium_memzero($eBoxKey);
            if ($message === false) {
                die("Decryption failed!");
            }
            return \json_decode($message, true);
        }
        return $block;
    }
    
    private function setup()
    {
        $oMirror = $this->getCommandObject('Mirror');
        $mirror = $oMirror->getRandomMirror();
        list($domain, $basepath) = $oMirror->getDomainAndPath($mirror['url']);
        
        $this->http = new \ParagonIE\AsgardClient\HTTPS($domain);
        $this->basepath = $basepath;
    }
    
    /**
     * Verify the signature of a block
     * 
     * @param type $block
     */
    private function verifySignature($block)
    {
        $signed = \Sodium::crypto_sign_verify_detached(
            \base64_decode($block['signature']),
            \base64_decode($block['message']),
            \base64_decode(Base\MetaData::AUTHORIZED_PUBLICKEY) 
        );
        
        if ($signed === false) {
            die("Signature verification failed!");
        }
        
        return \json_decode(
            \base64_decode($block['message']),
            true
        );
    }
    
    /**
     * Verify the merkle root
     * 
     * @param array $blocks Array of all the block data
     * @param string $hash  Hex-encoded hash
     * @return boolean
     */
    private function verifyMerkleRoot($blocks, $hash)
    {
        $mt = new \ParagonIE\AsgardClient\Structures\MerkleTree($blocks);
        return $mt->isValidRoot(
            \Sodium::sodium_hex2bin($hash)
        );
    }
    
    /**
     * Verify the checksums on $tmp_file for the package we are verifying
     * 
     * @param array $blockdata
     * @param string $tmp_file
     * @return boolean
     */
    private function verifyChecksums($blockdata, $tmp_file)
    {
        $matches = 0;
        $filedata = \file_get_contents($tmp_file);
        // Now let's check all of the hashes
        foreach ($blockdata['checksums'] as $algo => $hash) {
            switch ($algo) {
                case 'BLAKE2b':
                    // We used libsodium
                    $line = \Sodium::crypto_generichash($filedata);
                    break;
                default:
                    // A simple hash (SHA256, etc)
                    $line = \hash($algo, $filedata, true);
            }
            
            if (\hash_equals($line, \Sodium::sodium_hex2bin($hash))) {
                ++$matches;
            } else {
                die("{$algo} hash did not match!");
            }
        }
        unset($filedata); // explicitly free
        return ($matches > 0);
    }
}
