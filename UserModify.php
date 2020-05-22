<?php
require_once('include/load.php');
//添加
//修改
//删除
$result = array("result" => -1, 'status' => "Failed", "data" => "");

$content = file_get_contents("php://input");
$userInfo = json_decode($content, true);
//if (isset($_GET["type"])) {
//传入id后就返回某个id的列表信息
//$type = $_GET["type"];
//echo "hello";

$ret = modifyUser($userInfo);

if ($ret == true) {
    $result['result'] = 1;
    $result['status'] = "Success";
    $result['data'] = $ret;
    echo json_encode($result);
} else {
    echo json_encode($result);
}
return;
/*
if(!$session->isUserLoggedIn(true))
{
    echo json_encode($result);
    return;
}*/
//验证token

$content = file_get_contents("php://input");
$dataInfo = json_decode($content, true);

if (!$dataInfo) {
    $result['data'] = "no data info";
    echo json_encode($result);
    return;
}

if (count($dataInfo) <= 0) {
    $result['data'] = "no data info";
    echo json_encode($result);
    return;
}

$ret = uploadAnswerQuestions($dataInfo);

if ($ret['result']) {
    $result['result'] = 1;
    $result['status'] = "Success";
    $result['data'] = $ret;
    echo json_encode($result);
    return;
} else {
    $result['data'] = $ret;
    echo json_encode($result);
}