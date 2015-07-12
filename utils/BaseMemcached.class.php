<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
/**
 * @package
 * @file                 $RCSfile: BaseMemcached.class.php,v $
 * @version              $Revision: 1.0 $
 * @modifiedby           $Author: handaoliang$
 * @lastmodified         $Date: 2013/04/10 12:02:11 $
 * @copyright            Copyright (c) 2013, Comnovo Inc.
**/
/**
 * 通用Memcached类。
 **/
class BaseMemcached
{
    private $MemcacheConn = null;
    private $NameSpace = null;
    private $DataVersion = 1;

    public function __construct($MemcacheServer, $NameSpace)
    {
        if(!class_exists('Memcache')){
            $this->MemcacheConn = false;
            return;
        }

        $this->MemcacheConn = new Memcache();

        if(count($MemcacheServer) > 1)
        {
            foreach($MemcacheServer as $value)
            {
                $this->MemcacheConn->addServer($value['host'], $value['port'], $value['weight']);
            }
        } else {
            $this->MemcacheConn->addServer($MemcacheServer[0]['host'],$MemcacheServer[0]['port'], $MemcacheServer[0]['weight']);
        }
        $this->NameSpace = $NameSpace;
        //默认一个版本，各业务模块可以在生成对象后执行setDataVersion覆盖
        $this->setDataVersion();
    }

    /***
     * 检查Memcached是否连接成功
     * @return bool true成功，false 失败
     */
    public function checkStatus(){
        $memStatus = $this->MemcacheConn->getStats();
        if(empty($memStatus)){
            return false;
        }else{
            return true;
        }
    }


    /***
     * 添加缓存
     * @param $key key值
     * @param $value 缓存数据
     * @param $flag 0为MEMCACHE_COMPRESSED 1 为
     */
    public function setCache($key, $value, $expire=3600, $compress=false)
    {
        if(!$this->MemcacheConn){
            return;
        }
        $key = md5($key);
        $data = $this->MemcacheConn->get($key);
        if(empty($data)){
            if($compress){
                return $this->MemcacheConn->set($this->NameSpace.'_'.$this->DataVersion.'_'.$key, $value, MEMCACHE_COMPRESSED, $expire);
            }else{
                return $this->MemcacheConn->set($this->NameSpace.'_'.$this->DataVersion.'_'.$key, $value, 0, $expire);
            }
        }
    }

    /***
     * 获取缓存
     * @param $key值
     * @reutrn 缓存数据
     */
    public function getCache($key)
    {
        if(!$this->MemcacheConn){
            return;
        }

        $key = md5($key);
        return $this->MemcacheConn->get($this->NameSpace.'_'.$this->DataVersion.'_'.$key);
    }


    /***
     * 获取缓存
     * @param $key key值
     * @reutrn 缓存数据
     */
    public function deleteCache($key)
    {
        if(!$this->MemcacheConn){
            return;
        }
        $key = md5($key);
        return $this->MemcacheConn->delete($this->NameSpace.'_'.$this->DataVersion.'_'.$key);
    }


    /***
     * 增加值
     * @param $key key值
     * @param $value value值
     * @reutrn 缓存数据
     */
    public function incrementCache($key, $value=1)
    {
        if(!$this->MemcacheConn){
            return;
        }
        $key = md5($key);
        return $this->MemcacheConn->increment($this->NameSpace.'_'.$this->DataVersion.'_'.$key, $value);
    }

    /**
     * 减少值
     * @param $key key值
     * @param $value value值
     * @reutrn 缓存数据
     */
    public function decrementCache($key, $value=1)
    {
        if(!$this->MemcacheConn){
            return;
        }
        $key = md5($key);
        return $this->MemcacheConn->decrement($this->NameSpace.'_'.$this->DataVersion.'_'.$key, $value);
    }


    /**
     * 刷新缓存 根据$moduleName按模块批量刷新
     * @reutrn NULL
     */
    public function flushCache($ModuleName = "")
    {
        if(!$this->MemcacheConn){
            return;
        }
        $version_key = 'version_'.$this->NameSpace;
        if ($ModuleName != "")
        {
            $version_key .= '_'.$ModuleName;
        }
        $this->MemcacheConn->increment($version_key, 1);
    }

    /***
     * 按业务定制版本，方便批量刷新
     * @param ModuleName
     */
    public function setDataVersion($ModuleName = "")
    {
        $version_key = 'version_'.$this->NameSpace;
        if ($ModuleName != "")
        {
            $version_key .= '_'.$ModuleName;
        }

        $this->DataVersion = $this->MemcacheConn->get($version_key);
        if (empty($this->DataVersion))
        {
            $this->MemcacheConn->set($version_key, 1);
            $this->DataVersion = 1;
        }
    }

}


