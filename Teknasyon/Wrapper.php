<?php

class Wrapper
{
    /**
     * @var \PDO
     */
    protected $pdo;
    /**
     * @var int
     */
    protected $transactionLevel = 0;
    /**
     * @var string
     */
    protected $sqlIdPrefix = '';
    /**
     * @var PDOTxnLevelHandler
     */
    private ?PDOTxnLevelHandler $txnLevelHandler;
    /**
     * @param \PDO $pdo
     * @param string $sqlIdPrefix
     */
    public function __construct(\PDO $pdo, $sqlIdPrefix = '')
    {
        $this->pdo = $pdo;
        $this->sqlIdPrefix = $sqlIdPrefix;
        $this->txnLevelHandler = PDOTxnLevelHandler::getInstance();

    }
    public function beginTransaction()
    {
        if ($this->txnLevelHandler->getLevel() == 0) {

            $this->pdo->beginTransaction();
        } else {

            $this->pdo->exec('SAVEPOINT LEVEL' . $this->txnLevelHandler->getLevel());
        }
        $this->txnLevelHandler->incLevel();
    }
    public function rollBack()
    {
        $level = $this->txnLevelHandler->decrLevel();
        if ($level == 0) {

            $this->pdo->rollBack();
        } else {

            $this->pdo->exec('ROLLBACK TO SAVEPOINT LEVEL' . $level);
        }
    }
    public function commit()
    {
        $level = $this->txnLevelHandler->decrLevel();
        if ($level == 0) {

            $this->pdo->commit();
        } else {

            $this->pdo->exec('RELEASE SAVEPOINT LEVEL' . $level);
        }
    }
    /**
     * @param $table
     * @throws \Exception
     */
    public function truncate(string $table)
    {
        $sql = 'TRUNCATE TABLE ' . $table;
        $stmt = $this->execute($sql);
        $stmt->closeCursor();
    }
    /**
     * @param $sql
     * @param array $params
     * @return int
     * @throws \Exception
     */
    public function query(string $sql, array $params = array())
    {
        $params = static::fixParamKeys($params);
        $stmt = $this->execute($sql, $params);
        $affectedRows = $stmt->rowCount();
        $stmt->closeCursor();
        return $affectedRows;
    }
    /**
     * @param $sql
     * @param array $params
     * @return string
     * @throws \Exception
     */
    public function insert(string $sql, array $params = array())
    {
        $params = static::fixParamKeys($params);
        $stmt = $this->execute($sql, $params);
        $lastInsertId = $this->pdo->lastInsertId();
        $stmt->closeCursor();
        return $lastInsertId;
    }
    /**
     * @param $sql
     * @param $seqName
     * @param array $params
     * @return string
     * @throws \Exception
     */
    public function insertWithSequence(string $sql, string $seqName, array $params = array())
    {
        $params = static::fixParamKeys($params);
        $stmt = $this->execute($sql, $params);
        $lastInsertId = $this->pdo->lastInsertId($seqName);
        $stmt->closeCursor();
        return $lastInsertId;
    }
    /**
     * @param $sql
     * @param array $params
     * @throws \Exception
     */
    public function insertWithoutId(string $sql, array $params = array())
    {
        $params = static::fixParamKeys($params);
        $stmt = $this->execute($sql, $params);
        $stmt->closeCursor();
    }
    /**
     * @param $sql
     * @param array $params
     * @param int $index
     * @return string
     * @throws \Exception
     */
    public function fetchColumn(string $sql, array $params = array(), int $index = 0)
    {
        $params = static::fixParamKeys($params);
        $stmt = $this->execute($sql, $params);
        $result = $stmt->fetchColumn($index);
        $stmt->closeCursor();
        return $result;
    }
    /**
     * @param $sql
     * @param array $params
     * @param int $index
     * @return array
     * @throws \Exception
     */
    public function fetchAllColumn(string $sql, array $params = array(), int $index = 0)
    {
        $params = static::fixParamKeys($params);
        $stmt = $this->execute($sql, $params);
        $result = $stmt->fetchAll(\PDO::FETCH_COLUMN, $index);
        $stmt->closeCursor();
        return $result;
    }
    /**
     * @param $sql
     * @param array $params
     * @return array
     * @throws \Exception
     */
    public function fetchOne(string $sql, array $params = [])
    {
        $params = static::fixParamKeys($params);
        $stmt = $this->execute($sql, $params);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        return $result;
    }
    /**
     * @param $sql
     * @param array $params
     * @param string $class
     * @param array $constructParams
     * @return mixed
     * @throws \Exception
     */
    public function fetchOneObject(
        string $sql,
        array $params = [],
        string $class = '\stdClass',
        array $constructParams = []
    )
    {
        $params = static::fixParamKeys($params);
        $stmt = $this->execute($sql, $params);
        $result = $stmt->fetchObject($class, $constructParams);
        $stmt->closeCursor();
        return $result;
    }
    /**
     * @param $sql
     * @param array $params
     * @return array
     * @throws \Exception
     */
    public function fetchAll(string $sql, array $params = [])
    {
        $params = static::fixParamKeys($params);
        $stmt = $this->execute($sql, $params);
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        return $results;
    }
    /**
     * @param $sql
     * @param array $params
     * @return array
     * @throws \Exception
     */
    public function fetchAllKeyPair(string $sql, array $params = [])
    {
        $params = static::fixParamKeys($params);
        $stmt = $this->execute($sql, $params);
        $results = $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
        $stmt->closeCursor();
        return $results;
    }
    /**
     * @param $sql
     * @param array $params
     * @param string $class
     * @param array $constructParams
     * @return array
     * @throws \Exception
     */
    public function fetchAllObjects(
        string $sql,
        array $params = [],
        string $class = '\stdClass',
        array $constructParams = []
    )
    {
        $params = static::fixParamKeys($params);
        $stmt = $this->execute($sql, $params);
        $results = $stmt->fetchAll(\PDO::FETCH_CLASS, $class, $constructParams);
        $stmt->closeCursor();
        return $results;
    }
    /**
     * @param $sql
     * @param array $params
     * @return \PDOStatement
     * @throws \Exception
     */
    public function execute(string $sql, array $params = array())
    {
        /**
         * @var string $sqlId
         * @var string $startTime
         */

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

        return $stmt;
    }
    /**
     * @param string $tableName
     * @param array $params
     * @param bool $addIgnore
     * @throws \Exception
     */
    public function insertWrapperWithoutId(string $tableName, array $params, $addIgnore = false)
    {
        $params = static::fixParamKeys($params);
        $sql = $this->createInsertSql($tableName, $params, $addIgnore);
        $stmt = $this->execute($sql, $params);
        $stmt->closeCursor();
    }
    /**
     * @param $tableName
     * @param $params
     * @return string
     * @throws \Exception
     */
    public function insertWrapper(string $tableName, array $params)
    {
        $params = static::fixParamKeys($params);
        $sql = $this->createInsertSql($tableName, $params);
        $stmt = $this->execute($sql, $params);
        $lastInsertId = $this->pdo->lastInsertId();
        $stmt->closeCursor();
        return $lastInsertId;
    }
    /**
     * @param string $tableName
     * @param array $params
     * @param bool $addIgnore
     * @return string
     */
    public function createInsertSql(string $tableName, array $params, $addIgnore = false)
    {
        $columns = array();
        $values = array();
        foreach (array_keys($params) as $key) {
            if ($this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME) == 'mysql') {
                $columns[] = '`' . substr($key, 1) . '`';
            } elseif ($this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME) == 'pgsql') {
                $columns[] = '"' . substr($key, 1) . '"';
            }
            $values[] = $key;
        }
        $columns = implode($columns, ', ');
        $values = implode($values, ', ');
        $sql = 'INSERT ' . ($addIgnore ? 'IGNORE ' : '') . 'INTO ' . $tableName
            . '(' . $columns . ') VALUES (' . $values . ')';
        return $sql;
    }
    /**
     * @param string $tableName
     * @param $id
     * @param array $params
     * @return int
     * @throws \Exception
     */
    public function updateById(string $tableName, $id, array $params): int
    {
        if ($this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME) == 'mysql') {
            $tableName = '`' . $tableName . '`';
        } elseif ($this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME) == 'pgsql') {
            $tableName = '"' . $tableName . '"';
        }
        $updateParams = [];
        foreach ($params as $key => $value) {
            if ($key !== 'id') {
                $updateParams[] = $key . '=:' . $key;
            }
        }
        $params['id'] = $id;
        $sql = 'UPDATE ' . $tableName . ' SET ' . implode(',', $updateParams) . ' WHERE id =:id';
        return $this->query($sql, $params);
    }

    /**
     * @param array $params
     * @return array
     */
    public static function fixParamKeys(array $params): array
    {
        $queryParams = [];
        foreach ($params as $key => $value) {
            if (is_int($key)) {
                $queryParams[] = $value;
            } elseif (substr($key, 0, 1) == ':') {
                $queryParams[$key] = $value;
            } else {
                $queryParams[':' . $key] = $value;
            }
        }
        return $queryParams;
    }

    /**
     * @param $key
     * @throws \Exception
     */
    public function unlock($key)
    {
        $this->execute('DO RELEASE_LOCK(:key)', ['key' => $key]);
    }
    /**
     * @TODO MYSQL pdo exception getCode ne dondugu kontrol edilmeli!!!
     * @param \Exception $e
     * @return bool
     */
    public function isDuplicateKeyException(\Exception $e)
    {
        return in_array($e->getCode(), [1062, 1557, 1569, 1586]);
    }
}
