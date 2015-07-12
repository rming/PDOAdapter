<?php


interface PDOCacher
{

    /**
     * 单例
     * @return static object
     */
    public static function getInstance();

    /**
     * 设置缓存，按需重载
     * @param  string $cachename
     * @param  mixed $value
     * @param  int $expired
     * @return boolean
     */
    public function setCache($cachename, $value, $expired);

    /**
     * 获取缓存，按需重载
     * @param  string $cachename
     * @return mixed
     */
    public function getCache($cachename);

    /**
     * 清除缓存，按需重载
     * @param  string $cachename
     * @return boolean
     */
    public function removeCache($cachename);


    /**
     * 清除所有数据缓存
     * @return boolean
     */
    public function flushCache();

}
