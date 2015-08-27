<?php
class PDOAdapter
{
    //recommends
    //extends and reset in the models
    protected $_pk     = 'id';
    protected $_table  = null;

    //recommends
    //extends and reset in your adapter
    protected $_debug      = false;
    protected $_prefix     = null;
    protected $_dbUse      = 'maser';
    protected $_dbConfigs  = [];
    protected $_connector  = null;
    //是否使用缓存查询，默认关闭
    protected $_cacheQuery = false;
    protected $_cacheClear = false;
    protected $_cacher     = null;
    protected $_expired    = null;
    protected $_fetchType  = PDO::FETCH_ASSOC;

    //protected
    protected $_params   = [];
    protected $_sql      = null;
    protected $_stmt     = null;
    protected $_queryRes = null;

    public function __construct($table = null)
    {
        $this->_table = $table ?: $this->_table;
    }

    /**
     * 
     * 绑定到 instant model
     * @param  string $name tableName
     * @return object
     */
    public function __get($name)
    {
        return static::model($name);
    }

    /**
     * instant model
     * @param  string $table tableName
     * @return object
     */
    public static function model($table = null)
    {
        return new static($table);
    }

    /**
     * 切换当前使用的数据库配置
     * @param array $dbConfigs 
     */
    public function setDbConfig(array $dbConfigs)
    {
        $this->_dbConfigs = $dbConfigs;
        return $this;
    }

    /**
     * 切换当前使用的数据库
     * @param string $dbUse 
     */
    public function setDbUse($dbUse)
    {
        $this->_dbUse = $dbUse;
        return $this;
    }

    /**
     * 设置主键
     * @param string $pk
     */
    public function setPK($pk)
    {
        $this->_pk = $pk;
        return $this;
    }

    /**
     * 设置的PDO fetchStype
     * @return PDO FETCH_* const
     */
    protected function setFetchType($fetchType)
    {
        $this->_fetchType = $fetchType;
        return $this;
    }

    /**
     * 获取设置的PDO fetchStype
     * @return PDO FETCH_* const
     */
    protected function fetchType()
    {
        return $this->_fetchType;
    }

    /**
     * get tableName
     * @return string tableName with table prefix
     */
    protected function tableName()
    {
        return sprintf("%s%s", $this->_prefix, $this->_table);
    }


    /**
     * 清除数据库缓存
     */
    public function flushCache()
    {
        return $this->_cacher->flushCache();
    }

    /**
     * 用于记录SQL查询语句 和 处理PDOexception
     * @param  String $exception
     */
    public function logger($exception = null)
    {
        if ($this->_debug) {
            $logFormat  = "\n";
            $logFormat .= sprintf("Cache status : %s\n", $this->_cacheQuery ?'On':'Off');
            $logFormat .= $this->_cacheClear ? "Clear Cache\n" :'';
            $logFormat .= $this->_queryRes ? "Query Success :%s\n%s\n%s\n" : "Query Failed :%s\n%s\n%s\n";
            $paramsStr  = '';
            array_walk($this->_params, function($v,$k) use (&$paramsStr){
                $paramsStr .= $k." => ".$v."\n";
            });
            $queryRes = is_bool($this->_queryRes) ? ($this->_queryRes?'true':'false') : $this->_queryRes;
            $logMessage = sprintf($logFormat, $queryRes, $this->_sql, $paramsStr);
            if ($exception) {
                $logMessage .= sprintf("%s\n", $exception);
            }
            return $this->log($logMessage);
        }
    }

    /**
     * 记录查询日志，按需重载
     * 子类中实现此方法，用于记录PDOexception错误信息 和 SQL 查询语句
     * @param  String $logMessage
     */
    protected function log($logMessage)
    {
        return false;
    }

    public function getDb($dbUse = null)
    {
        $dbUse = $dbUse ?: $this->_dbUse;

        //set PDOexception handler
        set_exception_handler([$this,'logger']);
        $db = $this->_connector->$dbUse;
        restore_exception_handler();

        return $db;
    }

    /**
     * pdo 查询
     * @param  string $sql    脚本指令
     * @param  array  $params 绑定参数
     * @param  object $db     PDO数据库连接对象
     * @return object $this
     */
    public function query($sql, $params = null, $db = null)
    {
        $db     = $db ?: $this->getDb();
        $this->_sql    = $this->parseTplVar(trim($sql));
        $this->_params = is_array($params) ? $params : (array)$params;
        $this->_params = array_map(function($v){return (string)$v;}, $this->_params);

        //set PDOexception handler
        set_exception_handler([$this,'logger']);
        $this->_stmt     = $db ? $db->prepare($this->_sql) : null;
        restore_exception_handler();

        return $this;
    }

