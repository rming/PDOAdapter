<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
/**
 * @package
 * @file                 $RCSfile: MemcacheConfig.php,v $
 * @version              $Revision: 1.0 $
 * @modifiedby           $Author: handaoliang $
 * @lastmodified         $Date: 2013/11/30 20:51:09 $
 * @copyright            Copyright (c) 2013, Comnovo Inc.
**/
/**
 * Memcache集群配置文件。
**/
return array(
    "memcache_namespace"   =>"PDOAdapter_test",
    "memcache_server"      =>array(
        array(
            "host"           =>"127.0.0.1",
            "port"           =>11211,
            'weight'         =>10,
        ),
        array(
            "host"           =>"127.0.0.1",
            "port"           =>11211,
            'weight'         =>5,
        ),
    ),
);

