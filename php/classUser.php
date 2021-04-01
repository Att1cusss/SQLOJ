<?php
include_once "classDB.php";
/*************************************************************************************************
 * **********************************************************************************************
 * 
 *          用户类(classUser) 
 *          它的实例化表示<一名用户>
 *          2021/3/28       版本1.0     构建用户类 
 *          2021/3/30       版本1.1     扩充方法   
 *          
 * +--+-+-+--+-+-+-+--+函数功能说明+-+-+-+-+-+-+-+-+-+-+-+-+--+-+-+-+-+-+-
 * 
 * 1.__construct($url)      |       构造函数 拷贝信息 开始会话
 * 2.LoginStatus()          |       登录状态检测  
 * 3.Exit()                 |       退出登录
 * 4.Login()                |       登录
 * 5.UniqueName_normal()    |       普通用户邮箱查重
 * 6.UniqueName_vip()       |       出题用户名查重
 * 7.UniqueEmail_normal()   |       普通用户邮箱查重
 * 8.UniqueEmail_vip()      |       出题用户邮箱查重
 * 9.Insert_normal()        |       普通用户信息录入
 * 10.Insert_vip()          |       出题用户信息录入
 * 11.CheckInvitation()     |       检查邀请码
 * 12.Registration_normal() |       普通用户注册
 * 13.Registration_vip()    |       出题用户注册
 * 14.Registration()        |       注册
 * 15.Personalinfo()        |       显示个人主页
 * 
 * **********************************************************************************************
 ***********************************************************************************************/


/*+------------------------------[各种操作初始化对象时必需参数]---------------------------------------+
 * 
 *  1.登录状态检测：使用session完成 不需要参数
 *      $user = new User();
 * 
 *  2.退出登录：使用session和cookie 不需要参数
 *      $user = new User();
 * 
 *  3.登录：需要邮箱和密码
 *      $user = new User($email , $password);
 * 
 *  4.注册：需要邮箱、用户名、密码，邀请码选填 
 *      $user = new User($email , $password , $name , $invitation);
 * 
 *  5.显示个人主页：需要邮箱
 *      $user = new User($email);
 * 
 *  6.加入班级 使用session 无参
 *      $user = new User();
 * 
 ++--------------------------------------------------------------------*/


/*------------------------【错误信息表】----------------------------
 *                      
 *      若error与下列某数相与不为0 则表示发生了该数表示的错误 后面记录了可能导致该错误的函数
 *      1:      [普通用户名查重]错误           |    UniqueName_normal()
 *      2:      [出题用户名查重]错误           |    UniqueName_vip()
 *      4:      [普通用户邮箱查重]错误         |    UniqueEmail_normal()
 *      8:      [出题用户邮箱查重]错误         |    UniqueEmail_vip()
 *     16:      [普通用户信息录入]错误         |    Insert_normal()
 *     32:      [出题用户信息录入]错误         |    Insert_vip()
 *     64:      [检查邀请码]错误               |    CheckInvitation()                
 *    128:      [用户登录]错误                 |    Login()
 * 
 *--------------------------------------------------------------*/
class User{
    //邮箱 用户名 密码
    protected $email = NULL;
    protected $name = NULL;
    protected $password = NULL;
    //
    protected $invitation = NULL;
    protected $organization = NULL;

    protected $error = 0;//内部错误(和用户无关)

    //拷贝信息 开始会话
    public function __construct($email = NULL, $password = NULL, $name = NULL, $invitation = NULL) {
        session_start();
        $this->email = $email;
        $this->password = $password;
        $this->invitation = $invitation;
        $this->name = $name;
    }

