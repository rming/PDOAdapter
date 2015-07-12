<?php
/**
 * 用户信息表 Model
 */
class UsersModel extends MyPDOAdapter
{

    public function __construct($table = null)
    {
        parent::__construct($table);
    }

    // table name without prefix
    protected $_table = 'users';
    protected $_pk    = 'user_id';


    /**
     * 条件查询计数
     * @param  array $conditions 查询条件
     * @return int
     */
    public function getCount(array $conditions)
    {
        $c = $this->parseDate($conditions);
        return $this->count($c)->scalar();
    }

    /**
     * 获取用户列表
     * @param  array  $conditions 查询条件
     * @param  string $order      order by {order}
     * @param  int    $limit      limit {limit}
     * @param  int    $offset     offset {offset}
     * @return data set 查询结果
     */
    public function getUsers(array $conditions, $order = null, $limit = null, $offset = null)
    {
        $c = $this->parseDate($conditions);
        return $this->where($c, $order, $limit, $offset)->rows();
    }

    /**
     * 解析日期段
     * @param  array  $conditions 查询条件
     * @param  string $startKey   起始日期key
     * @param  string $endKey     结束日期key
     * @return array  $c          处理后的查询条件
     */
    protected function parseDate(array $conditions, $startKey, $endKey)
    {
        $c = $conditions;
        if (!empty($c['created_at_start']) && !empty($c['created_at_end'])) {
            $c['created_at BETWEEN :created_at_start AND :created_at_end'] = [
                ':created_at_start' => date('Y-m-d 00:00:00',strtotime($c['created_at_start'])),
                ':created_at_end'   => date('Y-m-d 23:59:59',strtotime($c['created_at_end'])),
            ];
            unset($c['created_at_start'], $c['created_at_end']);
        }

        return array_filter($c);
    }



}