    /**
     * 打开缓存查询标记，设置缓存时间，使用缓存查询
     * @param  int $second 
     * @return Object $this
     */
    public function cache($second)
    {
        $this->_cacheQuery = true;
        $this->_expired = $second;
        return $this;
    }

    /**
     * 打开缓存查询标记
     * @return Object $this
     */
    public function cacheClear()
    {
        $this->_cacheClear = true;
        return $this;
    }
    /**
     * resetCacheQuery
     * RESET  _cacheClear _cacheQuery _expired
     * @return object $this
     */
    protected function resetCacheQuery()
    {
        $this->_cacheClear = false;
        $this->_cacheQuery = false;
        $this->_expired    = 0;

        return $this;
    }

    /**
     * cacheQuery 如果开启了缓存查询，则优先尝试从缓存中读取查询结果，如果没有则查询数据库
     * @param string   $methodName 结果查询方法名
     * @param callable $queryFunc  结果查询函数
     */
    protected function cacheQuery($methodName, callable $queryFunc)
    {
        //是否需要清除缓存
        if ($this->_cacheClear) {
            $cacheKey = $this->cacheKey($methodName);
            $clearRes = $this->_cacher ? $this->_cacher->removeCache($cacheKey) : false;
        }
        //是否使用缓存查询
        if ($this->_cacheQuery) {
            $cacheKey = $this->cacheKey($methodName);
            //尝试从缓存中读取，否则执行查询
            $cache = $this->_cacher ? $this->_cacher->getCache($cacheKey) : false;
            if ($cache===false) {
                //nothing cached, do query
                //set PDOexception handler
                set_exception_handler([$this,'logger']);
                $this->_queryRes = $this->_stmt ? $this->_stmt->execute($this->_params) : false;
                $res = call_user_func($queryFunc);
                restore_exception_handler();
                //如果在本次查询前设置 $this->_expired 则对此次查询进行缓存，查询结束后，手动设置 $this->_expired=0
                if ($this->_expired && $this->_cacher) {
                    $this->_cacher->setCache($cacheKey, serialize($res), $this->_expired);
                }
            } else {
                //if cached
                $this->_queryRes = 'Cached';
                $res = unserialize($cache);
            }
        } else {
            //不使用缓存，直接查询
            //set PDOexception handler
            set_exception_handler([$this,'logger']);
            $this->_queryRes = $this->_stmt ? $this->_stmt->execute($this->_params) : false;
            $res = call_user_func($queryFunc);
            restore_exception_handler();
        }
        //db query log 
        $this->logger();
        //RESET  _cacheClear _cacheQuery _expired
        $this->resetCacheQuery();

        return $res;
    }

    /**
     * generate cacheKey
     * @param  string $methodName 
     * @return string $cacheKey
     */
    protected function cacheKey($methodName)
    {
        //generate unique key by dbConfig, dbUse, sql, params 
        $cacheKey = sha1(
            serialize($this->_dbConfigs).
            $this->_dbUse.
            $this->_sql.
            serialize($this->_params).
            $methodName
        ); 
        return $cacheKey;
    }
    /**
     * return affected rows count
     * @return int affected rows number
     */
    public function result()
    {
        return $this->cacheQuery(__METHOD__, function(){
            return $this->_stmt->rowCount($this->fetchType());
        });
    }


    /**
     * 查询数据对象结果集合
     * select rows data set
     * @return data set
     */
    public function rows()
    {
        return $this->cacheQuery(__METHOD__, function(){
            return $this->_stmt->fetchAll($this->fetchType()) ?: [];
        });
        
    }

    /**
     * 当前行数据对象
     * current row data
     * @return data
     */
    public function row()
    {
        return $this->cacheQuery(__METHOD__, function(){
            return $this->_stmt->fetch($this->fetchType()) ?: null;
        });
    }

    /**
     * 一般当使用 count 查询时使用，或者其他特定场景
     * 首行首列标量
     * @return int
     */
    public function scalar()
    {
        return $this->cacheQuery(__METHOD__, function(){
            $row = $this->_stmt->fetch(PDO::FETCH_NUM);
            return isset($row[0]) ? $row[0] : null;
        });


    }

    /**
     * 计数查询
     * @param  array  $conditions
     * @return object $this
     */
    public function count(array $conditions)
    {
        list($conditionClouse, $bindParams) = $this->condition($conditions);
        $sql = sprintf("SELECT COUNT(*) FROM `%s` %s;", $this->tableName(), $conditionClouse);
        return $this->query($sql, $bindParams);
    }

