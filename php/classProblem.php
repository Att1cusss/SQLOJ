<?php
include_once "classFile.php";
include_once "classDB.php";
include_once "classBS.php";
/*************************************************************************************************
 * **********************************************************************************************
 * 
 *          题目类(classProblem) 提供了SQLOJ系统中常用の题目运行环节の方法
 *          它的实例化表示<一道正在运行的题目>
 *          2021/3/5    版本1.0     构建题目类并写了一些基本函数用于测试
 *          2021/3/9    版本2.0     重构题目类和文件组织
 *          2021/3/15   版本2.1     修复构造函数bug
 *          2021/3/22   版本2.2     修复bug,引入BS类并增加更新BS功能
 *          2021/3/23   版本2.3     新增添加做题记录功能
 *          2021/3/26   版本2.4     修改添加做题记录和更新BS功能
 *      
 * +--+-+-+--+-+-+-+--+函数功能说明+-+-+-+-+-+-+-+-+-+-+-+-+--+-+-+-+-+-+-
 * 
 * 1.nextCase()                             |   样例迭代器加载下一个样例,返回bool,0表示已到达最后一个样例
 * 2.makeName()                             |   生成一个随机表名并返回 命名规则:"u"+[userID]+"p"+[problemID]+"t"+[时间戳(小数点用d表示)]
 * 3.__construct($userID, $problemID, $userSql) 构造函数
 * 4.__destruct()                           |   析构函数
 * 5.replaceName($str)                      |   将str中出现的表名转换为对应的随机表名
 * 6.createTable()                          |   建表 返回bool
 * 7.dropTable()                            |   删表 返回bool
 * 8.judgeQuery()                           |   判当前查询类样例,返回bool表示正误 需要注意的是:判完一个样例后迭代器没有后移,需调用nextCase
 * 9.judgeUpdate()                          |   判一个更新类样例,其他同上
 * 10.updateBS()                            |   更新用户BS 返回bool
 * 11.addHistory()                          |   添加用户做题记录 返回bool
 * 12.judgeAuto()                           |   自动判题 流程说明:建表->循环(判题)->删表->汇总信息
 * 13.isCorrect()                           |   是否正确
 * 14.acNumber()                            |   通过样例数
 * 15.totNumber()                           |   总样例数
 * 16.takeTime($digit)                      |   用户用时(并非系统用时)
 * 17.isError()                             |   用户的语句是否有执行错误 1有0无
 * 18.systemError()                         |   返回判题过程中的错误信息 参见成员变量声明下方的[错误信息收集]
 * 19.testPrint()                           |   用于测试时的输出,可忽略
 *
 * **********************************************************************************************
 ***********************************************************************************************/
class Problem{
    //----从前端获取的-----
    protected $userID = NULL;           //做题用户的ID(邮箱)
    protected $userName = NULL;         //用户名
    protected $problemID = NULL;        //问题的ID
    protected $userSql = NULL;          //做题用户的sql语句

    //---根据文件地址拿到的json对象---
    protected $problem_obj = NULL;

    //----判题信息----
    protected $type = NULL;             //题目类型 1是更新类 0是查询类
    protected $tableName = NULL;        //题目表名 关联数组tableName[tableID] = Name
    protected $problemName = NULL;      //题目名称
    protected $randomName = NULL;       //此题的随机表名 randomName[tableName] = randomName
    protected $table_tot = NULL;        //题目涉及表总数
    protected $case_tot = NULL;         //题目样例数
    protected $case_cnt = NULL;         //当前样例
    protected $iterator = NULL;         //样例迭代器 类型:对象一维数组
    protected $time_s = NULL;           //用户开始时间
    protected $time_e = NULL;           //用户结束时间
    protected $correct = NULL;          //题目是否正确
    protected $accept = NULL;           //题目ac数量
    protected $error = NULL;            //用户的语句是否有执行错误
    protected $pass = NULL;             //用户是否通过预先测试(advanceTest)

    protected $create = NULL;           //建表string数组table id=>str
    protected $insert = NULL;           //插入string数组case id=>str
    protected $drop = NULL;             //删表string数组table id=>str
    protected $answer = NULL;           //答案json对象

    protected $sys_error = NULL;        //判题流程中出现的错误

    protected $char = NULL;             //防止随机表名相同的尾缀字母
    //最低位0表示未出现错误，1表示出现错误，错误信息在高位表示
    /**---------------------[错误信息收集]---------------------------
     * 
     *          8       4       2       1
     * 
     *      |   0   |   0   |   0   |   0   |
     * 
     *        第3位   第2位    第1位   第0位
     * 
     *      第0位:指示是否有错误 当且仅当$sys_error === 0时,在判题流程中没有出现过系统级错误
     *      第1位:createTable()建表错误
     *      第2位:dropTable()错误
     *      第3位:判题结束更新Overview表失败
     *      请自定义
     *      第4位:更新BS错误
     *      第5位:添加做题记录错误
     *      
     *-----------------------------------------------------------------*/

