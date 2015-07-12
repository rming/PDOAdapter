<?php
error_reporting(E_ALL);
ini_set('display_errors','On');

require_once "../PDOAdapter/MyPDOAdapter.php";

$db = MyPDOAdapter::model();
(new tester)->run();

class tester {

    protected $db     = null;
    protected $action = null;
    public function __construct($db)
    {
        $this->db     = $db;
        $this->action = count($argv)>1 ? $argv[1] : 'test';
    }

    public function run()
    {
        call_user_func([$this, $this->action]);
    }

    protected function test()
    {
        // instant model
        // find by pk
        // table name without prefix
        $res = $db->test->find(226)->row();

        // instant model
        // insert
        $res = $db->test->insert(
            [
                'username'   => sprintf("test_%s", $this->randomChar()),
                'email'      => sprintf("test_%s@gmail.com", $this->randomChar()),
                'created_at' => date('Y-m-d H:i:s'),
            ]
        )->result();

        $res = $db->test->update(
            [
                'username'  => 'rmingwang',
            ],
            ['id' => 5]
        )->result();

        $res = $db->test->count(['score <='=>5000])->scalar();
    }
}
