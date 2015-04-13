<?php
namespace ParagonIE\AsgardNotary;

class Utilities 
{
    /**
     * Prepare handlers for Toro
     * 
     * @param array $routes
     * @param string $prefix
     * @return array
     */
    public static function handlers(
        array $routes = [],
        $prefix = '\\ParagonIE\\AsgardNotary\\Handler\\'
    ) {
        foreach ($routes as $key => $value) {
            if ($value[0] !== '\\') {
                $routes[$key] = $prefix.$value;
            }
        }
        return $routes;
    }
}
