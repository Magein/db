<?php

namespace Db;

class Connect
{
    /**
     * @var array
     */
    private static $instance = [];

    private function __construct()
    {

    }

    private function __clone()
    {

    }

    private static $config = [
        'driver' => 'mysql',
        'host' => '',
        'port' => 3306,
        'user' => '',
        'password' => '',
        'database' => '',
        'options' => [],
        'charset' => 'utf-8',
        'prefix' => '',
    ];

    /**
     * @param null $config
     * @param null $name
     * @return \PDO
     */
    public static function instance($config = null, $name = null)
    {
        if (empty($name)) {
            $name = date('Y-m-d');
        }

        $name = sha1(md5($name));

        if (!isset(self::$instance[$name])) {

            if ($config) {
                $config = array_merge(self::$config, $config);
            }

            self::$instance[$name] = self::connect($config);
        }

        return self::$instance[$name];
    }

    /**
     * @param null $config
     * @return \PDO
     */
    private static function connect($config = null)
    {

        $driver = $config['driver'];
        $host = $config['host'];
        $port = $config['port'];
        $database = $config['database'];
        $user = $config['user'];
        $password = $config['password'];
        $options = $config['options'];

        $dns = "$driver:host=$host:$port;dbname=$database";

        return new \PDO($dns, $user, $password, $options);
    }
}