    //用户登录状态检测
    public function LoginStatus() {
        if (isset($_SESSION) && $_SESSION['login'] === true) {
            return (json_encode(
                            array(
                                'login' => true,'username' => $_SESSION['username'],'email' => $_SESSION['email'], 'root' => $_SESSION['root']
                                 )
                              )
                    );
        }
        else {
            $_SESSION['login'] = false;
            return (json_encode(array('login' => false)));
        }
    }
    //退出登录 返回json
    public function Exit() {
        $res = true;
        $_SESSION['login'] = false;
        // 清除cookie
        setcookie('PHPSESSID', '', time()-1); 
        unset($_SESSION);
        if ($_SESSION['login']) {
            $res=false;
        }
        return json_encode($res);
    }

    //用户登录 返回json
    public function Login() {
        //注销登录状态
        $_SESSION['login'] = false;

        //准备数据
        $info = array();
        $info['login'] = false;//是否登录成功
        $info['username'] = null;//用户名
        $info['useremail'] = null;//用户邮箱
        $info['error'] = $this->error;//是否有发生错误(内部错误)
        $info['root'] = false;//是否是出题用户

        //先检查是否是vip用户
        $sql = "select * from ROOT where USER_EMAIL_ = '$this->email' and USER_PASSWORD_ = '$this->password';";
        $SQLOJ_USER = new DB('localhost', 'root', 'SQLoj2021.', 'SQLOJ_USER');
        //收集错误
        if ($info['error'] |= (bool)(strlen($SQLOJ_USER->ErrorC())) * 128) {
            unset($SQLOJ_USER);
            return json_encode($info);
        }
        //获取记录
        $res = $SQLOJ_USER->SelectAssoc($sql);
        //收集错误
        if ($info['error'] |= (bool)(strlen($SQLOJ_USER->Error())) * 128) {
            unset($SQLOJ_USER);
            return json_encode($info);
        }
        //是vip
        if (count($res) == 1) {
            $info['login'] = true;//登录成功
            $info['username'] = $res[0]['USER_NAME_'];
            $info['useremail'] = $this->email;
            $info['root'] = true;

            //更新session
            $_SESSION['login'] = true;//登录状态
            $_SESSION['username'] = $res[0]['USER_NAME_'];//用户名
            $_SESSION['email'] = $this->email;//邮箱
            $_SESSION['root'] = true;//是出题用户

            unset($SQLOJ_USER);
            return json_encode($info);
        }
        //-----------------------------------------------------
        //后检查 普通用户
        $sql = "select * from USER where USER_EMAIL_ = '$this->email' and USER_PASSWORD_ = '$this->password';";
        $SQLOJ_USER = new DB('localhost', 'root', 'SQLoj2021.', 'SQLOJ_USER');
        //收集错误
        if ($info['error'] |= (bool)(strlen($SQLOJ_USER->ErrorC())) * 128) {
            unset($SQLOJ_USER);
            return json_encode($info);
        }
        //获取记录
        $res = $SQLOJ_USER->SelectAssoc($sql);
        //收集错误
        if ($info['error'] |= (bool)(strlen($SQLOJ_USER->Error())) * 128) {
            unset($SQLOJ_USER);
            return json_encode($info);
        }
        //存在该用户
        if (count($res) == 1) {
            $info['login'] = true;//登录成功
            $info['username'] = $res[0]['USER_NAME_'];
            $info['useremail'] = $this->email;
            $info['root'] = false;

            //更新session
            $_SESSION['login'] = true;//登录状态
            $_SESSION['username'] = $res[0]['USER_NAME_'];//用户名
            $_SESSION['email'] = $this->email;//邮箱
            $_SESSION['root'] = false;//不是出题用户

            unset($SQLOJ_USER);
            return json_encode($info);
        }

        //登录失败
        unset($SQLOJ_USER);
        return json_encode($info);
    }

    //普通用户数据库用户名查重 没有重复返回true
    public function UniqueName_normal() {
        $sql = "select * from USER where USER_NAME_ = '$this->name';";
        $SQLOJ_USER = new DB('localhost', 'root', 'SQLoj2021.', 'SQLOJ_USER');
        //收集错误
        if ($this->error |= (bool)(strlen($SQLOJ_USER->ErrorC())) * 1) {
            unset($SQLOJ_USER);
            return false;
        }
        $res = $SQLOJ_USER->SelectAssoc($sql);
        //收集错误
        if ($this->error |= (bool)(strlen($SQLOJ_USER->Error())) * 1) {
            unset($SQLOJ_USER);
            return false;
        }
        unset($SQLOJ_USER);
        return (bool)(count($res) == 0);
    }

