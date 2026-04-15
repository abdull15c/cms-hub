<?php
namespace Src\Core;

class Container {
    private static $instances = [];

    public static function set($key, $instance) {
        self::$instances[$key] = $instance;
    }

    public static function get($key) {
        if (!isset(self::$instances[$key])) {
            throw new \Exception("Service not found: " . $key);
        }
        return self::$instances[$key];
    }

    public static function has($key) {
        return isset(self::$instances[$key]);
    }
}