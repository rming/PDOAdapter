<?php
/**
 * PDO connection manager
 */
class PDOConnector
{
    protected static $_instance = null;

    protected $_dbConfigs  = [];
    protected $_dbConfig   = [];
    protected $_links      = [];
    protected $_dbType     = null;
    protected $_dbHost     = null;
    protected $_dbPort     = null;
    protected $_dbName     = null;
    protected $_dbUser     = null;
    protected $_dbPassword = null;
    protected $_dbCharset  = null;

    private function __construct() 
    {
    }

    /**
     * 单例
     * @return static object
     */
    public static function getInstance()
    {
        if (static::$_instance === null) {
            return new static;
        }
        return static::$_instance;
    } 

    public function __get($dbUse)
    {
        if (isset($this->_links[$dbUse])) {
            return $this->_links[$dbUse];
        } elseif (!empty($this->_dbConfigs[$dbUse])) {
            $this->initConfig($this->_dbConfigs[$dbUse]);
            return $this->_links[$dbUse] = $this->newConnection();
        } else {
            throw new PDOException(sprintf('Unknow database configration :%s', $dbUse));
        }
    }

    public function loadConfigs(array $dbConfigs)
    {
        $this->_dbConfigs = $dbConfigs;
        return $this;
    }
    public function initConfig(array $dbConfig)
    {
        $this->_dbConfig   = $dbConfig;
        $this->_dbType     = $this->getter($this->_dbConfig, 'db_type');
        $this->_dbHost     = $this->getter($this->_dbConfig, 'db_host');
        $this->_dbPort     = $this->getter($this->_dbConfig, 'db_port');
        $this->_dbName     = $this->getter($this->_dbConfig, 'db_name');
        $this->_dbUser     = $this->getter($this->_dbConfig, 'db_user');
        $this->_dbPassword = $this->getter($this->_dbConfig, 'db_password');
        $this->_dbCharset  = $this->getter($this->_dbConfig, 'db_charset');

        return $this;
    }

    protected function newConnection()
    {
        $dsn = sprintf('%s:host=%s;port=%s;dbname=%s;charset=%s',
            $this->_dbType, 
            $this->_dbHost, 
            $this->_dbPort, 
            $this->_dbName, 
            $this->_dbCharset
        );

        // connection options
        $options = array(
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        );
        // connection charset handling for old php versions
        if ($this->_dbCharset and version_compare(PHP_VERSION, '5.3.6', '<')) {
            $options[PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET NAMES '.$this->_dbCharset;
        }

        $connection = new PDO($dsn, $this->_dbUser, $this->_dbPassword, $options);

        // Set prepared statement emulation depending on server version
        $emulatePreparesBelowVersion = '5.1.17';
        $serverVersion = $connection->getAttribute(PDO::ATTR_SERVER_VERSION);
        $emulatePrepares = (version_compare($serverVersion, $emulatePreparesBelowVersion, '<'));
        $connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, $emulatePrepares);

        return $connection;
    }

    protected function getter($arr, $k, $default = null)
    {
        return isset($arr[$k]) ? $arr[$k] : $default;
    }

}

