<?php
/**********************************************************
 * 
 *      返回个人页面信息
 *      
 ************************************************************/
include_once "classDB.php";
include_once "classUser.php";
$email = $_POST['useremail'];
$user = new User($eamil);
exit($user->PersonalInfo());