    /**
     * find by pk(default `id`)
     * @param  string $id
     * @return object $this
     */
    public function find($id)
    {
        $sql = sprintf("SELECT * FROM `%s` WHERE `%s`=? LIMIT 0,1;", $this->tableName(), $this->_pk);
        return $this->query($sql, $id);
    }

    /**
     * where clouse
     * @param  array  $conditions
     * @param  string $orderBy
     * @param  int    $limit
     * @param  int    $offset
     * @return object $this
     */
    public function where(array $conditions, $orderBy = null, $limit = null, $offset = null)
    {
        list($conditionClouse, $bindParams) = $this->condition($conditions, $orderBy, $limit, $offset);
        $sql = sprintf("SELECT * FROM `%s` %s;", $this->tableName(), $conditionClouse);
        return $this->query($sql, $bindParams);
    }

    /**
     * 插入单条数据
     * @param  array  $attributes
     * @return object $this
     */
    public function insert(array $attributes)
    {
        //insert one
        //@todo insert batch or sql query
        $keys     = array_keys($attributes);
        $keyStr   = implode('`, `', $keys);
        $bindKeys = array_map(function($v){return sprintf(':%s',$v);}, $keys);
        $bindStr  = implode(', ', $bindKeys);
        $bindVals = array_combine($bindKeys, array_values($attributes));
        $sql      = sprintf("INSERT INTO `%s` (`%s`) VALUES (%s)", $this->tableName(), $keyStr, $bindStr);

        return $this->query($sql, $bindVals);
    }

    /**
     * 更新数据
     * @param  array  $attributes
     * @param  array  $conditions
     * @param  int    $limit
     * @param  string $orderBy
     * @return object $this
     */
    public function update(array $attributes, array $conditions, $limit = null, $orderBy = null)
    {
        //where clouse
        $setClouse  = '';
        $bindParams = [];
        list($conditionClouse, $bindParams) = $this->condition($conditions, $orderBy, $limit);
        array_walk($attributes, function($v,$k) use (&$setClouse, &$bindParams){
            //['cid'=12]
            $paramKey = sprintf(":%s", $k);
            $paramKey = $this->uniqueParam($bindParams, $paramKey);
            $bindParams[$paramKey] = $v;
            $setClouse .= sprintf("`%s`=%s, ", $k, $paramKey);
        });
        $setClouse = rtrim(rtrim($setClouse), ",");
        $setClouse = $setClouse ? sprintf("SET %s", $setClouse) : '';

        $sql = sprintf("UPDATE `%s` %s %s;", $this->tableName(), $setClouse, $conditionClouse);
        return $this->query($sql, $bindParams);
    }

    /**
     * 删除记录
     * @param  array  $conditions
     * @param  int    $limit
     * @param  string $orderBy
     * @return object $this
     */
    public function delete(array $conditions, $limit = null, $orderBy = null)
    {
        //where clouse
        list($conditionClouse, $bindParams) = $this->condition($conditions, $orderBy, $limit);
        $sql = sprintf("DELETE FROM `%s` %s;", $this->tableName(), $conditionClouse);
        return $this->query($sql, $bindParams);
    }

    /**
     * lastInsert data object
     * @return object $this
     */
    public function lastInsert()
    {
        return $this->find($this->getDb()->lastInsertId());
    }

