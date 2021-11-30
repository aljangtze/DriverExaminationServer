<?php
require_once('include/load.php');
//添加
//修改
//删除
$result = array("result" => -1, 'status' => "Failed", "data" => "");

$content = file_get_contents("php://input");
$dataInfo = json_decode($content, true);
//if (isset($_GET["type"])) {
//传入id后就返回某个id的列表信息
//$type = $_GET["type"];
//echo "hello";

$ret = addUser($dataInfo);

if ($ret == true) {
    $result['result'] = 1;
    $result['status'] = "Success";
    $result['data'] = $ret;
    echo json_encode($result);
} else {
    $result['data'] = $ret;
    echo json_encode($result);
}
return;