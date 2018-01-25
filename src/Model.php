<?php

namespace Magein\Db;

class Model
{
    /**
     * @var array
     */
    protected $options = [
        'field' => '*'
    ];

    /**
     * @var string
     */
    private $sql = '';

    /**
     * @var array
     */
    private $templateSql = [
        'insert' => 'insert into %table% (%name%) values(%values%)',
        'update' => 'update %table% set %set% %where% %order% %limit%',
        'select' => 'select %field% from %table% %where% %order% %group% %having% %limit%',
        'delete' => 'delete from %table% %where%  %order% %limit%',
    ];

    /**
     * @var bool
     */
    private $fetchSql = false;

    /**
     * @var string
     */
    protected $table = '';

    /**
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * @var bool
     */
    protected $insertCreateTime = false;

    /**
     * @var string
     */
    protected $createTimeField = '';

    /**
     * @var string
     */
    protected $createTimeFormat = '';

    /**
     * @var bool
     */
    protected $autoUpdateTime = false;

    /**
     * @var \PDO
     */
    protected $db;

    /**
     * Model constructor.
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->connect($config);
    }

    /**
     * @return string
     */
    public function getLastSql()
    {
        return $this->sql;
    }

    /**
     * @return string
     */
    public function getTableName()
    {
        return $this->table;
    }

    /**
     * @param array $config
     * @param bool $reconnect
     * @return $this
     */
    public function connect(array $config = [], $reconnect = false)
    {
        $this->db = Connect::instance($config, $reconnect);

        return $this;
    }

    /**
     * @param string $table
     * @return $this
     */
    public function table(string $table)
    {
        $this->options['table'] = $table;

        return $this;
    }

    /**
     * @param string $field
     * @return $this
     */
    public function field(string $field)
    {
        $this->options['field'] = $field;

        return $this;
    }

    /**
     * @param string|array $where
     * @param null $express
     * @param null $value
     * @return array|string
     */
    private function parseWhere($where, $express = null, $value = null)
    {
        $whereStr = [];

        $parseStr = function (string $where, $express, $value) {
            switch ($express) {
                case 'not in':
                case 'in':
                    if (is_string($value)) {
                        $value = explode(',', $value);
                    }

                    $value = array_map(function ($item) {
                        return $this->parseValue($item);
                    }, $value);

                    $parseStr = $express . ' (' . implode(',', $value) . ')';

                    break;
                case 'between':
                    if (is_string($value)) {
                        $value = explode(',', $value);
                    }
                    list($start, $end) = $value;
                    $parseStr = $express . ' ' . $this->parseValue($start) . ' and ' . $this->parseValue($end);
                    break;
                default:
                    if (null !== $express && null !== $value) {
                        $parseStr = $express . ' ' . $this->parseValue($value);
                    } else {
                        $parseStr = ' = ' . $this->parseValue($express);
                    }
                    break;
            }

            return $where . ' ' . $parseStr;
        };

        if (is_array($where)) {
            foreach ($where as $key => $item) {
                if (is_array($item)) {
                    list($expressStr, $valueStr) = $item;
                } else {
                    $expressStr = $item;
                    $valueStr = $value;
                }
                $whereStr [] = $parseStr($key, $expressStr, $valueStr);
            }
        } elseif ($express) {
            $whereStr[] = $parseStr($where, $express, $value);
        } else {
            $whereStr[] = $where;
        }

        $whereStr = '(' . implode(' and ', $whereStr) . ')';

        return $whereStr;
    }

    /**
     * @param array|string $where
     * @param null $express
     * @param null $value
     * @return $this
     */
    public function where($where, $express = null, $value = null)
    {
        $whereStr = $this->parseWhere($where, $express, $value);

        if (!isset($this->options['where'])) {
            $this->options['where'] = $whereStr;
        } else {
            $this->options['where'] .= ' and ' . $whereStr;
        }

        return $this;
    }

    /**
     * @param array|string $where
     * @param null $express
     * @param null $value
     * @return $this
     */
    public function whereOr($where, $express = null, $value = null)
    {
        $whereStr = $this->parseWhere($where, $express, $value);

        if (!isset($this->options['where'])) {
            $this->options['where'] = $whereStr;
        } else {
            $this->options['where'] .= ' or ' . $whereStr;
        }

        return $this;
    }

    /**
     * @param string $order
     * @return $this
     */
    public function order(string $order)
    {
        $this->options['order'] = $order;
        return $this;
    }

    /**
     * @param int $offset
     * @param int $limit
     * @return $this
     */
    public function limit(int $offset = null, int $limit = null)
    {
        if ($offset && $limit == null) {
            $limit = $offset;
            $offset = 0;
        }

        $this->options['limit'] = $offset . ',' . $limit;

        return $this;
    }

