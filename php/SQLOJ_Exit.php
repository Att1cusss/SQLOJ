<?php
/**************************************
 * 
 *      用户登出
 *       
 ***************************************/
include_once "classUser.php";
$user = new User();
exit($user->exit());