<?php

/*************************************
 * 
 *      接收用户id(email),用户sql、题目ID
 *      进行判题
 *      返回判题信息
 * 
 * 
 ***************************************/
include_once "classFile.php";
include_once "classDB.php";
include_once "classProblem.php";
//接收
$sql = $_POST['sql'];
$userid = $_POST['userid'];
$problemid = $_POST['problemid'];

session_start();
$email = $_SESSION['email'];
$name = $_SESSION['username'];

//判题
$demo = new Problem($email, $name, $problemid, $sql);
$demo->judgeAuto();
//总结信息
$arr['accept'] = $demo->isCorrect(); //是否ac本题
$arr['time'] = $demo->takeTime(3); //总用时 参数是想保留的小数点后位数 单位(s)
$arr['pass'] = $demo->acNumber(); //正确样例数
$arr['total'] = $demo->totNumber(); //总样例数
//返回信息
exit(json_encode($arr));
