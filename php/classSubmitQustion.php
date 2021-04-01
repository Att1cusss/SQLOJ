<?php
include_once 'classFile.php';
include_once 'classDB.php';
/*

class SubmitQustion ( 提交问题类 )将会封装常用的方法及流程。

整个问题提交过程分为三个部分
--检查数据是否存在
--测试数据是否能够成功运行，如果能够成功运行，顺便生成正确答案和final样例
--插入数据

————函数功能如下：
__construct( $json )                        |   构造函数，将json信息，转化为array
__destruct()                                |   析构函数
checkSubmitQustionArray()                   |   输入数组，测试数组是否符合数据表处理
tryConnectTestDB()                          |   尝试连接TestDB数据库
error( $num, $msg )                         |   传入错误编码和消息，合成错误
errorHandling()                             |   错误回滚函数，当测试和数据库相关的函数发生错误的时候，借助此函数清除test DB中所有的表
testAndconstruct()                          |   测试函数，边测试每个table的create、drop语句及其insert语句，测试answerSql语句，生成数组，最后数组形成json字符串
*/

class SubmitQustion {

    //Json文件里的属性：
    //-------------------------------------------------------------------------------------------------
    //出题用户ID
    public $userID = null;
    //问题名称
    public $Name = null;
    //问题类型 0-查询，1-更新
    public $Type = null;
    //问题状态 默认为不公开状态0，公开状态为1，竞赛状态为2，作业状态为3，其他异常状态为4
    public $Status = null;
    //问题描述
    public $Description = null;
    //题解
    public $answerKey = null;
    //问题难度 0-简单，1-中等，2-困难
    public $Difficulty = null;
    //正确答案SQL语句
    public $answerSql = null;
    //提交的数据表数量
    public $table_count = null;
    //每个表的例子数量
    public $case_count = null;
    //提交的tables信息=>关联数组
    public $tables = null;
    //每个table信息包含'tableName', 'Note', 'createTableSql', 'dropTableSql', 'whole_case'( array )
    public $tableName = null;
    public $Note = null;
    public $createTableSql = null;
    public $dropTableSql = null;
    public $whole_case = null;
    //-------------------------------------------------------------------------------------------------

    //类中的临时变量:
    //-------------------------------------------------------------------------------------------------
    //提交的Json转化为Array
    public $subArr = null;
    //过程中出现的错误信息
    public $error = '';
    //连接SQLOJ_TESTDB
    public $testDB = null;
    //生成答案
    public $answer = null;
    //临时table信息，用于存储操作数据库时生成的信息，方便回滚操作。
    public $tableinfo = null;
    //生成final样例数组
    public $final_case = null;
    //问题的ID
    public $questionID = null;
    //File
    public $jsonFile = null;
    public $answerFile = null;
    public $descriptionFile = null;
    public $explanationFile = null;
    public $answerSqlFile = null;
    //-------------------------------------------------------------------------------------------------

    public function __construct( $Json ) {
        $this->subArr = $Json;
        $this->answer = array();
    }
    //构造函数

    public function __destruct() {
        ;
    }
    //析构函数

    public function tryConnectTestDB() {
        $this->testDB = new DB( 'localhost', 'test_submit', 'Testsubmit1.', 'SQLOJ_TESTDB' );
        if ( $this->testDB->ErrorC() != '' ) {
            return 1;
        } else {
            return 0;
        }
    }
    //连接函数TESTDB

    public function error( $num, $msg ) {
        $this->error = $this->error . "Error $num : $msg\n";
    }
    //错误函数

