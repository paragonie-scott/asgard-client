<?php
namespace ParagonIE\AsgardClient\Storage\Adapters;

interface DBAdapterInterface extends AdapterInterface
{
    public function getPdo();
    public function createTables($label = '');
}
