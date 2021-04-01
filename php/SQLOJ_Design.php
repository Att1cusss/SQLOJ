<?php
include_once 'classSubmitQustion.php';
$Json =$_POST['json'];
// $answerKey=$_POST['answerKey'];
// $answerSql=$_POST['answerSql'];
// $case_count=$_POST['case_count'];
// $questionDescription=$_POST['questionDescription'];
// $questionDifficulty=$_POST['questionDifficulty'];
// $questionName=$_POST['questionName'];
// $questionStatus=$_POST['questionStatus'];
// $questionType=$_POST['questionType'];
// $table_count=$_POST['table_count'];
// $tables=$_POST['tables'];
// $userID=$_POST['userID'];
$submit= new SubmitQustion($Json);
$back=array('status'=>$submit->run(),'error'=>$submit->error);

exit(json_encode($back));
// echo $Json;