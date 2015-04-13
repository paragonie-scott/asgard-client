<?php
namespace ParagonIE\AsgardClient\Networking;

class RawHTTP implements HTTPInterface
{
    private $lastHeader;
    private $lastBody;
    private $lastResponseCode;
    
    public function get($url, $params = [], $options = [])
    {
        
    }
    public function head($url, $params = [], $options = [])
    {
        
    }
    public function post($url, $params = [], $options = [])
    {
        
    }
    
    public function getLastBody()
    {
        
    }
    public function getLastHeader()
    {
        
    }
    public function getLastResponseCode()
    {
        
    }
}