   /**
     * condition clouse generator
     * @param  array  $conditions
     * @param  string $orderBy
     * @param  int    $limit
     * @param  int    $offset
     *
     * @return list($conditionClouse, $bindParams)
     */
    protected function condition(array $conditions, $orderBy = null, $limit = null, $offset = null)
    {
        //where clouse
        $whereClouse = '';
        $bindParams  = [];

        array_walk($conditions, function($v,$k) use (&$whereClouse, &$bindParams){
            if (is_array($v)) {
                //['BETWEEN '=>['1','2']]
                //['IN'=>['1','2']]
                if (preg_match('/^([_a-z0-9]+?)(\s+)(between|in)$/i', trim($k), $reg)) {
                    $condition = strtolower($reg[3]);
                    $makeParamKey = function($inVals, $prefix = null) {
                        foreach ($inVals as $key => $value) {
                            yield sprintf("%s%s", $prefix, $key) => $value;
                        }
                    };
                    switch ($condition) {
                        case 'in':
                            //empty array for where in condition
                            if (empty($v)) {
                                $whereClouse .='0=1 ';
                            } else {
                                $whereClouse .= sprintf("`%s` IN (", $reg[1]);
                                foreach ($makeParamKey($v, ':in_') as $key => $value) {
                                    $paramKey = $this->uniqueParam($bindParams, $key);
                                    $bindParams[$paramKey] = $value;
                                    $whereClouse .= sprintf("%s,", $key); 
                                }
                                $whereClouse = rtrim($whereClouse, ',') . ") ";
                            }
                            break;
                        case 'between':
                            $v = iterator_to_array($makeParamKey($v, ':in_'));
                            $keys = array_keys($v);
                            $vals = array_values($v);
                            //between
                            $paramKey = $this->uniqueParam($bindParams, current($keys));
                            $bindParams[$paramKey] = current($vals);
                            $whereClouse .= sprintf("`%s` BETWEEN %s ", $reg[1], $paramKey);
                            //and
                            $paramKey = $this->uniqueParam($bindParams, end($keys));
                            $bindParams[$paramKey] = end($vals);
                            $whereClouse .= sprintf("AND %s ", $paramKey);
                            break;
                        default:
                            return;
                            break;
                    }
                } else {
                    //['BETWEEN :a AND :b'=>[':a'=>'1',':b'=>'2']]
                    foreach ($v as $key => $value) {
                        $paramKey = $this->uniqueParam($bindParams, $key);
                        $bindParams[$paramKey] = $value;
                        //把参数绑定重复的键名更换掉
                        $k = str_replace($key, $paramKey, $k);
                    }
                    $whereClouse .= sprintf("%s", $k);
                }

            } else {
                //需要参数绑定的查询条件
                if (preg_match('/^([_a-z0-9]+?)(\s+)(>|<|>=|<=|=|!=|like)$/i', trim($k), $reg)) {
                    //['name >|<|>=|<=|=|!=|between|like']
                    $paramKey = sprintf(":%s", $reg[1]);
                    $paramKey = $this->uniqueParam($bindParams, $paramKey);
                    $bindParams[$paramKey] = $v;
                    $whereClouse .= sprintf("%s %s", $k, $paramKey);
                } else {
                    //['cid'=12]
                    $paramKey = sprintf(":%s", $k);
                    $paramKey = $this->uniqueParam($bindParams, $paramKey);
                    $bindParams[$paramKey] = $v;
                    $whereClouse .= sprintf("`%s`=%s", $k, $paramKey);
                }

            }
            $whereClouse .= " AND ";
        });

        $whereClouse = rtrim(rtrim($whereClouse), "AND");
        $whereClouse = $whereClouse ? sprintf("WHERE %s", $whereClouse) : '';

        //order by
        if ($orderBy) {
            //field 支持
            $matchOrder = preg_match('/^field\((.+?)\)(\s+?)(desc|asc)+?$/i', trim($orderBy));
            if ($matchOrder) {
                 $orderByStr = $orderBy;
            } else {
                //escape bad string in column
                $orderByArr = explode(',', $orderBy);
                $orderByArr = array_map(function($orderBy){
                    $orderBy = preg_replace('/^(\S*?)(\s+?)(desc|asc)+?$/i','`'.trim('${1}','`').'`'.' ${3}', trim($orderBy), '-1', $count);
                    return $count? $orderBy : false;
                }, $orderByArr);
                //implode to str
                $orderByStr = implode(',', array_filter($orderByArr)); 
            }
            $orderBy    = $orderByStr ? sprintf("ORDER BY %s", $orderByStr) : '';
        }

        //limit offset
        $offset = sprintf('%d', $offset) ?: '';
        if ($offset) {
            $paramKey = $this->uniqueParam($bindParams, ':offset');
            $bindParams[$paramKey] = $offset;
            $offset = sprintf(" OFFSET %s", $paramKey);
        }
        $limit = sprintf('%d', $limit) ?: '';
        if ($limit) {
            $paramKey = $this->uniqueParam($bindParams, ':limit');
            $bindParams[$paramKey] = $limit;
            $limit = sprintf(" LIMIT %s", $paramKey);
            $limit = $limit.$offset;
        }

        return [sprintf("%s %s %s", $whereClouse, $orderBy, $limit),$bindParams];
    }

    /**
     * 确保参数绑定的key是唯一的
     * @param  array  $params   参数数组
     * @param  string $paramKey 参数 key
     * @return string
     */
    protected function uniqueParam($params, $paramKey)
    {
        if (isset($params[$paramKey])) {
            return $this->uniqueParam($params, $paramKey.'0');
        } else {
            return $paramKey;
        }
    }

    /**
     * 解析 SQL 中的 模板变量
     * @return string $sql
     */
    protected function parseTplVar($sql)
    {
        $search  = [
            "{{table}}",
            '``',
        ];
        $replace = [
            sprintf('`%s`', $this->tableName()),
            '`',
        ];
        return str_ireplace($search, $replace, $sql);
    }

}