    //出题用户数据库用户名查重 没有重复返回true
    public function UniqueName_vip() {
        $sql = "select * from ROOT where USER_NAME_ = '$this->name';";
        $SQLOJ_USER = new DB('localhost', 'root', 'SQLoj2021.', 'SQLOJ_USER');
        //收集错误
        if ($this->error |= (bool)(strlen($SQLOJ_USER->ErrorC())) * 2) {
            unset($SQLOJ_USER);
            return false;
        }
        $res = $SQLOJ_USER->SelectAssoc($sql);
        //收集错误
        if ($this->error |= (bool)(strlen($SQLOJ_USER->Error())) * 2) {
            unset($SQLOJ_USER);
            return false;
        }
        unset($SQLOJ_USER);
        return (bool)(count($res) == 0);
    }

    //普通用户数据库邮箱查重 没有重复返回true
    public function UniqueEmail_normal() {
        $sql = "select * from USER where USER_EMAIL_ = '$this->email';";
        $SQLOJ_USER = new DB('localhost', 'root', 'SQLoj2021.', 'SQLOJ_USER');
        //收集错误
        if ($this->error |= (bool)(strlen($SQLOJ_USER->ErrorC())) * 4) {
            unset($SQLOJ_USER);
            return false;
        }
        $res = $SQLOJ_USER->SelectAssoc($sql);
        //收集错误
        if ($this->error |= (bool)(strlen($SQLOJ_USER->Error())) * 4) {
            unset($SQLOJ_USER);
            return false;
        }
        unset($SQLOJ_USER);
        return (bool)(count($res) == 0);
    }

    //出题用户数据库邮箱查重 没有重复返回true
    public function UniqueEmail_vip() {
        $sql = "select * from ROOT where USER_EMAIL_ = '$this->email';";
        $SQLOJ_USER = new DB('localhost', 'root', 'SQLoj2021.', 'SQLOJ_USER');
        //收集错误
        if ($this->error |= (bool)(strlen($SQLOJ_USER->ErrorC())) * 8) {
            unset($SQLOJ_USER);
            return false;
        }
        $res = $SQLOJ_USER->SelectAssoc($sql);
        //收集错误
        if ($this->error |= (bool)(strlen($SQLOJ_USER->Error())) * 8) {
            unset($SQLOJ_USER);
            return false;
        }
        unset($SQLOJ_USER);
        return (bool)(count($res) == 0);
    }

    //将用户信息存入普通用户数据库 成功返回true
    public function Insert_normal() {
        $sql = "insert into USER(USER_EMAIL_,USER_NAME_,USER_PASSWORD_)
                value
                ('$this->email','$this->name','$this->password');";
        $SQLOJ_USER = new DB('localhost', 'root', 'SQLoj2021.', 'SQLOJ_USER');
        //收集错误
        if ($this->error |= (bool)(strlen($SQLOJ_USER->ErrorC())) * 16) {
            unset($SQLOJ_USER);
            return false;
        }
        //收集错误
        if (!$SQLOJ_USER->Update($sql)) {
            $this->error |= 16;
            unset($SQLOJ_USER);
            return false;
        }
        unset($SQLOJ_USER);
        return true;
    }

