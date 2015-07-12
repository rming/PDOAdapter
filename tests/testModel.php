<?php
error_reporting(E_ALL);
ini_set('display_errors','On');

require_once "../PDOAdapter/MyPDOAdapter.php";
require_once "UsersModel.php";

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

    protected function tester()
    {
        $db = MyPDOAdapter::model();
        $testModel = MyPDOAdapter::model('test');
        // rows
        $sql = "SELECT * FROM {{table}};";
        $res = $testModel->query($sql)->rows();

        // row
        $res = $testModel->query($sql)->row();

        // affected rows
        $res = $testModel->query($sql)->result();

        // count rows
        $sql = "SELECT count(*) FROM {{table}};";
        $res = $testModel->query($sql)->scalar();

        // bind param and get a row
        // Style 1
        $sql = "SELECT * FROM {{table}} WHERE role_id=?;";
        $res = $testModel->query($sql,6)->rows();

        $condition = [
            ':role_id' => 6,
            ':score'   => 5000,
        ];
        // Style 2
        // Style 2.1
        $sql = "SELECT * FROM {{table}} WHERE role_id=? AND score>=?;";
        $res = $testModel->query($sql,[6, 5000])->rows();

        // Style 2.2
        $res = $testModel->query($sql,array_values($condition))->rows();

        // Style 3
        $sql = "SELECT * FROM {{table}} WHERE role_id=:role_id AND score>=:score;";
        $res = $testModel->query($sql,$condition)->rows();

        // find by pk(default `id`)
        $res = $testModel->find(2)->row();

        $res = $testModel->where(['username LIKE'=>"%f%",'role_id'=>2], 'id desc', 10,0)->rows();

        $res = $testModel->where(
            [
                'role_id' =>2,
                'score >' => 5000,
                'username LIKE'        => "%f%",
                'password IS NOT NULL' => [],
                'created_at BETWEEN :date_start AND :date_end' =>[
                    ':date_start' => date('Y-m-d 00:00:00'),
                    ':date_end'   => date('Y-m-d 23:59:59'),
                ],
            ],
            'score desc',
            10,0
        )->rows();


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

        $res = $testModel->delete(['id'=>mt_rand(20,40)])->result();

        var_dump($res);
    }
}
