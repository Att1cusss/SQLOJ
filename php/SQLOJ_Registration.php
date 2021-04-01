<?php
/**************************************
 * 
 *      用户注册
 *      接收前端邮箱(email)用户名(username)和密码(password)
 *      返回用户注册状态json('registration' => (bool), 'email' => (bool), 'name' => (bool), 'username' = (string), 'error' = (bool))
 *      json中'registration'为true表示注册成功,否则,失败原因由'email'或者'name'指出(如果重复返回false)
 *      如果注册成功 可以从'username'中获取用户名(string)
 *      'error'(bool)为真表示php执行错误和用户无关
 * 
 *      建议检查顺序:
 *      'error' --> 'registration' --> 'email' --> 'name' --> 'username'
 * 
 ***************************************/
//包含文件
include_once 'classDB.php';
include_once "classUser.php";
//开始会话
session_start();
$email = $_POST['email'];
$username = $_POST['username'];
$password = $_POST['password'];
$invitation = $_POST['invitation'];//接收的邀请码
$User = new User($email, $password, $username, $invitation);
if ($invitation != NULL && strlen($invitation) != 0) {
    exit($User->Registration_vip());
}
exit($User->Registration_normal());