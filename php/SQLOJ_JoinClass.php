<?php
include_once "classDB.php";
include_once "classUser.php";
$classname = $_POST['classname'];
$invitation = $_POST['invitation'];
$user = new User();
exit($user->JoinClass($classname, $invitation));