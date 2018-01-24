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
        'host' => '116.196.116.90',
        'port' => 3306,
        'user' => 'xiaomage',
        'password' => 'admin123',
        'database' => 'demo',
        'options' => [
            \PDO::MYSQL_ATTR_INIT_COMMAND => 'set names utf8'
        ],
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
        $config = self::$config;

        if (empty($name)) {
            $name = $config['driver'] . ':' . date('Y-m-d');
        }

        if (!isset(self::$instance[$name])) {
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