    public function checkSubmitQustionArray() {
        //check userID
        if ( !array_key_exists( 'userID', $this->subArr ) ) {
            $this->error( '01', 'userID not found' );
            return 1;
        }
        $this->userID = $this->subArr['userID'];
        //check questionName
        if ( !array_key_exists( 'questionName', $this->subArr ) ) {
            $this->error( '02', 'questionName not found' );
            return 2;
        }
        $this->Name = $this->subArr['questionName'];
        //check questionType
        if ( !array_key_exists( 'questionType', $this->subArr ) ) {
            $this->error( '03', 'questionType not found' );
            return 3;
        }
        $this->Type = $this->subArr['questionType'];
        //check questionStatus
        if ( !array_key_exists( 'questionStatus', $this->subArr ) ) {
            $this->error( '04', 'questionStatus not found' );
            return 4;
        }
        $this->Status = $this->subArr['questionStatus'];
        //check questionDescription
        if ( !array_key_exists( 'questionDescription', $this->subArr ) ) {
            $this->error( '05', 'questionDescription not found' );
            return 5;
        }
        $this->Description = $this->subArr['questionDescription'];
        //check answerKey
        if ( !array_key_exists( 'answerKey', $this->subArr ) ) {
            $this->error( '06', 'answerKey not found' );
            return 6;
        }
        $this->answerKey = $this->subArr['answerKey'];
        //check questionDifficulty
        if ( !array_key_exists( 'questionDifficulty', $this->subArr ) ) {
            $this->error( '07', 'questionDifficulty not found' );
            return 7;
        }
        $this->Difficulty = $this->subArr['questionDifficulty'];
        //check answerSql
        if ( !array_key_exists( 'answerSql', $this->subArr ) ) {
            $this->error( '08', 'answerSql not found' );
            return 8;
        }
        $this->answerSql = $this->subArr['answerSql'];
        //check table_count
        if ( !array_key_exists( 'table_count', $this->subArr ) ) {
            $this->error( '09', 'table_count not found' );
            return 9;
        }
        $this->table_count = $this->subArr['table_count'];
        //check case_count
        if ( !array_key_exists( 'case_count', $this->subArr ) ) {
            $this->error( '010', 'case_count not found' );
            return 10;
        }
        $this->case_count = $this->subArr['case_count'];
        //check tables
        if ( !array_key_exists( 'tables', $this->subArr ) ) {
            $this->error( '011', 'tables not found' );
            return 11;
        }
        $this->tables = $this->subArr['tables'];
        //check table_count
        if ( count( $this->tables ) != $this->table_count ) {
            $this->error( '012', "table_count $this->table_count not equal to real count ".count( $this->tables ) );
            return 12;
        }
        //check everytable in tables
        $i = 1;
        foreach ( $this->tables as $value ) {
            //check tableName
            if ( !array_key_exists( 'tableName', $value ) ) {
                $this->error( '013', "tableName not found in table No. $i" );
                return 13;
            }
            //check Note
            if ( !array_key_exists( 'Note', $value ) ) {
                $this->error( '014', "Note not found in table No. $i" );
                return 14;
            }
            //check createTableSql
            if ( !array_key_exists( 'createTableSql', $value ) ) {
                $this->error( '015', "createTableSql not found in table No. $i" );
                return 15;
            }
            //check dropTableSql
            if ( !array_key_exists( 'dropTableSql', $value ) ) {
                $this->error( '016', "dropTableSql not found in table No. $i" );
                return 16;
            }
            //check whole_case
            if ( !array_key_exists( 'whole_case', $value ) ) {
                $this->error( '017', "whole_case not found in table No. $i" );
                return 17;
            }
            //check case_count
            if ( count( $value['whole_case'] ) != $this->case_count ) {
                $this->error( '018', "case_count $this->case_count not equal to table No . $i 's real count ".count( $value['whole_case'] ) );
                return 18;
            }
            $i++;
        }
    }
    //检查函数

    public function errorHandling() {
        $result = $this->testDB->SelectAssoc( "SELECT CONCAT('drop table `',table_name,'`;') as droptableSql FROM information_schema.`TABLES` WHERE table_schema='sqloj_testdb';" );
        foreach ( $result as $key => $value ) {
            if ( !$this->testDB->Update( $value['droptableSql'] ) ) {
                $this->error( '11', 'errorHandling failed ,dangerous！' . $this->testDB->Error() );
                return false;
            }
        }
        return true;
    }
    //错误回滚函数

    public function backroll() {
        $insertDB = new DB( 'localhost', 'question_root', 'Questionroot1.', 'SQLOJ_QUESTION' );
        if ( $insertDB->ErrorC() != NULL ) {
            $this->error( '51', 'backroll Cannot connect to questionDB' );
            return 51;
        }
        foreach ( $this->tableinfo as $value ) {
            if ( array_key_exists( 'caseIDs', $value ) ) {
                foreach ( $value['caseIDs'] as $v ) {
                    if ( !$insertDB->Update( "delete from dataCase where caseID=$v" ) ) {
                        $this->error( '52', 'danger! cannot delete case' );
                        return 52;
                    }
                }
            }
            if ( array_key_exists( 'tableID', $value ) ) {
                // echo $value['tableID'];
                if ( !$insertDB->Update( 'delete from tableLibrary where tableID='.$value['tableID'] .';' ) ) {
                    $this->error( '53', 'danger! cannot delete table'. $insertDB->Error() );
                    return 53;
                }
            }
        }
    }

