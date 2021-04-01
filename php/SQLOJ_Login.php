<?php
/**************************************
 * 
 *      用户登录
 * 
 ***************************************/
include_once 'classDB.php';
include_once "classUser.php";
session_start();
$email = $_POST['email'];
$password = $_POST['password'];

$User = new User($email, $password);
exit($User->Login());