    //将用户信息存入出题用户数据库 成功返回true
    public function Insert_vip() {
        //先更新组织表里的人数 失败直接返回false
        $sql = "update ORGANIZATION set member=member+1;";
        $SQLOJ_USER = new DB('localhost', 'root', 'SQLoj2021.', 'SQLOJ_USER');
        //收集错误
        if ($this->error |= (bool)(strlen($SQLOJ_USER->ErrorC())) * 32) {
            unset($SQLOJ_USER);
            return false;
        }
        //收集错误
        if (!$SQLOJ_USER->Update($sql)) {
            $this->error |= 32;
            unset($SQLOJ_USER);
            return false;
        }
        $sql = "insert into ROOT(USER_EMAIL_,USER_NAME_,USER_PASSWORD_,invitation,organization)
                value
                ('$this->email','$this->name','$this->password','$this->invitation','$this->organization');";
        
        //收集错误
        if (!$SQLOJ_USER->Update($sql)) {
            $this->error |= 32;
            unset($SQLOJ_USER);
            return false;
        }
        unset($SQLOJ_USER);
        return true;
    }

    //检查邀请码是否合法 bool 若合法 更新$this->organization
    public function CheckInvitation() {
        $sql = "select * from ORGANIZATION where invitation = '$this->invitation';";
        $SQLOJ_USER = new DB('localhost', 'root', 'SQLoj2021.', 'SQLOJ_USER');
        //收集错误
        if ($this->error |= (bool)(strlen($SQLOJ_USER->ErrorC())) * 64) {
            unset($SQLOJ_USER);
            return false;
        }
        //检查记录
        $res = $SQLOJ_USER->SelectAssoc($sql);
        //收集错误
        if ($this->error |= (bool)(strlen($SQLOJ_USER->Error())) * 64) {
            unset($SQLOJ_USER);
            return false;
        }
        if ($res == NULL || count($res) == 0) {
            unset($SQLOJ_USER);
            return false;
        }
        $this->organization = $res[0]['name'];
        unset($SQLOJ_USER);
        return true;
    }

    //普通用户注册
    public function Registration_normal() {
        //注销登录状态
        $_SESSION['login'] = false;
        //准备数据
        $info = array();
        $info['registration'] = false;//是否注册成功
        $info['email'] = false;//注册邮箱是否合格
        $info['name'] = false;//注册用户名是否合格
        $info['username'] = null;//用户名
        $info['useremail'] = null;//用户邮箱
        $info['error'] = $this->error;//是否有发生错误(内部错误)
        $info['root'] = false;//是否是出题用户
        
        //检验信息
        $info['email'] = $this->UniqueEmail_normal();
        $info['name'] = $this->UniqueName_normal();
        //查重无误 写入数据库
        if ($info['email'] && $info['name']) {
            $this->Insert_normal();
        }
        //读取错误代码
        $info['error'] = $this->error;
        //检查信息以及系统执行 都没有出现问题 那么注册是成功的
        if (!$info['error'] && $info['email'] && $info['name']) {
            $info['registration'] = true;
            $info['username'] = $this->name;
            $info['useremail'] = $this->email;
            //更新session
            $_SESSION['login'] = true;//登录状态
            $_SESSION['username'] = $this->name;//用户名
            $_SESSION['email'] = $this->email;//邮箱
            $_SESSION['root'] = false;//不是出题用户
        }
        //返回json
        return json_encode($info);
    }

    //vip用户注册
    public function Registration_vip() {
        //准备数据
        $info = array();
            //基础数据
        $info['registration'] = false;//是否注册成功
        $info['email'] = false;//注册邮箱是否合格
        $info['name'] = false;//注册用户名是否合格
        $info['username'] = null;//用户名
        $info['useremail'] = null;//用户邮箱
        $info['error'] = $this->error;//是否有发生错误(内部错误)
            //出题用户特有数据
        $info['invitation'] = false;//邀请码是否有效
        $info['root'] = false;//是否是出题用户
        
        //检查邀请码 无误后继续进行
        $info['invitation'] = $this->CheckInvitation();
        //邀请码检查通过后 继续检验信息
        if ($info['invitation']) {
            //不能和普通用户重复，也不能和出题用户重复
            $info['email'] = (bool)((bool)$this->UniqueEmail_normal() & (bool)$this->UniqueEmail_vip());
            $info['name'] = (bool)((bool)$this->UniqueName_normal() & (bool)$this->UniqueName_vip());
            //符合要求 写入数据库
            if ($info['email'] && $info['name']) {
                $this->Insert_vip();
            }
            //收集错误信息
            $info['error'] = $this->error;
            //注册成功
            if (!$info['error'] && $info['email'] && $info['name']) {
                $info['registration'] = true;
                $info['username'] = $this->name;
                $info['useremail'] = $this->email;
                $info['root'] = true;//是否是出题用户
                //更新session
                $_SESSION['login'] = true;//登录状态
                $_SESSION['username'] = $this->name;//用户名
                $_SESSION['email'] = $this->email;//邮箱
                $_SESSION['root'] = true;//是出题用户
            }
        }
        //返回json
        return json_encode($info);
    }

