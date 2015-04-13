<?php
namespace ParagonIE\AsgardClient\Networking;

/**
 * A simple HTTP interface -- doesn't need a lot of features
 */
interface HTTPInterface
{
    public function get($url, $params = [], $options = []);
    public function head($url, $params = [], $options = []);
    public function post($url, $params = [], $options = []);
    
    public function getLastBody();
    public function getLastHeader();
    public function getLastResponseCode();
}