    public function backroll2() {
        $this->backroll();
        $insertDB = new DB( 'localhost', 'question_root', 'Questionroot1.', 'SQLOJ_QUESTION' );
        if ( $insertDB->ErrorC() != NULL ) {
            $this->error( '61', 'backroll Cannot connect to questionDB' );
            return 61;
        }
        if ( !$insertDB->Update( 'delete from finalTest where problemID='.$this->problemID.';' ) ) {
            $this->error( '62', 'danger! cannot delete finalTest' );
            //return 62;
        }
        if ( !$insertDB->Update( 'delete from Overview where problemID='.$this->problemID.';' ) ) {
            $this->error( '63', 'danger! cannot delete Overview' );
            return 63;
        }
    }

    public function testTableAndCase() {
        //建表
        $i = 1;
        foreach ( $this->tables as $value ) {
            $this->tableName = $value['tableName'];
            $this->createTableSql = $value['createTableSql'];
            if ( !$this->testDB->CreateTable( $this->createTableSql ) ) {
                $this->error( '21', 'createTable failed at No. ' . $i .$this->testDB->Error() );
                $this->errorHandling();
                return 21;
            }
            $i++;
        }
        //遍历每个样例
        for ( $j = 0; $j<$this->case_count; $j++ ) {
            //插入数据
            for ( $i = 0; $i<$this->table_count; $i++ ) {
                if ( !$this->testDB->Update( $this->tables[$i]['whole_case'][$j] ) ) {
                    $j++;
                    $i++;
                    $this->error( '22', "insert failed on table No. $j on case No. $i" . $this->testDB->Error() );
                    return 22;
                }
            }
            //根据题目类型生成答案
            if ( $this->Type == 0 ) {
                //查询类
                //执行Select answerSql 语句
                $result = $this->testDB->Select( $this->answerSql );
                if ( $result == NULL ) {
                    $this->error( '23', 'select answerSql wrong !'.$this->testDB->Error() );
                    return 23;
                }
                //补充答案数组
                array_push( $this->answer, $result );
            } elseif ( $this->Type == 1 ) {
                //更新类
                //执行Update answerSql语句
                if ( !$this->testDB->Update( $this->answerSql ) ) {
                    $this->error( '24', 'update answerSql wrong !'.$this->testDB->Error() );
                    return 24;
                }
                //执行selectAll语句，合并成答案
                $tempresult = '';
                for ( $i = 0; $i<$this->table_count; $i++ ) {
                    $tablename = $this->tables[$i]['tableName'];
                    $selectsql = "select * from `$tablename`;";
                    $tempresult = $tempresult . $this->testDB->Select( $selectsql );
                    if ( $this->testDB->Error() != '' ) {
                        $this->error( '25', 'select answerSql wrong ! please check the tablename!'.$this->testDB->Error() );
                        return 25;
                    }
                }
                array_push( $this->answer, $tempresult );
            } else {
                $this->error( '26', 'questiontype Wrong!' );
                return 26;
            }
            //清除数据
            for ( $i = 0; $i<$this->table_count; $i++ ) {
                $tablename = $this->tables[$i]['tableName'];
                $deletesql = "truncate table `$tablename`;";
                if ( $this->testDB->Update( $deletesql ) );
                if ( $this->testDB->Error() != '' ) {
                    $this->error( '27', 'delete data wrong ! please check the tablename!'.$this->testDB->Error() );
                    return 27;
                }
            }
        }
        //清空表
        $i = 1;
        foreach ( $this->tables as $value ) {
            $this->tablename = $value['tableName'];
            $this->dropTableSql = $value['dropTableSql'];
            if ( !$this->testDB->Update( $this->dropTableSql ) ) {
                $this->error( '28', 'droptable failed at No. ' . $i );
                return 28;
            }
            $i++;
        }
    }
    //测试函数

    public function insertTemp( $sql, $connectDB ) {
        if ( $connectDB->Update( $sql ) ) {
            return $connectDB->SelectAssoc( 'select last_insert_id();' )[0]['last_insert_id()'];
        } else {
            $this->error("??",$connectDB->Error());
            return -1;
        }
    }

