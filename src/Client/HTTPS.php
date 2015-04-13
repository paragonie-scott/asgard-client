<?php
namespace ParagonIE\AsgardClient;
use \ParagonIE\AsgardClient\Networking as Netw;

class HTTPS
{
    private $http;
    private $host;
    
    public function __construct($host)
    {
        if (\function_exists('\\curl_init')) {
            $this->http = new Netw\CurlHTTP;
        } else {
            // $this->http = new Netw\RawHTTP;
            throw new \Exception(
               "We have not implemented a raw HTTP driver yet. Please install cURL."
            );
        }
        if (\preg_match('#^[a-z0-9A-Z]+://#', $host)) {
            // Explicit protocol enforcement
            $this->host = $host;
        } else {
            // We default to HTTPS
            $this->host = 'https://' . $host;
        }
    }
    
    /**
     * Grab the value from a previous response header
     * 
     * @param string $key
     * @return boolean|string
     */
    public function lastHeader($key)
    {
        $header = $this->http->lastHeader;
        if (empty($header)) {
            return false;
        }
        foreach (\explode("\n", $header) as $head) {
            if (\preg_match('#^'.\preg_quote($key, '#').':\s*(.*)$#', $head, $m)) {
                return $m[1];
            }
        }
        return null;
    }
    
    /**
     * Make an HTTP GET; enforce TLS
     * 
     * @param string $path
     * @param mixed $params
     * @return string
     */
    public function get($path, $params = [])
    {
        return $this->http->get(
            $this->host.$path,
            $params
        );
    }
    
    /**
     * Make an HTTP GET; enforce TLS
     * 
     * @param string $path
     * @param mixed $params
     * @return string
     */
    public function head($path, $params = [])
    {
        return $this->http->head(
            $this->host.$path,
            $params
        );
    }
    
    /**
     * Make an HTTP POST; enforce TLS
     * 
     * @param string $path
     * @param mixed $params
     * @return string
     */
    public function post($path, $params = [])
    {
        return $this->http->post(
            $this->host.$path,
            $params
        );
    }
}
