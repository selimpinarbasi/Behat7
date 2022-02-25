<?php

class PDOTxnLevelHandler
{
    private $transactionLevel = 0;
    /**
     * @var self
     */
    protected static $handler = null;
    private function __construct()
    {
    }
    public static function getInstance()
    {
        if (is_null(self::$handler)) {
            self::$handler = new self();
        }
        return self::$handler;
    }
    public function getLevel()
    {
        return $this->transactionLevel;
    }
    public function incLevel()
    {
        return ++$this->transactionLevel;
    }
    public function decrLevel()
    {
        return --$this->transactionLevel;
    }
}
/**
$ins = PDOTxnLevelHandler::getInstance();
echo $ins->incLevel() . PHP_EOL;
echo $ins->getLevel() . PHP_EOL;
$ins2 = PDOTxnLevelHandler::getInstance();
echo $ins2->incLevel() . PHP_EOL;
echo $ins2->getLevel() . PHP_EOL;
echo $ins2->decrLevel() . PHP_EOL;
echo $ins2->getLevel() . PHP_EOL;
 * */