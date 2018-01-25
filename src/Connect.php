<?php

namespace Magein\Db;

class Connect
{
    /**
     * @var array
     */
    private static $instance = [];

    /**
     * pdo 配置
     * @var array
     */
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
     * @param array $config 数据库配置
     * @param bool $reconnect 强制重新链接
     * @return mixed
     */
    public static function instance(array $config = null, $reconnect = false)
    {
        if (self::$instance && !$reconnect) {
            return self::$instance;
        }

        if ($config) {
            $config = array_merge(self::$config, $config);
        }

        self::$instance = self::connect($config);

        return self::$instance;
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