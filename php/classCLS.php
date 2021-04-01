<?php
include_once "classDB.php";
/*************************************************************************************************
 * **********************************************************************************************
 * 
 *          班级类(classCLS) 
 *          它的实例化表示<一个班级>用于进行班级相关操作及其信息获取
 *          2021/3/31   版本1.0     创建班级类
 *             
 *          
 * +--+-+-+--+-+-+-+--+函数功能说明+-+-+-+-+-+-+-+-+-+-+-+-+--+-+-+-+-+-+-
 * 
 * 1.__construct($url)     |   构造函数
 * 2.__destruct()          |   析构函数
 * 3.
 * 
 * **********************************************************************************************
 ***********************************************************************************************/
class CLS {
    protected $creator_email = NULL;//创建人邮箱
    protected $creator_name = NULL;//创建人名称
    protected $name = NULL;//班级名称
    protected $invitation = NULL;//邀请码

    protected $member_list = NULL;//成员列表

    protected $homework_list = NULL;//作业列表

    //------------------------------//
    //构造函数 拷贝信息
    public function __construct($creator_email = NULL, $creator_name = NULL, $name = NULL, $invitation = NULL){
        $this->creator_email = $creator_email;
        $this->creator_name = $creator_name;
        $this->name = $name;
        $this->invitation = $invitation;
    }
    //创建班级 json 构造函数需要全部参数
    public function CreateClass() {
        //准备json
        $arr = array();
        $arr['success'] = false;
        $arr['checkname'] = false;
        $arr['checkinvitation'] = false;
        //连接数据库
        $SQLOJ_USER = new DB('localhost', 'root', 'SQLoj2021.', 'SQLOJ_USER');
        //检查非空
        if ($this->creator_email == NULL || $this->creator_name == NULL || $this->name == NULL || $this->invitation == NULL) {
            unset($SQLOJ_USER);
            return json_encode($arr);
        }
        //班级名查重
        $sql = "select * from CLASS_OVERVIEW where classname = '$this->name';";
        $res = $SQLOJ_USER->SelectAssoc($sql);
        if (count($res) != 0) {
            unset($SQLOJ_USER);
            return json_encode($arr);
        }
        $arr['checkname'] = true;
        //邀请码查重
        $sql = "select * from CLASS_OVERVIEW where invitation = '$this->invitation';";
        $res = $SQLOJ_USER->SelectAssoc($sql);
        if (count($res) != 0) {
            unset($SQLOJ_USER);
            return json_encode($arr);
        }
        $arr['checkinvitation'] = true;
        //插入数据库
        $sql = "    insert
                    into CLASS_OVERVIEW
                    value
                    ('$this->name', '$this->creator_email', '$this->creator_name', now(), '$this->invitation');";
        if (!$SQLOJ_USER->Update($sql)) {
            unset($SQLOJ_USER);
            return json_encode($sql);
        }
        $arr['success'] = true;
        unset($SQLOJ_USER);
        return json_encode($arr);
    }
    //返回班级成员列表
    //待修改 一个老师有多个班级 先获取班级列表 再获取班级名单
    public function GetMemberList($classname) {
        //准备json
        $arr = array();
        $arr['success'] = false;
        $arr['member'] = NULL;
        //连接数据库
        $SQLOJ_USER = new DB('localhost', 'root', 'SQLoj2021.', 'SQLOJ_USER');
        //检查非空
        if ($this->creator_email == NULL) {
            unset($SQLOJ_USER);
            return json_encode($arr);
        }
        //检索班级信息
        $sql = "select * from CLASS_OVERVIEW where creatoremail = '$this->creator_email';";
        $res = $SQLOJ_USER->SelectAssoc($sql);
        if (count($res) != 0) {
            unset($SQLOJ_USER);
            return json_encode($arr);
        }
        //查看成员
        $sql = "select * from CLASS_MEMBER where class = '$classname';";
        $this->member_list = $SQLOJ_USER->SelectAssoc($sql);
        if ($SQLOJ_USER->Error() == NULL || strlen($SQLOJ_USER->Error()) == 0) {
            $arr['success'] = true;
            $arr['member'] = $this->member_list;
            unset($SQLOJ_USER);
            return json_encode($arr);
        }
        unset($SQLOJ_USER);
        return json_encode($arr);
    }
    //删除班级成员
    //邀请班级成员
    //查看班级作业
    //删除班级作业
    //增加班级作业
}