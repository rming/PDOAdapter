# PDOAdapter
php classes extends PDO query

##TOC
*   [PDOAdapter](#pdoadapter)
    *   [简介](#简介)
    *   [使用](#使用)
    *   [目录](#目录)
    *   [USAGE](#usage)
        *   [编写 model](#编写-model)
            *   [model 文件](#model-文件)
            *   [Instant model](#instant-model)
        *   [数据操作](#数据操作)
            *   [Quick Start](#quick-start)
            *   [查询 有哪些方法？](#查询-有哪些方法)
            *   [结果 有哪些方法？](#结果-有哪些方法)
            *   [简单地查询组合](#简单地查询组合)
        *   [进阶](#进阶)
            *   [缓存查询](#缓存查询)
            *   [动态设置属性](#动态设置属性)
            *   [获得当前PDO实例](#获得当前PDO实例)
        *   [查询记录](#查询记录)

##简介
代码实现主要包括三部分：

1.  单例模式的PDO连接管理 `PDOConnector` 

2.  基本的 CURD 封装 `PDOAdapter`

3.  基于查询语句的缓存 `PDOCacher` 

4.  完整的数据查询记录，错误信息 `PDOAdapter::log($logMessage)`

##使用
复制 `PDOAdapter` 目录到项目中，修改 `My***.php` 中相关方法和接口实现。

##目录

```
|-- README.md
|-- PDOAdapter  
|   |-- MyPDOAdapter.php
|   |-- MyPDOCacher.php
|   |-- MyPDOConnector.php
|   `-- libs
|       |-- PDOAdapter.php
|       |-- PDOCacher.php
|       `-- PDOConnector.php
|-- tests
|   |-- README.md
|   |-- UsersModel.php
|   |-- dbMigrate.php
|   |-- logs
|   |   `-- db-2015-07-13.log
|   |-- testInstantModel.php
|   `-- testModel.php
`-- utils
    |-- BaseMemcached.class.php
    |-- MemcacheConfig.php
    `-- MysqlConfig.php
```

- `PDOAdapter` 目录下是继承实现 `libs` 下相关核心类并实现相关功能，`libs` 下为核心类
- `tests` 下是测试代码
- `utils` 下是测试代码需要使用到的 `config` 或者 `class`


##USAGE
###编写 `model`
#### model 文件
在具体的 model 文件中，需要设置 `数据表名` 和 `主键` 即可，其他设置，继承 `MyPDOAdapter` 或使用默认配置即可。
**备注**：`数据表名`没有默认值，`主键`的默认值是 `id`，`主键` 会在 `$this->find($id)` 方法调用时用到

一个典型的model文件示例：
```php
class UsersModel extends MyPDOAdapter
{
    // table name without prefix
    protected $_table = 'users';
    protected $_pk    = 'user_id';

    public function __construct($table = null)
    {
        parent::__construct($table);
    }
}
```
通常，我们在 `MyPDOAdapter` 中配置一些属性，以供具体的`model`去继承，比如：
```php
class MyPDOAdapter extends PDOAdapter
{

    public function __construct($table = null)
    {
        //db connector config
        $this->_dbUse     = 'master';
        $this->_dbConfig  = require "../utils/MysqlConfig.php";
        //PDOAdapter config
        $this->_prefix    = $this->_dbConfig[$this->_dbUse]["db_table_pre"];
        $this->_debug     = $this->_dbConfig[$this->_dbUse]["db_debug"];
        $this->_fetchType = PDO::FETCH_OBJ;

        // get db connection manager
        $this->_connector = MyPDOConnector::getInstance()->initConfig($this->_dbConfig);
        $this->_cacher    = MyPDOCacher::getInstance();

        parent::__construct($table);
    }
```
我们在 `MyPDOAdapter` 配置：
- 数据库配置文件 `$this->_dbConfig`
- 默认连接使用数据库 `$this->_dbUse`
- 数据库调试模式 `$this->_debug`
- PDO默认抓取数据格式 `$this->_fetchType`（通常可以是 `PDO::FETCH_OBJ`, `PDO::FETCH_ASSOC`)
- `MySQL` 连接管理工具 `MyPDOConnector::getInstance()`
- `Cache` 管理工具 `MyPDOCacher::getInstance()`

#### Instant model
`Instant model` 是指不编写 `model` 文件，而在控制器中直接使用 PDOAdapter实例，指定表名，设置属性（`PDOAdapter::set**()`），得到新的PDOAdapter实例，进行数据查询操作。

简单实例：
```php
//代码上下文为控制器中
$MyPDOAdapter = new MyPDOAdapter();

//得到一个 表名 为 users ，主键 为 user_id ，获取数组数据，的 model实例（以下两种写法相同）
$usersModel = $MyPDOAdapter->users->setPK('user_id')->setFetchType(PDO::FETCH_ASSOC);
$usersModel = MyPDOAdapter::model('users')->setPK('user_id')->setFetchType(PDO::FETCH_ASSOC);
```


###数据操作

####Quick Start

```php
class UsersModel extends MyPDOAdapter
{
    ...

    public function newUser()
    {
        $data = [
            'username'   => 'rming',
            'age'        => 12,
            'created_at' => date('Y-m-d H:i:s'),
        ];  
        //插入操作影响行数
        $affectedRows = $this->insert($data)->result();      
        //返回刚才插入的那一行数据
        return $this->lasteInsert()->row()
    }

    ...
}

```
由代码中可以看出，我们多数据的操作过程中使用了 `链式调用` ，通常，一个数据操作（CURD）通常包含 `2` 个方法调用，即 **查询** 和 **结果** ，当然可以是 `2+` 个方法链构成的查询，更复杂的方法链主要在 **缓存查询** 和 **设置属性** 相关查询中使用。

####`查询` 有哪些方法？

- `find($id)` 
根据主键获取记录

- `where(array $conditions, $orderBy = null, $limit = null, $offset = null)`
根据查询条件获取记录

- `lasteInsert()` 
上一次 INSERT 或 UPDATE 的第一个记录（WHERE {pk}=SELECT LAST_INSERT_ID()）

- `insert(array $attributes)` 
插入一条数据（批量插入未实现）

- `update(array $attributes, array $conditions, $limit = null, $orderBy = null)` 
更新数据

- `delete(array $conditions, $limit = null, $orderBy = null)` 
删除数据

- `count(array $conditions)` 
计数

- `query($sql, $params = null, $db = null)` 
自定义查询


####`结果` 有哪些方法？
- `row()`
获取第一行数据，返回 {对象/数组}，返回默认值是 `null`

- `rows()`
获取所有行数据，返回 {对象/数组} 集合，返回默认值是 `[]`

- `result()`
获取影响函数，返回数字，返回默认值是 `0`

- `scalar()`
获取首行首字段标量，返回标量，返回默认值是 `null`

**备注**：返回 `对象` 还是 `数组` 取决于设置的PDO fetchStyle


####简单地查询组合
**常用组合：**
- `count()`和`scalar()`方法，用于查询计数
- `insert(), update(), delete()`和`result()`方法，用于根据影响行数判断是否执行成功
- `find(), where(), lastInsert()`和`row(), rows()` 方法，用于查询结果数据

```php
//获取主键是 78 的那一行
$this->find(78)->row();

//获取名字是 'rming' 的那一行
$this->where(['name'=>'rming'])->row();
//获取年龄大于等于 20 的所有行
$this->where(['age >='=>20])->rows();
//获取姓王的所有行
$this->where(['name LIKE'=>"王%"])->rows();
//当然，查询条件不限于1个，但是目前仅支持 AND 查询
//20 岁以上，姓王的所有行
$this->where([
    'name LIKE'=>"王%",
    'age >='=>20
])->rows();

//计数通常和scalar结果方法一起用= =、
//20 岁以上，姓王的行数
$this->count([
    'name LIKE'=>"王%",
    'age >='=>20
])->scalar();

//删掉那个id是88的
$this->delete(['id'=>88])->result();

//一个完整的where查询
//角色2，年龄大于20 按照ID降序（新注册的），前50条数据
$this->where(
    [
        'role_id' => 2,
        'age >='  => 20
    ],
    'id DESC',
    50
)->rows();

//自定义查询
//参数绑定，再也不怕SQL注入了 = =、
//参数绑定0
$sql = 'SELECT * FROM `users` WHERE `id`=? limit 0,1';
$this->query($sql, 1)->row();
//参数绑定1
$sql = 'SELECT * FROM `users` WHERE `age`>? LIMIT ?';
$this->query($sql, [20,100])->rows();
//参数绑定2
$sql = 'SELECT * FROM `users` WHERE `age`>:age LIMIT :limit';
$this->query($sql, [':age'=>20,':limit'=>100])->rows();
```


###进阶
####缓存查询
```php
$userId = 10028;
//优先使用缓存查询，如果没找到再使用数据库查询，并更新缓存
$user   = $this->find($userId)->cache(300)->row();
//先清掉的缓存，然后使用查询数据库并更新缓存
$user   = $this->find($userId)->cacheClear()->cache(300)->row();
$user   = $this->find($userId)->cache(300)->cacheClear()->row();
//清掉缓存，使用数据库查询
$user   = $this->find($userId)->cacheClear()->row();
```



####动态设置属性
动态设置属性的方法（更改表名，主键名，数据库链接，设置数据抓取方式）改变了原来实例的属性，所以能对上下文调用产生较大的影响，应谨慎合理使用。
```php
$this->setDbUse('slave');
$this->setPK('user_id');
$this->setFetchType(PDO::FETCH_ASSOC);

``` 
####获得当前PDO实例
```php
$PDOInstance = $this->getDb();
```
获得PDO实例后，就可以直接调用PDO的方法了，比如`PDO::exec()`之类


###查询记录
一个完整的记录包括：

- 查询时间
- 是否使用了缓存查询
- 清除缓存的操作（清除缓存时会有）
- 查询是否成功
- 查询语句(prepare)
- 参数绑定(bindParams)
- PDOException详细信息(prepare或者query出错时会记录)


示例：
```perl
[2015-07-12 06:26:56] -  
Cache status : On
Query Success :true
SELECT * FROM `mall_users` WHERE `user_id`=? LIMIT 0,1;
0 => 89

[2015-07-13 01:43:50] -
Cache status : Off
Query Success :true
SELECT * FROM `app_users` WHERE username LIKE :username AND `role_id`=:role_id  ORDER BY :order_by  LIMIT :limit;
:username => %f%
:role_id => 2
:order_by => id desc
:limit => 10

[2015-07-13 04:23:06] - 
Cache status : Off
Clear Cache
Query Success :true
SELECT * FROM `mall_users` WHERE `user_id`=? LIMIT 0,1;
0 => 89

[2015-07-13 04:25:45] -
Cache status : Off
Query Failed :


exception 'PDOException' with message 'SQLSTATE[HY000] [1049] Unknown database 'malsdl'' in /data/wwwroot/wangshouqian/mall.miaoshou.com/Mall/Libs/PDOAdapter/libs/PDOConnector.php:81
Stack trace:
#0 /data/wwwroot/wangshouqian/mall.miaoshou.com/Mall/Libs/PDOAdapter/libs/PDOConnector.php(81): PDO->__construct('mysql:host=192....', 'root', 'root', Array)
#1 /data/wwwroot/wangshouqian/mall.miaoshou.com/Mall/Libs/PDOAdapter/libs/PDOConnector.php(41): PDOConnector->newConnection()
#2 /data/wwwroot/wangshouqian/mall.miaoshou.com/Mall/Libs/PDOAdapter/libs/PDOAdapter.php(140): PDOConnector->__get('master')
#3 /data/wwwroot/wangshouqian/mall.miaoshou.com/Mall/Libs/PDOAdapter/libs/PDOAdapter.php(155): PDOAdapter->getDb()
#4 /data/wwwroot/wangshouqian/mall.miaoshou.com/Mall/Libs/PDOAdapter/libs/PDOAdapter.php(356): PDOAdapter->query('SELECT * FROM `...', Array)
#5 /data/wwwroot/wangshouqian/mall.miaoshou.com/Mall/Controllers/LoginController.php(41): PDOAdapter->where(Array)
#6 [internal function]: LoginController->{closure}(Array)
#7 /data/wwwroot/wangshouqian/mall.miaoshou.com/Mall/Libs/MyUserCenterSDK.php(27): call_user_func_array(Object(Closure), Array)
#8 /data/wwwroot/wangshouqian/mall.miaoshou.com/Mall/Controllers/LoginController.php(51): MyUserCenterSDK::SDKException(Array, Array, Object(Closure))
#9 /data/wwwroot/wangshouqian/mall.miaoshou.com/NovoPHP/Libs/BaseController.class.php(124): LoginController->doIndex()
#10 /data/wwwroot/wangshouqian/mall.miaoshou.com/NovoPHP/Libs/BaseInterface.class.php(52): BaseController->dispatchAction()
#11 /data/wwwroot/wangshouqian/mall.miaoshou.com/Mall/WebRoot/index.do(31): BaseInterface::initInterface()
#12 {main}


[2015-07-13 04:28:56] -
Cache status : Off
Query Failed :
SELECT * FROM `mall_users` WHERE `id`=? LIMIT 0,1;
0 => 89

exception 'PDOException' with message 'SQLSTATE[42S22]: Column not found: 1054 Unknown column 'id' in 'where clause'' in /data/wwwroot/wangshouqian/mall.miaoshou.com/Mall/Libs/PDOAdapter/libs/PDOAdapter.php:162
Stack trace:
#0 /data/wwwroot/wangshouqian/mall.miaoshou.com/Mall/Libs/PDOAdapter/libs/PDOAdapter.php(162): PDO->prepare('SELECT * FROM `...')
#1 /data/wwwroot/wangshouqian/mall.miaoshou.com/Mall/Libs/PDOAdapter/libs/PDOAdapter.php(341): PDOAdapter->query('SELECT * FROM `...', 89)
#2 /data/wwwroot/wangshouqian/mall.miaoshou.com/Mall/Models/UsersModels.php(78): PDOAdapter->find(89)
#3 /data/wwwroot/wangshouqian/mall.miaoshou.com/Mall/Libs/MyBaseController.php(130): UsersModels->cacheFind(89)
#4 /data/wwwroot/wangshouqian/mall.miaoshou.com/Mall/Libs/MyBaseController.php(63): MyBaseController->getUserInfo()
#5 /data/wwwroot/wangshouqian/mall.miaoshou.com/Mall/Controllers/LoginController.php(43): MyBaseController->setUserLogin(Array)
#6 [internal function]: LoginController->{closure}(Array)
#7 /data/wwwroot/wangshouqian/mall.miaoshou.com/Mall/Libs/MyUserCenterSDK.php(27): call_user_func_array(Object(Closure), Array)
#8 /data/wwwroot/wangshouqian/mall.miaoshou.com/Mall/Controllers/LoginController.php(51): MyUserCenterSDK::SDKException(Array, Array, Object(Closure))
#9 /data/wwwroot/wangshouqian/mall.miaoshou.com/NovoPHP/Libs/BaseController.class.php(124): LoginController->doIndex()
#10 /data/wwwroot/wangshouqian/mall.miaoshou.com/NovoPHP/Libs/BaseInterface.class.php(52): BaseController->dispatchAction()
#11 /data/wwwroot/wangshouqian/mall.miaoshou.com/Mall/WebRoot/index.do(31): BaseInterface::initInterface()
#12 {main}
```
