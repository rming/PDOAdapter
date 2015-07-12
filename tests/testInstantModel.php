<?php
error_reporting(E_ALL);
ini_set('display_errors','On');

require_once "../PDOAdapter/MyPDOAdapter.php";

$db = MyPDOAdapter::model();

(new tester($db))->run();

class tester {

    protected $db     = null;
    protected $action = null;
    public function __construct($db)
    {
        $this->db     = $db;
        $this->action = count($GLOBALS['argv'])>1 ? $GLOBALS['argv'][1] : 'test';
    }

    public function run()
    {
        call_user_func([$this, $this->action]);
    }

    protected function test()
    {
        // instant model
        // 每次调用 db->{{tableName}} 将产生一个实例
        // 
        $usersModel = $this->db->users->setPK('user_id');
        //equals 
        $usersModel = $this->db->users;
        $usersModel = $usersModel->setPK('user_id');
        //equals 
        $usersModel = $this->db->users;
        $usersModel->setPK('user_id');

        // find by pk
        // table name without prefix
        $res = $usersModel->find(226)->row();
        echo sprintf("%s\n",var_export($res));

        // instant model
        // insert
        $res = $usersModel->insert(
            [
                'username'   => sprintf("test_%s", $this->randomChar()),
                'email'      => sprintf("test_%s@gmail.com", $this->randomChar()),
                'created_at' => date('Y-m-d H:i:s'),
            ]
        )->result();
        echo sprintf("%s\n",var_export($res));

        $res = $usersModel->update(
            [
                'username'  => 'rmingwang',
            ],
            ['user_id' => 5]
        )->result();
        echo sprintf("%s\n",var_export($res));

        $res = $usersModel->count(['score <='=>5000])->scalar();
        echo sprintf("%s\n",var_export($res));
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
