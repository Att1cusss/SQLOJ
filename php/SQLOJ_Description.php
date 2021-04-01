<?php

/**********************************************
 * 
 *      接收题目id 返回题目描述和题解
 * 
 * 
 **********************************************/
include_once "classDB.php";
//获得题目ID
$problemID = $_POST['problemID'];
//连接数据库
$SQLOJ_QUESTION = new DB('localhost', 'question_root', 'Questionroot1.', 'SQLOJ_QUESTION');
//获取文件链接
$sql = "select descriptionFile as des, explanationFile as exp , ProblemName as name from Overview where problemID=$problemID;";
$url_arr = $SQLOJ_QUESTION->SelectAssoc($sql);
//读取文件并打包
$arr = array();
//如果没有文件链接或不唯一 返回错误
if (!isset($url_arr) || count($url_arr) != 1) {
    $arr['error'] = true;
} else {
    $arr['error'] = false;
    $file = new File($url_arr[0]['des']);
    $arr['description'] = $file->ReadTxt();
    $file->Redirect($url_arr[0]['exp']);
    $arr['explanation'] = $file->ReadTxt();
    unset($file);
    $arr['problemName'] = $url_arr[0]['name'];
}
// 返回json
exit(json_encode($arr));