    /**
     * @param $field
     * @return $this
     */
    public function group($field)
    {
        $this->options['group'] = $field;

        return $this;
    }

    /**
     * @param $having
     * @return $this
     */
    public function having($having)
    {
        $this->options['having'] = $having;

        return $this;
    }

    /**
     * @param $string
     * @return string
     */
    private function parseKey($string)
    {
        return '`' . $string . '`';
    }

    /**
     * @param $data
     * @return string
     */
    private function parseValue($data)
    {
        if (is_string($data)) {
            return '"' . $data . '"';
        }

        return $data;
    }

    /**
     * @param $value
     * @return bool
     */
    private function parsePrimaryKey($value)
    {
        $this->options['where'] = $this->parseKey($this->primaryKey) . ' = ' . $this->parseValue($value);

        return true;
    }

    /**
     * @param $operate
     * @return mixed|null|string
     */
    private function parseSql($operate)
    {
        $templateSql = isset($this->templateSql[$operate]) ? $this->templateSql[$operate] : '';

        if (empty($templateSql)) {
            return null;
        }

        $options = $this->options;

        $templateSql = preg_replace_callback('/%([a-z]+)%/', function ($matches) use ($options) {

            $option = '';

            if (isset($matches[1])) {

                $key = $matches[1];

                if (isset($options[$key])) {

                    if (in_array($key, ['where', 'having', 'limit'])) {
                        $option = $key . ' ';
                    } elseif (in_array($key, ['order', 'group'])) {
                        $option = $key . ' by ';
                    }

                    $option = $option . $options[$key];

                }
            }

            return $option;

        }, $templateSql);

        return $templateSql;
    }

    /**
     * @param array $data
     * @return bool|\PDOStatement
     */
    public function insert(array $data)
    {
        if (empty($data)) {
            return false;
        }

        if ($this->insertCreateTime) {
            if (method_exists($this, $this->createTimeFormat)) {
                $createTime = call_user_func([$this, $this->createTimeFormat]);
            } else {
                $createTime = time();
            }
            $data[$this->createTimeField] = $createTime;
        }

        $name = $values = '';
        foreach ($data as $key => $item) {
            $name .= $this->parseKey($key) . ',';
            $values .= $this->parseValue($item) . ',';
        }

        $this->options['name'] = trim($name, ',');
        $this->options['values'] = trim($values, ',');

        $sql = $this->parseSql(__FUNCTION__);

        $result = $this->query($sql);

        return $result;
    }

    /**
     * @param array $data
     * @param null $primaryValue
     * @return bool|\PDOStatement
     */
    public function update(array $data, $primaryValue = null)
    {
        if (empty($data)) {
            return false;
        }

        if ($primaryValue) {
            $this->parsePrimaryKey($primaryValue);
        }

        if (!isset($this->options['where'])) {
            return false;
        }

        $set = '';

        foreach ($data as $name => $item) {
            $set .= $this->parseKey($name) . ' = ' . $this->parseValue($item);
        }

        $this->options['set'] = $set;

        $sql = $this->parseSql(__FUNCTION__);

        $result = $this->query($sql);

        return $result;
    }

    /**
     * @param null $primaryValue
     * @return bool|\PDOStatement
     */
    public function delete($primaryValue = null)
    {
        if ($primaryValue) {
            $this->parsePrimaryKey($primaryValue);
        }

        if (!isset($this->options['where'])) {
            return false;
        }

        $sql = $this->parseSql(__FUNCTION__);

        $result = $this->query($sql);

        return $result;
    }

    /**
     * @param $primaryValue
     * @return mixed
     */
    public function get($primaryValue)
    {
        $this->parsePrimaryKey($primaryValue);

        return array_shift($this->toArray());
    }

    /**
     * @return mixed
     */
    public function find()
    {
        $this->limit(1);

        return array_shift($this->toArray());
    }

    /**
     * @return array|\PDOStatement|string
     */
    public function select()
    {
        return $this->toArray();
    }

    /**
     * @param string $sql
     * @return \PDOStatement|string
     */
    public function query(string $sql)
    {
        $this->sql = $sql;

        if ($this->fetchSql) {
            return $this->sql;
        }

        $result = $this->db->query($sql);

        return $result;
    }

    /**
     * @return $this
     */
    public function fetchSql()
    {
        $this->fetchSql = true;

        return $this;
    }

    /**
     * @param int $style
     * @return array|\PDOStatement|string
     */
    private function toArray($style = \PDO::FETCH_ASSOC)
    {
        $sql = $this->parseSql('select');

        $result = $this->query($sql);

        $data = [];

        if (empty($result)) {
            return [];
        }

        if (is_string($result)) {
            return $result;
        }

        if ($result) {
            while ($item = $result->fetch($style)) {
                $data[] = $item;
            }
        }

        return $data;
    }
}