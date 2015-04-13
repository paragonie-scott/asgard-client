<?php
namespace ParagonIE\AsgardClient\Storage;

class JSON implements \JsonSerializable
{
    private $path;
    private $data = [];
    
    public function __construct($path)
    {
        $this->path = $path;
        if (\file_exists($path)) {
            $this->data = \json_decode(
                \file_get_contents($path),
                true
            );
        }
    }
    
    /**
     * Append a value to an array
     * 
     * @param array $path ['grandparent', 'parent']
     * @param mixed $value
     */
    public function append($path = null, $value = '')
    {
        // Reference
        $target =& $this->data;
        while (!empty($path)) {
            $iter = \array_shift($path);
            if (!isset($target[$iter])) {
                $target[$iter] = [];
            }
            // We're looking for children
            $target =& $target[$iter];
        }
        // Set value by reference:
        $target []= $value;
        return $target;
    }
    
    /**
     * Set a value
     * 
     * @param array $path ['grandparent', 'parent']
     * @param mixed $value
     */
    public function set($path = null, $value = '')
    {
        // Reference
        $target =& $this->data;
        while (!empty($path)) {
            $iter = \array_shift($path);
            if (!isset($target[$iter])) {
                $target[$iter] = [];
            }
            // We're looking for children
            $target =& $target[$iter];
        }
        // Set value by reference:
        $target = $value;
        return $target;
    }
    
    /**
     * Get a value from the dataset
     * 
     * @param string $path
     */
    public function get($path = [])
    {
        $target =& $this->data;
        while (!empty($path)) {
            $iter = \array_shift($path);
            if (!isset($target[$iter])) {
                return null;
            }
            $target =& $target[$iter];
        }
        return $target;
    }
    
    /**
     * Return JSON Serializable data (interface requirement)
     * 
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->data;
    }
    
    /**
     * Save the JSON data
     * 
     * @return int|boolean
     */
    public function save()
    {
        return \file_put_contents(
            $this->path,
            \json_encode(
                $this->data,
                JSON_PRETTY_PRINT
            )
        );
    }
    
    /**
     * Ensure changes are saved implictly on script execution termination
     */
    public function __destruct() {
        $this->save();
    }
}