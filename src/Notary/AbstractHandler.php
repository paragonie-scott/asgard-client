<?php
namespace ParagonIE\AsgardNotary;

abstract class AbstractHandler
{
    public function __construct() {
        $this->config = \json_decode(
            \file_get_contents(
                CONFIGDIR.'/notary_server.json'
            ),
            true
        );
        $this->db = \ParagonIE\AsgardClient\Utilities::getStorageAdapter(
            'notary', 
            \file_get_contents(
                CONFIGDIR.'/config.json'
            ),
            true
        );
    }
    
    abstract public function get();
    
    public function post()
    {
        $args = \func_get_args();
        return $this->get(...$args);
    }
    
    public function get_xhr()
    {
        $args = \func_get_args();
        return $this->get(...$args);
    }
    
    public function post_xhr()
    {
        $args = \func_get_args();
        return $this->get(...$args);
    }
}