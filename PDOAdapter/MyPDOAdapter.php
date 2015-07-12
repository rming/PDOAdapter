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
        $this->_dbConfig  = BaseInitialize::loadAppsConfig('mysql');
        //PDOAdapter config
        $this->_prefix    = $this->_dbConfig[$this->_dbUse]["db_table_pre"];
        $this->_debug     = $this->_dbConfig[$this->_dbUse]["db_debug"];
        $this->_fetchType = PDO::FETCH_ASSOC;

        // get db connection manager
        $this->_connector = MyPDOConnector::getInstance()->initConfig($this->_dbConfig);
        $this->_cacher    = MyPDOCacher::getInstance();

        parent::__construct($table);
    }


    protected function log($logMessage)
    {
        $logFormat   = "[%s] - %s\n";
        $logFileName = 'db-' . date('Y-m-d') . '.log';
        $logFile     = APPS_BASE_DIR.DIRECTORY_SEPARATOR.'Logs'.DIRECTORY_SEPARATOR. $logFileName;
        $logMessage  = sprintf($logFormat, date('Y-m-d H:i:s'), $logMessage);

        return file_put_contents($logFile, $logMessage, FILE_APPEND);
    }

}
