<?php
error_reporting(E_ALL);
ini_set('display_errors','On');

require_once "../PDOAdapter/MyPDOAdapter.php";
$db = MyPDOAdapter::model();
(new dbMigrate($db))->run();


class dbMigrate 
{
    protected $db     = null;
    protected $action = null;
    public function __construct($db)
    {
        $this->db     = $db;
        $this->action = count($GLOBALS['argv'])>1 ? $GLOBALS['argv'][1] : 'createTable';
    }

    public function run()
    {
        call_user_func([$this, $this->action]);
    }

    protected function createTable()
    {
        //table creating scripts
        $query = "CREATE TABLE `app_instant_users` (
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
CREATE TABLE `app_users` (
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
";
        //create test tables
        try {
            $res = $this->db->getDb()->exec($query);
        } catch (Exception $e) {
            $res = false;
        }

        if ($res === false) {
            echo "table created failed\n";
        } else {
            echo "table created success\n";
        }
    }

    protected function insert()
    {
        $usersModel = MyPDOAdapter::model('users');
        $usersModel->setDbUse('master');
        $usersModel->setPK('user_id');


        $password = '13456';
        $userData = [
            'username'   => $this->randomChar(),
            'role_id'    => mt_rand(0,10),
            'score'      => mt_rand(0,10000),
            'email'      => $this->randomChar()."@gmail.com",
            'created_at' => date('Y-m-d H:i:s'),
        ];

        $update = false;
        if ($usersModel->insert($userData)->result()) {
            $user   = $usersModel->lastInsert()->row();
            echo "INSERT User{{$user->user_id}}\n";
            $update = $usersModel->update(
                ['password' => sha1($user->user_id.'=>'.$password)],
                ['user_id' => $user->user_id]
            )->result();
            if ($update) {
                echo "UPDATE User{{$user->user_id}}\n\n";
            }
        }
    }

    public function insertBatch()
    {
        for($i=1; $i<100; $i++){
            $this->insert();
        }
    }

    protected function randomChar($length = 8)
    {
        $randChar = '';
        for ($i = 0; $i < $length; $i++)
        {
            $randChar .= chr(mt_rand(97, 122));
        }
        return $randChar;
    }

}


