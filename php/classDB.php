<?php
include_once "classFile.php";
/*************************************************************************************************
 * **********************************************************************************************
 * 
 *          数据库类(classDB) 提供了SQLOJ系统中常用の涉及数据库操作の方法
 *          它的实例化表示<一个有效数据库及与其建立的连接>
 *          2021/3/1    版本1.0     构建DB类并写了一些基本函数用于测试
 *          2021/3/2    版本2.0     重构DB类,文件操作使用自定义文件类进行
 *          2021/3/3    版本2.1     扩充select方法
 *          2021/3/5    版本2.2     完善代码、增强可读性
 *          2021/3/10   版本2.3     在select方法中防止sql注释符引入
 *          2021/3/13   版本2.4     增加返回错误信息函数
 *          2021/3/23   版本2.5     取消select中注释符的替换,支持注释符的引入
 *      
 * +--+-+-+--+-+-+-+--+函数功能说明+-+-+-+-+-+-+-+-+-+-+-+-+--+-+-+-+-+-+-
 * 
 * 1.__construct($h, $u, $p, $d)            |   构造函数
 * 2.__destruct()                           |   析构函数
 * 3.CreateTable($sql)                      |   用string建表返回bool
 * 4.CreateTableF($url)                     |   用txt建表返回bool
 * 5.Select($sql)                           |   用string执行查询语句,返回string(一般用于判题)
 * 6.SelectF($url)                          |   用txt执行查询语句,返回string(一般用于判题)
 * 7.SelectAssoc($sql)                      |   sql语句错误返回NULL，sql语句正确返回二维关联数组arr[记录编号]['属性']
 * 8.SelectAssocF($url)                     |   文件路径或sql语句错误返回NULL，sql语句正确返回二维关联数组arr[记录编号]['属性']
 * 9.SelectNum($sql)                        |   sql语句错误返回NULL，sql语句正确返回二维数字下标数组arr[记录编号][属性编号]
 * 10.SelectNumF($url)                      |   文件路径或sql语句错误返回NULL，sql语句正确返回二维数字下标数组arr[记录编号][属性编号]
 * 11.Update($sql)                          |   用string执行更新语句,返回bool(可执行多条)
 * 12.UpdateF($url)                         |   用txt执行更新语句,返回bool(可执行多条)
 * 13.Error()                               |   返回执行语句的错误信息(string)值得一提的是,当正确执行后错误信息更新为空字符串，转bool为false
 * 14.ErrorC()                              |   返回连接(connect)的错误信息(string)确保连接成功后进行操作
 *
 * **********************************************************************************************
 ***********************************************************************************************/
class DB {
    protected $host = NULL;
    protected $user = NULL;
    protected $password = NULL;
    protected $database = NULL;
    protected $result = NULL;
    protected $mysqli = NULL;
    protected $file = NULL;
    protected $id = NULL;
    
    protected $err = NULL;
    protected $connerr = NULL;

