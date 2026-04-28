<?php
namespace Src\Core;

class Event {
    private static $listeners = [];

    public static function listen($event, $callback) {
        self::$listeners[$event][] = $callback;
    }

    public static function fire($event, $data = []) {
        if (isset(self::$listeners[$event])) {
            foreach (self::$listeners[$event] as $callback) {
                // Support for Class@method string syntax
                if (is_string($callback) && strpos($callback, '@') !== false) {
                    [$class, $method] = explode('@', $callback);
                    $instance = new $class();
                    $instance->$method($data);
                } 
                // Support for Closures
                elseif (is_callable($callback)) {
                    call_user_func($callback, $data);
                }
            }
        }
    }
}