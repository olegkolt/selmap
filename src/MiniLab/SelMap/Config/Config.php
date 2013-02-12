<?php

namespace MiniLab\SelMap\Config;

class Config
{
    private static $options = array();
    public static function get($name)
    {
        if(!isset(self::$options[$name])) {
            $json = file_get_contents(__DIR__ . "/data/" . $name . ".json");
            self::$options[$name] = json_decode($json, true);
        }
        return self::$options[$name];
    }
}