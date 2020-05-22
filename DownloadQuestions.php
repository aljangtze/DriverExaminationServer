<?php
require_once('include/load.php');
$result = array("result" => -1, 'status' => "Failed", "data" => "");

$content = file_get_contents("php://input");
$dataInfo = json_decode($content, false);

if (!$dataInfo) {
    $result['data'] = "no data info";
    echo json_encode($result);
    return;
}

if(!property_exists($dataInfo, 'user_id'))
{
    $result['data'] = "no data info";
    echo json_encode($result);
    return;
}

$user_id = $dataInfo->user_id;

$ret = getAnswerQuestions($user_id);

$result['data'] = $ret;
$result['result'] = 1;
$result['status'] = "Success";
echo json_encode($result);