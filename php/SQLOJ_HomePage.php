<?php
/****************************************
 * 
 * 显示题目数据及通过情况
 *  
 *****************************************/
include_once "classFile.php";
include_once "classDB.php";
include_once "classBS.php";

//获取邮箱
session_start();
$email = $_SESSION['email'];
//连接数据库
$SQLOJ_QUESTION = new DB('localhost', 'question_root', 'Questionroot1.', 'SQLOJ_QUESTION');
$SQLOJ_USER = new DB('localhost', 'root', 'SQLoj2021.', 'SQLOJ_USER');
//获取Overview关联数组
$sql = "select * from Overview;";
$overview = $SQLOJ_QUESTION->SelectAssoc($sql);
//获取用户BitSet数组
$sql = "select * from RECORD where USER_EMAIL_='$email';";
$arr_bs = $SQLOJ_USER->SelectAssoc($sql);
//遍历题目、形成对象数组
$tot = count($overview);
$arr = array();
for($i = 0; $i < $tot; $i++) {
    //准备数据
    $temp = array();
        //题目id
    $id = (int)$overview[$i]['problemID'];
        $bs = new BS();
        $bsid = $id % 60 == 0 ? 60 : $id % 60;
        $bsno = (int)($id / 60) + (bool)($id % 60);
        for($j = 0; $j < count($arr_bs); $j++) {
            if ((int)$arr_bs[$j]['BSNO'] == $bsno) {
                $bs = new BS((int)$arr_bs[$j]['BS']);
                break;
            }
        }
        //是否通过
    $ispassed = (bool)$bs->GetBit($bsid);
        //题目名称
    $problemname = $overview[$i]['problemName'];
        //通过率
    $passed = round((int)$overview[$i]['acceptTime'] / (int)$overview[$i]['submitTime'] * 100, 1);
        //难度0/1/2
    $difficulty = (int)$overview[$i]['problemDifficulty'];
    //写入对象
    $temp['ispassed'] = $ispassed;
    $temp['id'] = $id;
    $temp['problemname'] = $problemname;
    $temp['passed'] = $passed;
    $temp['difficulty'] = $difficulty;
    //加入数组
    $arr[$i] = $temp;
}
//返回json
$res = array();
$res['problemlist'] = $arr;
exit(json_encode($res));