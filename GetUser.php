<?php
require_once('include/load.php');
$result = array("result" => -1, 'status' => "Failed", "data" => "");

if (isset($_GET["id"])) {
    //传入id后就返回某个id的列表信息
    $user_id = $_GET["id"];

    $ret = getUserlist($user_id);

    if($ret)
    {
        $result['data'] = $ret;
        $result['result'] = 1;
        $result['status'] = "Success";
        echo json_encode($result);
    }
    else
    {
        echo json_encode($result);
    }
} else {
    echo json_encode($result);
}


