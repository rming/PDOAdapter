<?php
error_reporting(E_ALL);
ini_set('display_errors','On');

require_once "../PDOAdapter/MyPDOAdapter.php";
require_once "UsersModel.php";

$userModel = new UsersModel();
(new tester($userModel))->run();

class tester {

    protected $model  = null;
    protected $action = null;
    public function __construct($model)
    {
        $this->model  = $model;
        $this->action = count($GLOBALS['argv'])>1 ? $GLOBALS['argv'][1] : 'test';
    }

    public function run()
    {
        call_user_func([$this, $this->action]);
    }

    protected function test()
    {
        // rows
        $sql = "SELECT * FROM {{table}};";
        $res = $this->model->query($sql)->rows();
        echo sprintf("%s\n",var_export($res));

        // row
        $res = $this->model->query($sql)->row();
        echo sprintf("%s\n",var_export($res));

        // affected rows
        $res = $this->model->query($sql)->result();
        echo sprintf("%s\n",var_export($res));

        // count rows
        $sql = "SELECT count(*) FROM {{table}};";
        $res = $this->model->query($sql)->scalar();
        echo sprintf("%s\n",var_export($res));

        // bind param and get a row
        // Style 1
        $sql = "SELECT * FROM {{table}} WHERE role_id=?;";
        $res = $this->model->query($sql,6)->rows();
        echo sprintf("%s\n",var_export($res));

        $condition = [
            ':role_id' => 6,
            ':score'   => 5000,
        ];
        // Style 2
        // Style 2.1
        $sql = "SELECT * FROM {{table}} WHERE role_id=? AND score>=?;";
        $res = $this->model->query($sql,[6, 5000])->rows();
        echo sprintf("%s\n",var_export($res));

        // Style 2.2
        $res = $this->model->query($sql,array_values($condition))->rows();
        echo sprintf("%s\n",var_export($res));

        // Style 3
        $sql = "SELECT * FROM {{table}} WHERE role_id=:role_id AND score>=:score;";
        $res = $this->model->query($sql,$condition)->rows();
        echo sprintf("%s\n",var_export($res));

        // find by pk(default `id`)
        $res = $this->model->find(2)->row();
        echo sprintf("%s\n",var_export($res));

        $res = $this->model->where(['username LIKE'=>"%f%",'role_id'=>2], 'id desc', 10,0)->rows();
        echo sprintf("%s\n",var_export($res));

        $res = $this->model->where(
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
        echo sprintf("%s\n",var_export($res));

        $res = $this->model->delete(['user_id'=>mt_rand(20,40)])->result();
        echo sprintf("%s\n",var_export($res));
    }
}
