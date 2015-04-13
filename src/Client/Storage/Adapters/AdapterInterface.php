<?php
namespace ParagonIE\AsgardClient\Storage\Adapters;

interface AdapterInterface
{
    public function delete($table, $where = []);
    public function insert($table, $rows);
    public function select($table, $columns = [], $where = []);
    public function update($table, $rows, $where = []);
}
