<?php
/**************************************
 *
 *      用户登录状态检测
 *
 ***************************************/
include_once "classUser.php";
$user = new User();
exit($user->LoginStatus());