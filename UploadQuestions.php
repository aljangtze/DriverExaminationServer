<?php
require_once('include/load.php');
$result = array("result" => -1, 'status' => "Failed", "data" => "");
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