    //样例迭代器加载下一个样例 返回bool 请使用do while语句利用迭代器判题
    public function nextCase() {
        //已到达最后一个样例
        if (++$this->case_cnt == $this->case_tot) {
            return false;
        }
        $this->iterator = $this->problem_obj->case[$this->case_cnt];
        return true;
    }
    //生成一个随机表名 命名规则:"u"+[userID]+"p"+[problemID]+"t"+[时间戳(小数点用d表示)]+自增字符
    public function makeName() {
        $t = (string)microtime(true);
        $t = str_replace(".", "d", $t);
        $email = str_replace('.', 'd', $this->userID);
        $email = str_replace('@', 'at', $email);
        return ('u'.$email.'p'.$this->problemID.'t'.$t.$this->char++);
    }
    //构造函数 加载判题所需信息
    public function __construct($userID, $userName, $problemID, $userSql) {
        $this->char = 'a';
        //拷贝数据前端
        $this->userID = $userID;
        $this->userName = $userName;
        $this->problemID = $problemID;
        $this->userSql = $userSql;
        //建立连接
        $SQLOJ_QUESTION = new DB('localhost', 'question_root', 'Questionroot1.', 'SQLOJ_QUESTION');
        //读终测表
        $sql = "select * from finalTest where problemID=$this->problemID;";
        $record = $SQLOJ_QUESTION->SelectAssoc($sql);
        $json = new File($record[0]['jsonFile']);
        //保存题目json对象
        $this->problem_obj = $json->ReadJson();
        //保存题目每个样例的表数量
        $this->table_tot = $this->problem_obj->table_number;
        //保存题目样例数
        $this->case_tot = $this->problem_obj->case_number;
        //为样例迭代器加载第一个样例数组
        $this->case_cnt = 0;
        $this->iterator = $this->problem_obj->case[0];
        //获取答案json
        $file = new File($record[0]['answerFile']);
        $this->answer = $file->ReadJson();
        unset($file);
        //由样例迭代器 保存每个表的表名 同时生成此题的随机表名 形成映射
        $this->tableName = array();
        $this->randomName = array();
        $this->create = array();
        $this->drop = array();
        for($i = 0; $i < $this->table_tot; $i++) {
            $id = $this->iterator[$i]->tableID;
            $sql = "select * from tableLibrary where tableID=$id;";
            $record = $SQLOJ_QUESTION->SelectAssoc($sql);
            //id=>表名
            $array = array("$id" => $record[0]['tableName']);
            $this->tableName += $array;
            //表名=>随机名
            $array = array($record[0]['tableName'] => $this->makeName());
            $this->randomName += $array;
            //id=>建表sql
            $file = new File($record[0]['createFile']);
            $array = array("$id" => $file->ReadTxt());
            $this->create += $array;
            unset($file);
            //id=>删表sql
            $file = new File($record[0]['dropFile']);
            $array = array("$id" => $file->ReadTxt());
            $this->drop += $array;
            unset($file);
        }
        //初始化判题信息
        $this->accept = 0;
        $this->error = false;
        //读总览表
        $SQLOJ_QUESTION = new DB('localhost', 'question_root', 'Questionroot1.', 'SQLOJ_QUESTION');
        $sql = "select * from Overview where problemID=$this->problemID;";
        $record = $SQLOJ_QUESTION->SelectAssoc($sql);
        $this->type=(int)$record[0]['problemType'];
        $this->problemName = $record[0]['problemName'];
        //读样例sql 用迭代器遍历所有insert语句 并保存
        $this->insert = array();
        do{
            for($i = 0; $i < $this->table_tot; $i++) {
                $id = $this->iterator[$i]->caseID;
                $sql = "select * from dataCase where caseID=$id;";
                $record = $SQLOJ_QUESTION->SelectAssoc($sql);
                $file = new File($record[0]['insertFile']);
                $array = array("$id" => $file->ReadTxt());
                $this->insert += $array;
            }
        } while ($this->nextCase());
        //迭代器重新定位到第一个case
        $this->case_cnt = 0;
        $this->iterator = $this->problem_obj->case[0];
        //断开数据库
        unset($SQLOJ_QUESTION);
        
        //var_dump($this->randomName);
        
    }
    //析构函数
    public function __destruct() {
        ;
    }
    //将str中出现的表名转换为对应的随机表名
    public function replaceName($str) {
        foreach($this->tableName as $k => $v) {
            $str = str_replace($v, $this->randomName[$v], $str);
        }
        return $str;
    }
    //建表 返回bool
    public function createTable() {
        //更新类
        if ($this->type == 1) {
            $SQLOJ_UPDATE_TEMP = new DB('localhost', 'update_temp_judge', 'Updatetempjudge1.', 'SQLOJ_UPDATE_TEMP');
            //遍历每个表 只create
            foreach($this->tableName as $k => $v) {
                $createSql = $this->replaceName($this->create[$k]);
                if (!$SQLOJ_UPDATE_TEMP->CreateTable($this->replaceName($createSql))) {
                    unset($SQLOJ_UPDATE_TEMP);
                    $this->sys_error |= 3;
                    return false;
                }
            }
            unset($SQLOJ_UPDATE_TEMP);
            return true;
        }
        //查询类
        $SQLOJ_SELECT_TEMP = new DB('localhost', 'select_temp_judge', 'Selecttempjudge1.', 'SQLOJ_SELECT_TEMP');
        //遍历每个表 只create
        foreach($this->tableName as $k => $v) {
            $createSql = $this->replaceName($this->create[$k]);
            if (!$SQLOJ_SELECT_TEMP->CreateTable($this->replaceName($createSql))) {
                unset($SQLOJ_SELECT_TEMP);
                $this->sys_error |= 3;
                return false;
            }
        }
        unset($SQLOJ_SELECT_TEMP);
        return true;
    }
    //drop表 返回bool
    public function dropTable() {
        //更新类
        if ($this->type == 1) {
            //连接
            $SQLOJ_UPDATE_TEMP = new DB('localhost', 'update_temp_judge', 'Updatetempjudge1.', 'SQLOJ_UPDATE_TEMP');
            //遍历每个表 只drop
            foreach($this->tableName as $k => $v) {
                $dropSql = $this->drop[$k];
                if (!$SQLOJ_UPDATE_TEMP->Update($this->replaceName($dropSql))) {
                    unset($SQLOJ_UPDATE_TEMP);
                    $this->sys_error |= 5;
                    return false;
                }
            }
            unset($SQLOJ_UPDATE_TEMP);
            return true;
        }
        //查询类
        $SQLOJ_SELECT_TEMP = new DB('localhost', 'select_temp_judge', 'Selecttempjudge1.', 'SQLOJ_SELECT_TEMP');
        //遍历每个表 只create
        foreach($this->tableName as $k => $v) {
            $dropSql = $this->drop[$k];
            if (!$SQLOJ_SELECT_TEMP->Update($this->replaceName($dropSql))) {
                unset($SQLOJ_SELECT_TEMP);
                $this->sys_error |= 5;
                return false;
            }
        }
        unset($SQLOJ_SELECT_TEMP);
        return true;
    }
    //判当前查询类样例,返回bool表示正误 需要注意的是:判完一个样例后迭代器没有后移,需调用nextCase
    //另外要注意 执行判题前确保先调用了createTable建表 这在judgeAuto已有保证
    public function judgeQuery() {
        //连接数据库
        $SQLOJ_SELECT_TEMP = new DB('localhost', 'select_temp_judge', 'Selecttempjudge1.', 'SQLOJ_SELECT_TEMP');
        //遍历迭代器中的每个表 只insert
        for($i = 0; $i < $this->table_tot; $i++) {
            $insertSql = $this->insert[$this->iterator[$i]->caseID];
            $SQLOJ_SELECT_TEMP->Update($this->replaceName($insertSql));
        }
        //断开连接
        unset($SQLOJ_SELECT_TEMP);
        //更换专用用户 开始执行用户查询语句
        $SQLOJ_SELECT_TEMP = new DB('localhost', 'select_temp_submit', 'Selecttempsubmit1.', 'SQLOJ_SELECT_TEMP');
        //得到用户答案
        $userAnswer = $SQLOJ_SELECT_TEMP->Select($this->replaceName($this->userSql));
        //断开连接
        unset($SQLOJ_SELECT_TEMP);
        //删表中的所有记录 并非drop
        $SQLOJ_SELECT_TEMP = new DB('localhost', 'select_temp_judge', 'Selecttempjudge1.', 'SQLOJ_SELECT_TEMP');
        foreach($this->tableName as $k => $v) {
            $name = $this->randomName[$v];
            $sql = "truncate table $name;";
            $SQLOJ_SELECT_TEMP->Update($sql);
        }
        //断开连接
        unset($SQLOJ_SELECT_TEMP);
        //答案错误
        if ($userAnswer != $this->answer->answer[$this->case_cnt]) {
            return false;
        }
        return true;
    }
    //判一个更新类样例
    /*
        多表更新题，答案是每个测试样例中，按表顺序读取表状态汇总成一个字符串    
    */
    public function judgeUpdate() {
        //连接数据库
        $SQLOJ_UPDATE_TEMP = new DB('localhost', 'update_temp_judge', 'Updatetempjudge1.', 'SQLOJ_UPDATE_TEMP');
        //遍历迭代器中的每个表 只insert
        for($i = 0; $i < $this->table_tot; $i++) {
            $insertSql = $this->insert[$this->iterator[$i]->caseID];
            $SQLOJ_UPDATE_TEMP->Update($this->replaceName($insertSql));
        }
        //断开连接
        unset($SQLOJ_UPDATE_TEMP);
        //更换专用用户 开始执行用户更新语句
        $SQLOJ_UPDATE_TEMP = new DB('localhost', 'update_temp_submit', 'Updatetempsubmit1.', 'SQLOJ_UPDATE_TEMP');
        //执行用户更新 收集语句执行错误信息
        $this->error = !$SQLOJ_UPDATE_TEMP->Update($this->replaceName($this->userSql));
        //断开用户连接
        unset($SQLOJ_UPDATE_TEMP);
        //连接数据库
        $SQLOJ_UPDATE_TEMP = new DB('localhost', 'update_temp_judge', 'Updatetempjudge1.', 'SQLOJ_UPDATE_TEMP');
        //获得用户答案(按序遍历表)
        $userAnswer = "";
        //因为需要按顺序 所以使用迭代器(iterator)遍历而非表名列表($this->tableName)
        for($i = 0; $i < $this->table_tot; $i++) {
            //获取对应的随机表名
            $name = $this->randomName[$this->tableName[$this->iterator[$i]->tableID]];
            $sql = "select * from $name;";
            $userAnswer .= $SQLOJ_UPDATE_TEMP->Select($sql);
        }
        //答案获取完毕后 删表中的所有记录 并非drop
        foreach($this->tableName as $k => $v) {
            $name = $this->randomName[$v];
            $sql = "truncate table $name;";
            $SQLOJ_UPDATE_TEMP->Update($sql);
        }
        //断开连接
        unset($SQLOJ_UPDATE_TEMP);
        //判断正误
            //答案错误
        if($userAnswer != $this->answer->answer[$this->case_cnt]) {
            return false;
        }
            //答案正确
        return true;
    }
    //更新BitSt return(bool)
    public function updateBS() {
        //连接数据库
        $SQLOJ_USER = new DB('localhost', 'root', 'SQLoj2021.', 'SQLOJ_USER');
        //计算正确BS编号
        $bs = new BS();
        $bsid = $this->problemID % 60 == 0 ? 60 : $this->problemID % 60;
        $bsno = (int)($this->problemID / 60) + (bool)($this->problemID % 60);
        //获得用户相应的BS
        $sql = "select * from RECORD where USER_EMAIL_='$this->userID' and BSNO=$bsno;";
        $arr_bs = $SQLOJ_USER->SelectAssoc($sql);
        //判断用户有无对应BS
        $exist = (bool)count($arr_bs);
        //需要生成BS
        if (!$exist) {
            $bs->SetBit($bsid, 1);
            $data = $bs->GetBS();
            $sql = "INSERT INTO RECORD VALUES ('$this->userID', '$this->userName', $data, $bsno);";
            if (!$SQLOJ_USER->Update($sql)) {
                unset($SQLOJ_USER);
                return false;
            }
            unset($SQLOJ_USER);
            return true;
        }
        //仅需更新BS
        else {
            $bs = new BS((int)$arr_bs[0]['BS']);
            $bs->SetBit($bsid, 1);
            $data = $bs->GetBS();
            $sql = "UPDATE RECORD SET BS = $data WHERE USER_EMAIL_ = '$this->userID';";
            if (!$SQLOJ_USER->Update($sql)) {
                unset($SQLOJ_USER);
                return false;
            }
            unset($SQLOJ_USER);
            return true;
        }
    }
    //添加用户做题记录 返回bool
    public function addHistory() {
        //连接数据库
        $SQLOJ_USER = new DB('localhost', 'root', 'SQLoj2021.', 'SQLOJ_USER');
        //是否正确
        $ac = (int)$this->correct;
        //用时
        $usetime = (int)round(($this->time_e - $this->time_s) * 1000, 0);
        //正确与否
        $ac = (int)$this->correct;

        $sql =  "   insert
                    into HISTORY(USER_EMAIL_, USER_NAME_, problemID, problemName, datetime, accept, usetime)
                    value('$this->userID', '$this->userName', $this->problemID, '$this->problemName', now(), $ac, $usetime);
                ";
        //返回执行结果
        if (!$SQLOJ_USER->Update($sql)) {
            unset($SQLOJ_USER);
            return false;
        }
        unset($SQLOJ_USER);
        return true;
    }
    //自动判题 流程说明:建表->循环(判题)->删表
    public function judgeAuto() {
        //更新类题
        if ($this->type == 1) {
            //建表
            $this->createTable();
            //判题
            $this->time_s = microtime(true);
            do{
                $this->accept += $this->judgeUpdate();
            } while ((!$this->sys_error) && $this->nextCase());
            $this->time_e = microtime(true);
            //删表
            $this->dropTable();
            //汇总信息
            $this->correct = ($this->accept == $this->case_tot);
            //连接数据库 更新Overview
            $SQLOJ_QUESTION = new DB('localhost', 'question_root', 'Questionroot1.', 'SQLOJ_QUESTION');
            //如果通过 正确记录+1
            if ($this->correct) {
                $sql = "update Overview set acceptTime=acceptTime+1 where problemID=$this->problemID;";
                if (!$SQLOJ_QUESTION->Update($sql)) {
                    $this->sys_error |= 9;
                    return false;
                }
                //如果通过 更新BS
                if (!$this->updateBS()) {
                    $this->sys_error |= 17;
                    return false;
                }
            }
            //无论通过与否 总记录+1
            $sql = "update Overview set submitTime=submitTime+1 where problemID=$this->problemID;";
            if (!$SQLOJ_QUESTION->Update($sql)) {
                $this->sys_error |= 9;
                return false;
            }
            //添加做题记录
            if (!$this->addHistory()) {
                $this->sys_error |= 33;
                return false;
            }
            return true;
        }
        //查询类题目
        //建表
        $this->createTable();
        //判题
        $this->time_s = microtime(true);
        do{
            $this->accept += $this->judgeQuery();
        } while ((!$this->sys_error) && $this->nextCase());
        $this->time_e = microtime(true);
        //删表
        $this->dropTable();
        //汇总信息
        $this->correct = ($this->accept == $this->case_tot);
        //连接数据库 更新Overview
        $SQLOJ_QUESTION = new DB('localhost', 'question_root', 'Questionroot1.', 'SQLOJ_QUESTION');
        //如果通过 正确记录+1
        if ($this->correct) {
            $sql = "update Overview set acceptTime=acceptTime+1 where problemID=$this->problemID;";
            if (!$SQLOJ_QUESTION->Update($sql)) {
                $this->sys_error |= 9;
                return false;
            }
            //如果通过 更新BS
            if (!$this->updateBS()) {
                $this->sys_error |= 17;
                return false;
            }
        }
        //无论通过与否 总记录+1
        $sql = "update Overview set submitTime=submitTime+1 where problemID=$this->problemID;";
        if (!$SQLOJ_QUESTION->Update($sql)) {
            $this->sys_error |= 9;
            return false;
        }
        //添加做题记录
        if (!$this->addHistory()) {
            $this->sys_error |= 33;
            return false;
        }
        return true;
    }
    //是否正确
    public function isCorrect() {
        return $this->correct;
    }
    //通过样例数
    public function acNumber() {
        return $this->accept;
    }
    //总样例数
    public function totNumber() {
        return $this->case_tot;
    }
    //用户用时 保留digit位小数
    public function takeTime($digit) {
        return round($this->time_e - $this->time_s, $digit);
    }
    //用户语句是否有执行错误
    public function isError() {
        return $this->error;
    }
    //返回系统错误信息
    public function systemError() {
        return (int)$this->sys_error;
    }
    //testing123..
    public function testPrint() {
        if ($this->isCorrect()) {
            echo "<font size=\"100\" color=\"green\">Accept</font><br>";
            echo "<font size=\"5\" >执行时间:".$this->takeTime(3)."s<br>";
            echo "通过样例:".$this->acNumber()."/".$this->totNumber()."<br>";
            echo "错误信息:".$this->systemError()."</font>";
            return ;
        }
        echo "<font size=\"100\" color=\"red\">Wrong Answer</font><br>";
        echo "<font size=\"5\" >执行时间:".$this->takeTime(3)."s<br>";
        echo "通过样例:".$this->acNumber()."/".$this->totNumber()."<br>";
        echo "错误信息:".$this->systemError()."</font>";
        return ;
    }
}