    //用户注册 返回json
    public function Registration($invitation = NULL) {
        $this->invitation = $invitation;
        if ($this->invitation == NULL || strlen($this->invitation) == 0) {
            return $this->Registration_normal();
        }
        return $this->Registration_vip();
    }

    //返回用户个人页面信息 json
    public function PersonalInfo() {
        $email = $_POST['useremail'];
        $obj = array();
        //连接数据库
        $SQLOJ_USER = new DB('localhost', 'root', 'SQLoj2021.', 'SQLOJ_USER');
        //-------------------【获取我的提交】--------------------------
        //删除过期做题记录(30天)
        $sql =  "   delete
                    from HISTORY
                    where USER_EMAIL_ = '$email' and datetime <= date(date_sub(now(), interval 30 day));
                ";
        $SQLOJ_USER->Update($sql);
        //获得有效数据
        $sql = "select * from HISTORY where USER_EMAIL_='$email' order by id desc limit 10;";
        $arr_history = $SQLOJ_USER->SelectAssoc($sql);
        //--------------------【获取做题情况】----------------------------------------
        //--------------------【获取我的班级】----------------------------------------
        //--------------------【获取我的竞赛/作业】----------------------------------------
        //--------------------【获取我的收藏】----------------------------------------
        //打包
        $obj['history'] = $arr_history;
        //返回
        unset($SQLOJ_USER);
        return json_encode($obj);
    }

    //加入班级 返回json
    public function JoinClass($classname, $invitation) {
        //准备数据
        $arr = array();
        $arr['success'] = false;
        $arr['checkclassname'] = false;
        $arr['checkinvitation'] = false;
        $arr['already'] = false;//是否已经在班级中
        //连接数据库
        $SQLOJ_USER = new DB('localhost', 'root', 'SQLoj2021.', 'SQLOJ_USER');
        //查看班级是否存在
        $sql = "select * from CLASS_OVERVIEW where classname = '$classname';";
        $res = $SQLOJ_USER->SelectAssoc($sql);
        if (count($res) == 0) {
            unset($SQLOJ_USER);
            return json_encode($arr);
        }
        $arr['checkclassname'] = true;
        //查看邀请码是否正确
        $sql = "select * from CLASS_OVERVIEW where classname = '$classname' and invitation = '$invitation';";
        $res = $SQLOJ_USER->SelectAssoc($sql);
        if (count($res) == 0) {
            unset($SQLOJ_USER);
            return json_encode($arr);
        }
        $arr['checkinvitation'] = true;
        //检查是否已经在班级中
        $username = $_SESSION['username'];
        $useremail = $_SESSION['email'];
        $sql = "select * from CLASS_MEMBER where email = '$useremail';";
        $res = $SQLOJ_USER->SelectAssoc($sql);
        if (count($res) != 0) {
            unset($SQLOJ_USER);
            $arr['already'] = true;
            return json_encode($arr);
        }
        //加入班级
        $sql = "    insert
                    into CLASS_MEMBER
                    value
                    ('$useremail', '$username', '$classname');";
        if (!$SQLOJ_USER->Update($sql)) {
            unset($SQLOJ_USER);
            return json_encode($arr);
        }
        $arr['success'] = true;
        unset($SQLOJ_USER);
        return json_encode($arr);
    }
}