<?php
namespace Src\Core;

class Container {
    private static $instances = [];
    private static $factories = [];

    public static function set($key, $instance) {
        self::$instances[$key] = $instance;
    }

    public static function factory($key, callable $factory) {
        self::$factories[$key] = $factory;
    }

    public static function get($key) {
        if (isset(self::$instances[$key])) {
            return self::$instances[$key];
        }
        if (isset(self::$factories[$key])) {
            self::$instances[$key] = (self::$factories[$key])();
            return self::$instances[$key];
        }
        throw new \Exception("Service not found: " . $key);
    }

    public static function has($key) {
        return isset(self::$instances[$key]) || isset(self::$factories[$key]);
    }
}
