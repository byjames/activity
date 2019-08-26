<?php

class Mysqldb
{
    private $dbh;
    private $statement;
    private $dbconfig;

    function __construct($dbKey = array())
    {
        if (count($dbKey) <= ZERO) {
            $this->dbconfig = array
            (
                'host' => DBHOST,
                'port' => DBPORT,
                'user' => DBUSER,
                'pass' => DBPASSWORD,
                'dbname' => DBNAME
            );
        } else {
            log_message::info("############", json_encode($dbKey));
            $this->dbconfig = array
            (
                'host' => $dbKey['host'],
                'port' => 3306,
                'user' => $dbKey['user'],
                'pass' => $dbKey['pass'],
                'dbname' => $dbKey['dbname']
            );
            log_message::info("############", json_encode($this->dbconfig));
        }

        //$this->dbconfig = $dbconfig;
        $dbh = $this->connect();


        if ($this->connect()) {
            $this->dbh = $this->connect();
        }
    }

    private function connect()
    {
        $_dsn = 'mysql:host=' . $this->dbconfig['host'] . ';';
        $_dsn .= 'port=' . $this->dbconfig['port'] . ';';
        $_dsn .= 'dbname=' . $this->dbconfig['dbname'] . ';';
        $_dsn .= 'charset=utf8';

        try {
            $dbLink = new PDO($_dsn, $this->dbconfig['user'],
                $this->dbconfig['pass'], array(
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8';"));
            $dbLink->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);

        } catch (PDOException $e) {
            log_message::info('Connection failed: ' . $e->getMessage(), 'db');
            return false;
        }
        return $dbLink;
    }

    public function close()
    {
        if (is_object($this->dbh)) {
            $this->dbh = null;
        }
    }

    public function query($sql, $prepare = array())
    {
        if (empty($sql) || !is_array($prepare) || empty($this->dbh)) {    //echo  "lod false prepare 1";
            log_message::info("false execution failed", 'db');
        }
        log_message::info("sqll" . $sql);
        //echo  "lod false prepare ??";
        $dbh = $this->dbh->prepare($sql);
        //echo  "lod false prepare ??";
        $dbh->closeCursor();

        $res = $dbh->execute($prepare);
        //echo  "lod false prepare ??";
        if (!$res) {
            log_message::info("db error : " . json_encode($dbh->errorInfo()) . " sql:" . $sql, 'db');
            return false;
        }
        $this->statement = $dbh;
        return $dbh;
    }

    /**
     *  update
     *
     */
    public function update($table, $aFields, $where = '', $prepare_array = array())
    {

        if (!$where)
            return false;

        if (!is_array($aFields) || count($aFields) < 1)
            return false;

        $aSet = array();
        foreach ($aFields as $key => $v) {
            $aSet[] = "`{$key}`=:{$key}";
            $prepare_array[$key] = $v;
        }
        $aSet && $set = implode(',', $aSet);
        if (empty($set))
            return false;

        $sql = " UPDATE {$table} SET {$set} WHERE {$where}";
        if ($this->query($sql, $prepare_array)) {

            return true;
        }

        return false;
    }

    /**
     *  自定义 update
     *
     *
     * public function update2($table, $aFields, $where = array())
     * {
     * if (!$where)
     * return false;
     *
     * if (!is_array($aFields) || count($aFields) < 1)
     * return false;
     *
     * $aSet = array();
     * foreach ($aFields as $key => $v) {
     * $aSet[] = "`{$key}`={$v}";
     * }
     *
     * foreach ($where as $fileKey => $fileVar) {
     * if (isset($whereOut)) {
     * $whereOut[$fileKey] = "AND  {$fileKey}={$fileVar}";
     * } else {
     * $whereOut[$fileKey] = "WHERE {$fileKey}={$fileVar}";
     * }
     * }
     * $aSet && $set = implode(',', $aSet);
     *
     * $wheres = implode(' ', $whereOut);
     *
     * $sql = " UPDATE {$table} SET {$set}  {$wheres}";
     * //_log("updata2 sql ".$sql,'db');
     * if ($this->mysqlQuery($sql)) {
     * return true;
     * }
     * return false;
     *
     * } */
    public function update2($table, $aFields, $where = '', $prepare_array = array())
    {

        if (!$where)
            return false;

        if (!is_array($aFields) || count($aFields) < 1)
            return false;

        $aSet = array();
        foreach ($aFields as $key => $v) {
            $aSet[] = "`{$key}`=:{$key}";
            $prepare_array[$key] = $v;
        }
        $aSet && $set = implode(',', $aSet);
        if (empty($set))
            return false;

        $sql = " UPDATE {$table} SET {$set} WHERE {$where}";

        if ($this->query($sql, $prepare_array)) {
            if ($this->rowcount() > 0) {
                return true;
            }
            return true;
        }
        return false;
    }

    public function mysqlQuery($sql)
    {
        if (empty($sql) || empty($this->dbh)) {
            return false;
            log_message::info("false execution failed", 'db');
        }
        return $this->dbh->query($sql);
    }

    /**
     * 返回结果集
     * @$statement PDO
     */
    public function fetch_all($statement = false)
    {
        if (!isset($statement) || empty($statement)) {

            return $this->statement->fetchAll(PDO::FETCH_ASSOC);
        }
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function rowcount($statement = false)
    {
        if (!isset($statement) || empty($statement)) {

            return $this->statement->rowCount();
        }

        return $statement->rowCount();
    }

    /**
     * 取得单行记录
     *
     * @return array
     */
    public function fetch_row($statement = false)
    {
        if (!isset($statement) || empty($statement)) {

            return $this->statement->fetch(PDO::FETCH_ASSOC);
        }

        return $statement->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * 单个录入
     *
     * @param type $table tables
     * @param type $array =array(fields=>value)
     * @param type $dbLink pdo connect
     */
    public function insert($table, $array = array())
    {
        $sql = " INSERT INTO {$table} ";
        $fields = array_keys($array);
        $values = array_values($array);
        $condition = array_fill(1, count($fields), '?');

        $sql .= "(`" . implode('`,`', $fields) . "`)
		VALUES (" . implode(',', $condition) . ")";

        return $this->query($sql, $values);
    }

    public function insert2($table, $array = array(), $addition = null)
    {
        $sql = " INSERT INTO {$table} ";
        $fields = array_keys($array);
        $values = array_values($array);
        $condition = array_fill(1, count($fields), '?');

        $sql .= "(`" . implode('`,`', $fields) . "`)
		VALUES (" . implode(',', $condition) . ") " . $addition;

        return $this->query($sql, $values);
    }

    /**
     * 批量插入
     * 字段与数值的对应关系，请调用方处理好
     *
     * @param type $table
     * @param type $field = "uid,name,sex",用逗号隔开
     * @param type $data = 多维数组
     */
    public function insertBatch($table, $fields, $data = array())
    {
        if (empty($fields))
            return false;

        if (!is_array($data) || count($data) < 1) {
            return false;
        }
        $sql = " INSERT INTO {$table} ($fields) VALUES  ";

        foreach ($data as $v) {
            $sql .= "(" . implode(',', $v) . "),";
        }

        return $this->query(rtrim($sql, ','), array());
    }

    /**
     * 注入符验证
     *
     * @param type $sql_str 验证字符
     */
    private function inject_check($sql_str)
    {
        $injectSign = 'select|insert|update|delete|\'|\/\*|\*|';

        $injectSign .= '\.\.\/|\.\/|union|into|load_file|outfile';

        $check = eregi($injectSign, $sql_str);

        if ($check) {
            //exit('{"status":"false","result":"Invaild symbol"}');
        } else {
            return $sql_str;
        }
    }

    /***
     * @param $data
     * @param null $addition
     * @return bool|string
     */
    public static function formatSqlWhere($data, $addition = null)
    {
        if (isDatas($data)) {
            $where = null;
            foreach ($data as $field_key => $field_val) {
                if (!empty($where)) {
                    $where .= ' AND  ' . $field_key . '=:' . $field_key;
                } else {
                    $where .= $field_key . '=:' . $field_key;
                }
            }
            log_message::info("formatSqlWhere data is ok", $where, $addition);
            return $where . ' '.$addition;
        }
        log_message::info("formatSqlWhere data is null", FAILURE);
        return false;
    }

}

?>