    /*构造函数 建立与指定数据库的连接 无返回*/
    public function __construct($h, $u, $p, $d) {
        $this->host = $h;
        $this->user = $u;
        $this->password = $p;
        $this->database = $d;
        $this->mysqli = @new mysqli($h, $u, $p, $d);
        if ($this->mysqli->connect_errno) {
            $this->connerr = $this->mysqli->connect_error;
        }
    }
    /*析构函数 断开连接*/
    public function __destruct() {
        if (isset($this->mysqli)) {
            @$this->mysqli->close();
        }
    }
    /*使用string建表 返回bool*/
    public function CreateTable($sql) {
        if (!isset($sql) || empty($sql)) {
            return false;
        }
        if ($this->mysqli->multi_query($sql)) {
            $this->err = $this->mysqli->error;
            //清除结果集缓冲 以便进行后续操作
            do {
                    if ($this->result = $this->mysqli->store_result()) {
                        //如需读取请额外添加
                        $this->result->free();
                    }
            } while ($this->mysqli->next_result());
            return true;
        }
        else {
            $this->err = $this->mysqli->error;
            return false;
        }
    }
    /*使用txt建表 返回bool*/
    public function CreateTableF($url) {
        if (!isset($url) || empty($url)) {
            return false;
        }
        $txt = new File($url);
        if (!$sql = $txt->ReadTxt()) {
            return false;
        }
        if (!$this->CreateTable($sql)) {
            return false;
        }
        return true;
    }
    /*用string执行!一句!查询语句 将查询结果保存到string并返回
    考虑到查询的到的结果不能只看记录，还要和字段结合起来
    所以就返回关联数组形式，并把关联数组的所有键值对按顺序存到string中 */
    //执行失败返回NULL，内容为空返回空字符串
    public function Select($sql) {
        if (!isset($sql) || empty($sql)) {
            return NULL;
        }
        if ($this->result = $this->mysqli->query($sql)) {
            $this->err = $this->mysqli->error;
            //这是目前保存答案的通用格式 关联数组转字符串
            $ans = "";
            //结果集为bool(true)显然不能获取内容
            if($this->result !== true)
            while ($tmp = $this->result->fetch_array(MYSQLI_ASSOC)) {
                foreach($tmp as $k => $v) {
                    $ans .= $k;
                    $ans .= $v;
                }
            }
            return $ans;
        }
        //查询语句执行失败
        else {
            $this->err = $this->mysqli->error;
            return NULL;
        }
    }
    //同上 用txt操作
    //执行失败返回NULL，内容为空返回空字符串
    public function SelectF($url) {
        if (!isset($url) || empty($url)) {
            return NULL;
        }
        $txt = new File($url);
        if (!$sql = $txt->ReadTxt()) {
            return NULL;
        }
        return $this->Select($sql);
    }
    //string查询 返回关联数组
    //sql语句错误返回NULL，sql语句正确返回二维关联数组arr[记录编号]['属性']
    public function SelectAssoc($sql) {
        if (!isset($sql) || empty($sql)) {
            return NULL;
        }
        $this->result = $this->mysqli->query($sql);
        $this->err = $this->mysqli->error;
        if (!$this->err && $this->result !== true) $arr = $this->result->fetch_all(MYSQLI_ASSOC);
        return $arr;
    }
    //txt查询 返回关联数组
    public function SelectAssocF($url) {
        if (!isset($url) || empty($url)) {
            return NULL;
        }
        $txt = new File($url);
        if (!$sql = $txt->ReadTxt()) {
            return NULL;
        }
        return $this->SelectAssoc($sql);
    }
    //string查询 返回数字下标数组
    //sql语句错误返回NULL，sql语句正确返回二维数字下标数组arr[记录编号][属性编号]
    public function SelectNum($sql) {
        if (!isset($sql) || empty($sql)) {
            return NULL;
        }
        $this->result = $this->mysqli->query($sql);
        $this->err = $this->mysqli->error;
        if (!$this->err && $this->result !== true) $arr = $this->result->fetch_all(MYSQLI_NUM);
        return $arr;
    }
    //txt查询 返回数字下标数组
    public function SelectNumF($url) {
        if (!isset($url) || empty($url)) {
            return NULL;
        }
        $txt = new File($url);
        if (!$sql = $txt->ReadTxt()) {
            return NULL;
        }
        return $this->SelectNum($sql);
    }
    /*用string执行更新语句 返回bool
    虽然建表函数和更新函数本质差别不大，以后可能会分化 */
    public function Update($sql) {
        if (!isset($sql)||empty($sql)) {
            return false;
        }
        if ($this->mysqli->multi_query($sql)) {
            $this->err = $this->mysqli->error;
            //清除结果集缓冲 以便进行后续操作
            do {
                    if ($this->result = $this->mysqli->store_result()) {
                        //如需读取请额外添加
                        $this->result->free();
                    }
            } while ($this->mysqli->next_result());
            return true;
        }
        else {
            $this->err = $this->mysqli->error;
            return false;
        }
    }
    //同上 用txt操作
    public function UpdateF($url) {
        if (!isset($url)||empty($url)) {
            return false;
        }
        $txt = new File($url);
        if (!$sql = $txt->ReadTxt()) {
            return false;
        }
        return $this->Update($sql);
    }
    //返回错误信息
    public function Error() {
        return $this->err;
    }
    //返回连接错误信息
    public function ErrorC() {
        return $this->connerr;
    }
}