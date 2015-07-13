<?php
require_once "libs/PDOAdapter.php";
require_once "libs/PDOCacher.php";
require_once "libs/PDOConnector.php";
require_once "MyPDOConnector.php";
require_once "MyPDOCacher.php";



class MyPDOAdapter extends PDOAdapter
{

    public function __construct($table = null)
    {
        //db connector config
        $this->_dbUse     = 'master';
        $this->_dbConfigs = require "../utils/MysqlConfig.php";
        //PDOAdapter config
        $this->_prefix    = $this->_dbConfigs[$this->_dbUse]["db_table_pre"];
        $this->_debug     = $this->_dbConfigs[$this->_dbUse]["db_debug"];
        $this->_fetchType = PDO::FETCH_OBJ;

        // get db connection manager
        $this->_connector = MyPDOConnector::getInstance()->loadConfigs($this->_dbConfigs);
        $this->_cacher    = MyPDOCacher::getInstance();

        parent::__construct($table);
    }


    protected function log($logMessage)
    {
        $logFormat   = "[%s] - %s\n";
        $logFileName = 'db-' . date('Y-m-d') . '.log';
        $logFile     = '../tests/logs'.DIRECTORY_SEPARATOR. $logFileName;
        $logMessage  = sprintf($logFormat, date('Y-m-d H:i:s'), $logMessage);

        return file_put_contents($logFile, $logMessage, FILE_APPEND);
    }

}
