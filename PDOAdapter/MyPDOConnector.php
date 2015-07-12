<?php
/**
 * PDO connection manager
 */
class MyPDOConnector extends PDOConnector
{
    /**
     * 重载初始化配置（主要是key的问题）
     * @param  array  $dbConfig 
     */
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

}

