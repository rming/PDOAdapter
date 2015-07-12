<?php
require_once "../utils/BaseMemcached.class.php";

class MyPDOCacher implements PDOCacher
{
    protected static $_instance = null;
    protected $cachePrefix  = 'db';
    protected $memObj       = null;

    protected function __construct()
    {
        $memcacheConfig = require_once "../utils/MemcacheConfig.php";
        if($memcacheConfig && isset($memcacheConfig["memcache_namespace"], $memcacheConfig["memcache_server"])) {
            $memServer    = $memcacheConfig["memcache_server"];
            $memNamespace = $memcacheConfig["memcache_namespace"];
            $this->memObj = new BaseMemcached($memServer, $memNamespace);
            if ($this->memObj->checkStatus())
            {
                $this->memObj->setDataVersion($this->cachePrefix);
            }
        } else {
            throw new Exception('PDOCacher Memcache initialize error');
        }
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

    /**
     * 设置缓存，按需重载
     * @param string $cachename
     * @param mixed $value
     * @param int $expired
     * @return boolean
     */
    public function setCache($cachename, $value, $expired){
        return $this->memObj->setCache($cachename, $value, $expired);
    }

    /**
     * 获取缓存，按需重载
     * @param string $cachename
     * @return mixed
     */
    public function getCache($cachename){
        return $this->memObj->getCache($cachename);
    }

    /**
     * 清除缓存，按需重载
     * @param string $cachename
     * @return boolean
     */
    public function removeCache($cachename){
        return $this->memObj->deleteCache($cachename);
    }

    /**
     * 清除所有数据缓存
     * @return boolean
     */
    public function flushCache()
    {
        return $this->memObj->flushCache($this->cachePrefix);
    }

}
