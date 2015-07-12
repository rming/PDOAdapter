<?php
error_reporting(E_ALL);
ini_set('display_errors','On');

require_once "../PDOAdapter/MyPDOAdapter.php";
$db = MyPDOAdapter::model();
(new dbMigrate)->run();


class dbMigrate 
{
    protected $db     = null;
    protected $action = null;
    public function __construct($db)
    {
        $this->db     = $db;
        $this->action = count($argv)>1 ? $argv[1] : 'createTable';
    }

    public function run()
    {
        call_user_func([$this, $this->action]);
    }

    protected function createTable()
    {
        //table creating scripts
        $query = <<<EOT
-- Create syntax for TABLE 'instant_users'
CREATE TABLE `instant_users` (
  `user_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `role_id` int(11) NOT NULL DEFAULT '0',
  `score` int(11) NOT NULL DEFAULT '0',
  `username` varchar(50) NOT NULL DEFAULT '',
  `password` varchar(50) NOT NULL DEFAULT '',
  `email` varchar(120) NOT NULL DEFAULT '',
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Create syntax for TABLE 'users'
CREATE TABLE `users` (
  `user_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `role_id` int(11) NOT NULL DEFAULT '0',
  `score` int(11) NOT NULL DEFAULT '0',
  `username` varchar(50) NOT NULL DEFAULT '',
  `password` varchar(50) NOT NULL DEFAULT '',
  `email` varchar(120) NOT NULL DEFAULT '',
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
EOT;
        //create test tables
        $res = $this->db->query($query);
        var_dump($res);
    }
}