    public function Addone( $connectDB, $tableID ) {
        if ( $connectDB->Update( "update tableLibrary set caseNumber = caseNumber + 1 where tableID = $tableID;" ) ) {
            return 0;
        } else {
            return 1;
        }
    }

    public function insertDB() {
        //将之前确定测试成功的表和测试成功的样例插入数据库
        //连接数据库
        $insertDB = new DB( 'localhost', 'question_root', 'Questionroot1.', 'SQLOJ_QUESTION' );
        if ( $insertDB->ErrorC() != NULL ) {
            $this->error( '31', 'Cannot connect to questionDB' );
            return 31;
        }
        $this->tableinfo = array();
        //对每个表和每个表的样例进行插入和记录
        foreach ( $this->tables as $value ) {
            $this->tableName = $value['tableName'];
            $this->Note = $value['Note'];
            $this->createTableSql = $value['createTableSql'];
            $this->dropTableSql = $value['dropTableSql'];
            $this->whole_case = $value['whole_case'];
            $autoID = 'temp';
            $temp1 = null;
            $createFile = "/var/www/PROBLEM/create/$autoID.txt";
            $dropFile = "/var/www/PROBLEM/drop/$autoID.txt";
            $insertsql =  "insert into tableLibrary(tableName,Note,createFile,dropFile) values('$this->tableName','$this->Note','$createFile','$dropFile');" ;
            //插入临时数据表数据
            if ( $insertDB->Update( $insertsql ) ) {
                //获得插入数据表的autoID
                $autoID = (int)$insertDB->SelectAssoc( 'select last_insert_id();' )[0]['last_insert_id()'];
                $createFile = "/var/www/PROBLEM/create/$autoID.txt";
                $dropFile = "/var/www/PROBLEM/drop/$autoID.txt";
                $temp1 = array( 'tableID'=>$autoID, 'createFile'=>$createFile, 'dropFile'=>$dropFile );
                //更新获得的autoID表的信息
                if ( $insertDB->Update( "update tableLibrary set createFile='$createFile',dropFile='$dropFile' where tableID=$autoID;" ) ) {
                    //写入文件包括建表和删表文件
                    $fileconnect1 = new File( $createFile );
                    if ( !$fileconnect1->WriteTxt( $this->createTableSql ) ) {
                        array_push( $this->tableinfo, $temp1 );
                        $this->backroll();
                        $this->error( '32', 'write createtableSql failed' );
                        return 32;
                    }
                    $fileconnect2 = new File( $dropFile );
                    if ( !$fileconnect2->WriteTxt( $this->dropTableSql ) ) {
                        array_push( $this->tableinfo, $temp1 );
                        $this->backroll();
                        $this->error( '33', 'write droptableSql failed' );
                        return 33;
                    }
                } else {
                    array_push( $this->tableinfo, $temp1 );
                    $this->backroll();
                    $this->error( '34', 'update temp question failed' );
                    return 34;
                }
            } else {
                //array_push( $this->tableinfo, $temp );
                $this->backroll();
                $this->error( '35', 'insert temp question failed' );
                return 35;
            }
            $temp2 = array();
            //插入表的每个样例
            foreach ( $value['whole_case'] as $v ) {
                $sql = "insert into dataCase(tableID,insertFile) values ($autoID,'/var/www/PROBLEM/insert/temp.txt');";
                //插入样例case，并获取ID->temp
                $temp = (int)$this->insertTemp( $sql, $insertDB );
                if ( $temp == -1 ) {
                    array_push( $temp1, $temp2 );
                    array_push( $this->tableinfo, $temp1 );
                    $this->error( '36', 'insert temp case failed' );
                    $this->backroll();
                    return 36;
                }
                //将获取的temp ID放入temp2
                array_push( $temp2, $temp );
                //更新tempID对应的数据
                if ( !$insertDB->Update( "update dataCase set insertFile = '/var/www/PROBLEM/insert/$temp.txt' where caseID = $temp" ) ) {
                    array_push( $temp1, $temp2 );
                    array_push( $this->tableinfo, $temp1 );
                    $this->error( '37', 'update temp case failed' );
                    $this->backroll();
                    return 37;
                }
                //写入文件
                $fileconnect1 = new File( '/var/www/PROBLEM/insert/'.$temp.'.txt' );
                if ( !$fileconnect1->WriteTxt( $v ) ) {
                    array_push( $temp1, $temp2 );
                    array_push( $this->tableinfo, $temp1 );
                    $this->backroll();
                    $this->error( '38', 'write insert case failed' );
                    return 38;
                }
                //样例数目+1
                if ( $this->Addone( $insertDB, $autoID ) ) {
                    array_push( $temp1, $temp2 );
                    array_push( $this->tableinfo, $temp1 );
                    $this->backroll();
                    $this->error( '39', 'caseNumber add one failed' );
                    return 39;
                }
            }
            //将该表的信息放进tableinfo
            // array_push( $temp1, 'caseIDs'=>$temp2 );
            $temp1['caseIDs'] = $temp2;
            array_push( $this->tableinfo, $temp1 );
        }
        //生成caseJson
        $this->final_case = array( 'table_number'=>$this->table_count, 'case_number'=>$this->case_count );
        $tempcase = array();
        for ( $i = 0; $i<$this->case_count; $i++ ) {
            $temp1 = array();
            for ( $j = 0; $j<$this->table_count; $j++ ) {
                $temp2 = array( 'tableID'=>$this->tableinfo[$j]['tableID'], 'caseID'=>$this->tableinfo[$j]['caseIDs'][$i] );
                array_push( $temp1, $temp2 );
            }
            array_push( $tempcase, $temp1 );
        }
        // array_push( $this->final_case, 'case'=>$tempcase );
        $this->final_case['case'] = $tempcase;
        //插入问题数据库
        $sql = "insert into Overview (problemName,problemType,problemStatus,descriptionFile,explanationFile,problemDifficulty) values ('$this->Name',$this->Type,$this->Status,'temp','temp',$this->Difficulty);";
        //获得问题ID
        $this->problemID = $this->insertTemp( $sql, $insertDB );
        if ( $this->problemID == -1 ) {
            $this->backroll();
            $this->error( '41', 'get AutoID failed' );
            return 41;
        }
        $this->descriptionFile = "/var/www/PROBLEM/description/$this->problemID.txt";
        $this->explanationFile = "/var/www/PROBLEM/explanation/$this->problemID.txt";
        $this->jsonFile = "/var/www/PROBLEM/finalJson/$this->problemID.json";
        $this->answerSqlFile = "/var/www/PROBLEM/sql/$this->problemID.txt";
        $this->answerFile = "/var/www/PROBLEM/finalAnswer/$this->problemID.json";
        if ( !$insertDB->Update( "update Overview set descriptionFile = '$this->descriptionFile',explanationFile='$this->explanationFile' where problemID=$this->problemID ;" ) ) {
            $this->error( '42', 'update Overview fail' );
            $this->backroll2();
            return 42;
        }
        $file = new File( $this->descriptionFile );
        if ( !$file->WriteTxt( $this->Description ) ) {
            $this->error( '43', 'write description failed' );
            $this->backroll2();
            return 43;
        }
        unset( $file );
        $file = new File( $this->explanationFile );
        if ( !$file->WriteTxt( $this->answerKey ) ) {
            $this->error( '44', 'write answerKey failed' );
            $this->backroll2();
            return 44;
        }
        unset( $file );
        if ( !$insertDB->Update( "insert into finalTest(problemID,jsonFile,answerSqlFile,answerFile) values ($this->problemID,'$this->jsonFile','$this->answerSqlFile','$this->answerFile');" ) ) {
            $this->error( '45', 'update Overview failed' );
            $this->backroll2();
            return 45;
        }
        $file = new File( $this->jsonFile );
        if ( !$file->WriteJson( $this->final_case  ) ) {
            $this->error( '46', 'write caseJson failed' );
            $this->backroll2();
            return 46;
        }
        unset( $file );
        $file = new File( $this->answerSqlFile );
        if ( !$file->WriteTxt( $this->answerSql ) ) {
            $this->error( '47', 'write answerSql failed' );
            $this->backroll2();
            return 47;
        }
        unset( $file );
        $file = new File( $this->answerFile );
        if ( !$file->WriteJson( array('answer'=>$this->answer)  ) ) {
            $this->error( '48', 'write answer failed' );
            $this->backroll2();
            return 48;
        }
        unset( $file );
    }

    public function run() {
        if ( $this->checkSubmitQustionArray() != 0 ) {
            return false;
        }
        if ( $this->tryConnectTestDB() != 0 ) {
            return false;
        }
        if ( $this->testTableAndCase() != 0 ) {
            return false;
        }
        if ( $this->insertDB() != 0 ) {
            return false;
        }
        return true;
    }
}

