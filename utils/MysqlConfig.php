<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
/**
 * @package
 * @file                 $RCSfile: MySQLConfig.php,v $
 * @version              $Revision: 1.0 $
 * @modifiedby           $Author: handaoliang $
 * @lastmodified         $Date: 2013/11/30 20:51:09 $
 * @copyright            Copyright (c) 2013, Comnovo Inc.
**/
/**
 * MySQL集群配置文件。
**/
return array (
    "master" => array (
        "db_host"         =>"192.168.1.250",
        "db_port"         =>3306,
        "db_user"         =>"root",
        "db_password"     =>"root",
        "db_name"         =>"test_db",
        "db_table_pre"    =>"app_",
        "db_charset"      =>"utf8",
        "db_type"         =>"mysql",
        "db_debug"        =>true,
    ),

    "slave" => array (
        "db_host"         =>"192.168.1.250",
        "db_port"         =>3306,
        "db_user"         =>"root",
        "db_password"     =>"root",
        "db_name"         =>"test_db2",
        "db_table_pre"    =>"app_",
        "db_charset"      =>"utf8",
        "db_type"         =>"mysql",
        "db_debug"        =>true,
    ),
);

