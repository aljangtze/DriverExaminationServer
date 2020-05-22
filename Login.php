<?php
/**
 * Created by PhpStorm.
 * User: aljangtze
 * Date: 2020/3/26
 * Time: 15:35
 */
require_once('include/load.php');

if(isset($_GET["device_code"]))
{
    $device = $_GET["device_code"];
    $user  =  loginUseDevice($device);
    if($user) {
        //$session->login($user["id"]);
        //登录成功，传回token
        $result['result']=1;
        $result['status']="Success";
        $result['data'] = $user;
        //echo json_encode();
        echo json_encode($result);
    }
    else
    {
        $result['data']="user info is error";
        echo json_encode($result);
        return;
    }

    return;
}

$content = file_get_contents("php://input");

$result = array("result"=>-1, 'status'=>"Failed", "data"=>"");

$userInfo = json_decode($content,false);

if(!$userInfo)
{
    $result['data']="no user info";
    echo json_encode($result);
    return;
}

if(!property_exists($userInfo, 'Name') || !property_exists($userInfo, 'Password'))
{
    $result['data']="user info is null";
    echo json_encode($result);
    return;
}

$username = $userInfo->Name;
$password = $userInfo->Password;

$resultData  =  login($username, $password, $userInfo->device_id);

if($resultData['result'] == 1) {
    //$session->login($user["id"]);
    //登录成功，传回token
    $result['result']=1;
    $result['status']="Success";
    $result['data'] = $resultData['data'];
    //echo json_encode();
    echo json_encode($result);
}
else
{
    $result['data']=$resultData['info'];
    echo json_encode($result);
    return;
}
?>