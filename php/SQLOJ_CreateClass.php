<?php
include_once "classDB.php";
include_once "classCLS.php";
session_start();
$useremail = $_SESSION['email'];
$username = $_SESSION['username'];
//POST
$classname = $_POST['classname'];
$invitation = $_POST['invitation'];

$class = new CLS($useremail, $username, $classname, $invitation);
exit($class->CreateClass());