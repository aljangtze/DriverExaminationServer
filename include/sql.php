<?php
require_once('include/load.php');

function login($user, $password, $device)
{
    try {
        global $db;

        $resultData = array("result"=>0, 'info'=>"", "data"=>"");

        //先查找用户
        $sql = "select id, name,type,status,device_count from users where name='{$user}' and password='{$password}' and status=1 limit 1";
        $userInfo = $db->fetch($sql);
        if (!$userInfo) {
            $resultData['info'] = "用户名或密码不正确";
            return $resultData;
        }

        //如果是管理员不记录，直接登录就好，微信登录不区分管理员
        if($userInfo['type'] == 1)
        {
            $resultData['info'] = "Success";
            $resultData['data'] = $userInfo;
            $resultData['result'] = 1;
            return $resultData;
        }

        //如果是放开的设备，也不记录，直接登录就好
        if($db->exists("select * from devices where is_super=1 and guid=:device_id", ["device_id"=>$device]))
        {
            $resultData['info'] = "Success";
            $resultData['data'] = $userInfo;
            $resultData['result'] = 1;
            return $resultData;
        }

        //检查当前设备是否已经记录
        $sql = "select * from user_device as ud
        inner join devices  as d on ud.device_id=d.id
        where d.guid ='{$device}' and ud.user_id={$userInfo["id"]}";

        $deviceInfo = $db->fetch($sql);
        if ($deviceInfo) {
            $resultData['info'] = "Success";
            $resultData['data'] = $userInfo;
            $resultData['result'] = 1;
            return $resultData;
            //return $userInfo;
        }

        //设备不存在，需要添加,检查是否还有授权号
        $sql = "select count(1) as count from user_device where user_id={$userInfo["id"]}";
        $registerCount = $db->fetch($sql);

        if ($userInfo["device_count"] > $registerCount["count"]) {
            //添加设备绑定
            bindUserDevice($userInfo["id"], $device);
            $resultData['result'] = 1;
            $resultData['info'] = "Success";
            return $resultData;
        } else {
            $resultData['info'] = "最大允许登录的设备数不足";
            return $resultData;
        }

    } catch (Exception $e) {

        $resultData['info'] = "内部异常";
        return $resultData;
    }
}

function bindUserDevice($user_id, $device_id, $type=0)
{
    try {
        global $db;

        $sql = "select * from devices where guid = '{$device_id}'";
        if(!$db->exists($sql)){
            $sql = "insert into devices (guid, type, is_super) values ('{$device_id}', 0, 0)";
            $db->query($sql);
        }

        $sql = "select * from user_device where device_id in (select id from devices where guid = '{$device_id}') and user_id={$user_id}";
        if(!$db->exists($sql)){
            $sql = "insert into user_device (user_id, device_id) select {$user_id},id from devices where guid ='{$device_id}'";
            $db->query($sql);
        }
    } catch (Exception $e) {
        return false;
    }
}

function loginUseDevice($device)
{
    try {
        global $db;

        $sql = "select * from users as u  
                    where u.id in (
                                select ud.user_id from user_device as ud 
                    inner join devices  as d on ud.device_id=d.id
                    where d.guid ='{$device}') limit 1";

        //$sql = "select user_id from user_deivce where name='{$user}' and password='{$password}' and status=1 limit 1";
        $result = $db->fetch($sql);

        $resultData = array("result"=>0, 'info'=>"", "data"=>"");

        if(!$result)
        {
            $resultData['info'] = '未找到设备';
            $resultData['result'] = 0;
        }
        else
        {
            $resultData['data'] = $result;
            $resultData['result'] = 1;
        }
        return $result;
    } catch (Exception $e) {
        $resultData['info'] = "内部异常";
        return false;
    }
}

function uploadErrorQuestionsX($data)
{
    global $db;
    $successCount = 0;
    foreach ($data as $errorQuestion) {
        if ($db->insert('answers_update', $errorQuestion) > 0)
            $successCount = $successCount + 1;
    }

    return $successCount;
}

//上传错误题目的数据，需要修改一下更新的表
function uploadAnswerQuestions($data)
{
    $result = ['result' => false, 'post' => count($data), 'update' => 0, "insert" => 0, 'error' => ''];
    global $db;
    $insertCount = 0;
    $updateCount = 0;
    $db->beginTransaction();
    try {
        foreach ($data as $errorQuestion) {
            $arr = array_slice($errorQuestion, 0, 2);
            if ($db->exists("select * from answers where question_id=:question_id and user_id=:user_id", $arr)) {
                if ($errorQuestion['answerNumber'] != $errorQuestion['rightNumber'] + $errorQuestion['errorNumber'])
                    continue;

                $params = array_slice($errorQuestion, 0, 5);
                $params['lastupdatetime'] = date('Y-m-d h:i:s', time());
                $sql = 'update answers set answerNumber=:answerNumber+answerNumber, rightNumber=:rightNumber+rightNumber,errorNumber=:errorNumber+errorNumber, lastupdatetime=:lastupdatetime where question_id=:question_id and user_id=:user_id';
                $updateCount = $updateCount + $db->query($sql, $params);
            } else {
                $arr = array_slice($errorQuestion, 0, 5);
                $arr['lastupdatetime'] = date('Y-m-d h:i:s', time());
                if ($db->insert('answers', $arr) > 0)
                    $insertCount = $insertCount + 1;
            }
        }
    } catch (Exception $e) {
        $db->rollback();
        $result['error'] = $e->getMessage();
        return $result;
    }

    $db->commit();
    $result['result'] = true;
    $result['update'] = $updateCount;
    $result['insert'] = $insertCount;
    return $result;
}


function AddAnswerQuestion($errorQuestion)
{
    $result = ['result' => false, 'post' => 1, 'update' => 0, "insert" => 0, 'error' => ''];
    global $db;
    $insertCount = 0;
    $updateCount = 0;
    $db->beginTransaction();
    try {
            $arr = array_slice($errorQuestion, 0, 2);
            if ($db->exists("select * from answers where question_id=:question_id and user_id=:user_id", $arr)) {
                if ($errorQuestion['answerNumber'] != $errorQuestion['rightNumber'] + $errorQuestion['errorNumber'])
                    return $result;

                $params = array_slice($errorQuestion, 0, 5);
                $params['lastupdatetime'] = date('Y-m-d h:i:s', time());
                $sql = 'update answers set answerNumber=:answerNumber+answerNumber, rightNumber=:rightNumber+rightNumber,errorNumber=:errorNumber+errorNumber, lastupdatetime=:lastupdatetime where question_id=:question_id and user_id=:user_id';
                $updateCount = $updateCount + $db->query($sql, $params);
            } else {
                $arr = array_slice($errorQuestion, 0, 5);
                $arr['lastupdatetime'] = date('Y-m-d h:i:s', time());
                if ($db->insert('answers', $arr) > 0)
                    $insertCount = $insertCount + 1;
        }
    } catch (Exception $e) {
        $db->rollback();
        $result['error'] = $e->getMessage();
        return $result;
    }

    $db->commit();
    $result['result'] = true;
    $result['update'] = $updateCount;
    $result['insert'] = $insertCount;
    return $result;
}

//删除服务器上的答题结果
function DeleteAnswer($user)
{
    try {
        global $db;
        $id = $user["ID"];

        $sql = 'delete from answers where user_id=:user_id';
        $result = $db->query($sql, ["user_id" => $id]);
        return true;
    } catch (Exception $e) {
        return false;
    }
}

//获取答题列表
function getAnswerQuestions($user_id)
{
    global $db;
    return $db->fetchAll("select * from answers where user_id=:user_id", ["user_id" => $user_id]);
}

//获取用户列表
function getUserlist($user_id)
{
    global $db;
    if ($user_id != "0" || $user_id != 0) {
        return $db->fetchAll("SELECT id as ID,name , password, type , case status when 1 then 'true' else 'false' end as status, real_name, school_name,learn_subject, register_date, device_count FROM users where id=:user_id", ["user_id" => $user_id]);
    } else {
        return $db->fetchAll("SELECT id as ID,name , password, type , case status when 1 then 'true' else 'false' end as status, real_name, school_name, learn_subject, register_date, device_count FROM users ");
    }

}

function addExamAnswer($ExamAnswer)
{
    try {
        global $db;
        $ExamAnswer['exam_date'] = date('Y-m-d h:i:s', time());
        //$user = array_slice($ExamAnswer, 1);
        $result = $db->insert("exam_result", $ExamAnswer);
        return $result;
    } catch (Exception $e) {
        return false;
    }
}


//添加用户
function addUser($user)
{
    try {
        global $db;
        $user = array_slice($user, 1);
        $result = $db->insert("users", $user);
        return $result;
    } catch (Exception $e) {
        return false;
    }
}

//修改用户
function modifyUser($user)
{
    try {
        global $db;
        //$sql = 'update answers set answerNumber=:answerNumber+answerNumber, rightNumber=:rightNumber+rightNumber,errorNumber=:errorNumber+errorNumber, lastupdatetime=:lastupdatetime where question_id=:question_id and user_id=:user_id';
        $id = $user["ID"];
        //$user = array_slice($user, 1);
        //$db->update('users', $user);
        $result = $db->update("users", $user, ["id" => $id]);
        return $result;
    } catch (Exception $e) {
        return false;
    }
}

function deleteUser($user)
{
    try {
        global $db;
        $id = $user["ID"];
        //$user = array_slice($user, 1);
        //$db->update('users', $user);

        $sql = 'delete from users where id=:user_id';
        $result = $db->query($sql, ["user_id" => $id]);
        return $result;
    } catch (Exception $e) {
        return false;
    }
}

function getGroups($car_type, $subject_id, $group_type, $user_id)
{
    //car_type 0 小车 1客车 2 货车 3摩托车 车辆类型
    //subject_id 0 科目一 1科目四 科目类型

    //classfication 1客车 2 货车 3 小车 4通用科目四 5 摩托车科目一 6 摩托车科目四
    //$moudle_id 用于区分分组
    //group type 0 章节 1技巧 2 强化 3 错题

    //返回json组织
    //index (1) id tittle  car_type subject_id  count
    //select index, id, name as tittle, :car_type as car_type, subject_id as subject_id, count from groups where type={moudle_id} and classification={classification} and status=1

    $classification = "";
    //科目四
    if ($subject_id == 1) {
        //如果是摩托车
        if ($car_type == 3) {
            $classification = '(6)';
        } else {
            $classification = '(4)';
        }

    } else {
        if ($car_type == 0) {
            $classification = '(3)';
        } else if ($car_type == 1) {
            $classification = '(3, 1)';
        } else if ($car_type == 2) {
            $classification = '(3, 2)';
        } else if ($car_type == 3) {
            $classification = '(5)';
        }
    }

    /*select  g.sql, g.sql_parameter, g.id,g.name, g.type, g.classification as classification_id, g.status, count(gq.question_id) as count, g.name as classification_name, gt.name as group_type from groups as g
                            left join group_questions as gq on gq.group_id=g.id and gq.type=g.type
							left join classfication as c on g.classification=c.id
							left join group_type as gt on g.type = gt.id
                                where g.type=0 group by g.id,g.name,g.type, g.classification, g.status order by g.name;
    */
    global $db;
    //$sql = "select rowid as 'index', id, name as tittle, :car_type as car_type, :subject_id as subject_id, count from groups where type=:group_id and classification in ".$classification;
    $sql = "select  g.rowid as 'index', g.id,g.name as tittle,  
            :car_type as car_type, :subject_id as subject_id, g.classification,g.vip,
            count(gq.question_id) as count,sql, sql_parameter
            from groups as g 
            left join group_questions as gq on gq.group_id=g.id and gq.type=g.type
                        left join classfication as c on g.classification=c.id
                        left join group_type as gt on g.type = gt.id
            where g.type=:group_type and classification in" . $classification . "
            group by g.id,g.name,g.type, g.classification, g.status order by g.name";
    $parameter = ['car_type' => $car_type, 'subject_id' => $subject_id, 'group_type' => $group_type];

    $data = $db->fetchAll($sql, $parameter);


    $returnData = Array();
    if($group_type == 3) {
        foreach ($data as $row) {

            $sqlInfo = $row['sql'];

            $sqlInfo = str_replace('{0}', $user_id, $sqlInfo);
            $sqlInfo = str_replace('{1}', $row['classification'], $sqlInfo);
            $sqlInfo = str_replace('{2}', $row['sql_parameter'], $sqlInfo);
            $sqlInfo = str_replace(';', '', $sqlInfo);

            $sqlQuery = 'select count(1) as count from (' . $sqlInfo . ')';

            $countInfo = $db->fetch($sqlQuery);

            $row['count'] = $countInfo['count'];
            $row['sql'] = '';

            array_push($returnData, $row);
        }
    }
    else
    {
        $returnData = $data;
    }

    return $returnData;
}


function getGroupQuestions($group_id, $user_id)
{
    global $db;

    $sql = "select * from groups where id=:group_id";
    $groupInfo = $db->fetch($sql, ["group_id" => $group_id]);

    if($groupInfo['type'] == 3) {

        $sqlInfo = $groupInfo['sql'];

        $sqlInfo = str_replace('{0}', $user_id, $sqlInfo);
        $sqlInfo = str_replace('{1}', $groupInfo['classification'], $sqlInfo);
        $sqlInfo = str_replace('{2}', $groupInfo['sql_parameter'], $sqlInfo);
        $sqlInfo = str_replace(';', '', $sqlInfo);

        $sqlQuery = 'select q.id, q.tittle, q.type, q.option1, q.option2, q.option3, q.option4, q.answer1, q.answer2, q.answer3, q.answer4, 
            q.tittleEmphasize, q.skillEmphasize, q.notice, q.option1Emphasize, q.option2Emphasize, q.option3Emphasize, q.option4Emphasize,
            case when image is null then \'\' else id||\'.jpg\' end as image,case when flash is null then \'\' else id||\'.mp4\' end as flash 
            from questions as q inner join (' . $sqlInfo . ') as gq 
            on gq.question_id=q.id';

        return $db->fetchAll($sqlQuery);
    }

    $sql = "select q.id, q.tittle, q.type, q.option1, q.option2, q.option3, q.option4, q.answer1, q.answer2, q.answer3, q.answer4, 
            q.tittleEmphasize, q.skillEmphasize, q.notice, q.option1Emphasize, q.option2Emphasize, q.option3Emphasize, q.option4Emphasize,
            case when image is null then '' else id||'.jpg' end as image,case when flash is null then '' else id||'.mp4' end as flash 
            from questions as q  left join group_questions as gq 
            on gq.question_id=q.id where gq.group_id=:group_id order by q.id";
    return $db->fetchAll($sql, ["group_id" => $group_id]);
}

//group_ids = "1,2,3,4"
function getGroupsQuestions($group_ids, $question_type)
{
    global $db;
    $sql = "select q.id,  q.type, gq.group_id
            from questions as q  left join group_questions as gq 
            on gq.question_id=q.id where gq.group_id in (" . $group_ids . ") and q.type=:question_type order by q.id, q.type";
    return $db->fetchAll($sql, ["question_type" => $question_type]);
}

function getQuestionsByIds($question_ids)
{
    global $db;
    $sql = "select q.id, q.tittle, q.type, q.option1, q.option2, q.option3, q.option4, q.answer1, q.answer2, q.answer3, q.answer4, 
            q.tittleEmphasize, q.skillEmphasize, q.notice, q.option1Emphasize, q.option2Emphasize, q.option3Emphasize, q.option4Emphasize,
            case when image is null then '' else id||'.jpg' end as image,case when flash is null then '' else id||'.mp4' end as flash 
            from questions as q  where id in (" . $question_ids . ") order by q.id, q.type";
    $data = $db->fetchAll($sql);
    shuffle($data);
    return $data;
}

/*--------------------------------------------------------------*/
/* Function for find all database table rows by table name
/*--------------------------------------------------------------*/
function find_all($table)
{
    global $db;
    if (tableExists($table)) {
        return find_by_sql("SELECT * FROM " . $db->escape($table));
    }
}

/*--------------------------------------------------------------*/
/* Function for Perform queries
/*--------------------------------------------------------------*/
function find_by_sql($sql)
{
    global $db;
    $result = $db->query($sql);
    $result_set = $db->while_loop($result);
    return $result_set;
}

/*--------------------------------------------------------------*/
/*  Function for Find data from table by id
/*--------------------------------------------------------------*/
function find_by_id($table, $id)
{
    global $db;
    $id = (int)$id;
    if (tableExists($table)) {
        $sql = $db->query("SELECT * FROM {$db->escape($table)} WHERE id='{$db->escape($id)}' LIMIT 1");
        if ($result = $db->fetch_assoc($sql))
            return $result;
        else
            return null;
    }
}

function find_specifications_by_name($name)
{
    global $db;

    $result = $db->query("select name,specification from product where name='{$db->escape($name)}' group by name, specification");
    $result_set = $db->while_loop($result);
    return $result_set;
}


function find_model_number_by_name($name, $specification)
{
    global $db;

    $sql = "select a.* from product as a where name='{$db->escape($name)}' and specification='{$db->escape($specification)}'";
    $result = $db->query($sql);
    $result_set = $db->while_loop($result);
    return $result_set;
}

function find_rule_by_id($id)
{
    global $db;
    $id = (int)$id;

    $result = $db->query("select a.*, b.id as relation_id, case when role_id is null then 0 else 1 end as checked from rule as a left join role_rules as b on b.rule_id=a.id and b.role_id='{$db->escape($id)}' order by a.id, checked");
    $result_set = $db->while_loop($result);
    return $result_set;

    /*global $db;
    $result = $db->query($sql);*/
    //$result_set = $db->while_loop($result);
    //return $result_set;
}

function find_role_group_by_id($id)
{
    global $db;
    $id = (int)$id;

    $result = $db->query("select a.*, b.id as relation_id, case when role_id is null then 0 else 1 end as checked from role as a left join role_group_roles as b on b.role_id=a.id and b.group_id='{$db->escape($id)}'");
    $result_set = $db->while_loop($result);
    return $result_set;

    /*global $db;
    $result = $db->query($sql);*/
    //$result_set = $db->while_loop($result);
    //return $result_set;
}


/*--------------------------------------------------------------*/
/* Function for Delete data from table by id
/*--------------------------------------------------------------*/
function delete_by_id($table, $id)
{
    global $db;
    if (tableExists($table)) {
        $sql = "DELETE FROM " . $db->escape($table);
        $sql .= " WHERE id=" . $db->escape($id);
        $sql .= " LIMIT 1";
        $db->query($sql);
        return ($db->affected_rows() === 1) ? true : false;
    }
}

/*--------------------------------------------------------------*/
/* Function for Count id  By table name
/*--------------------------------------------------------------*/

function count_by_id($table)
{
    global $db;
    if (tableExists($table)) {
        $sql = "SELECT COUNT(id) AS total FROM " . $db->escape($table);
        $result = $db->query($sql);
        return ($db->fetch_assoc($result));
    }
}

/*--------------------------------------------------------------*/
/* Determine if database table exists
/*--------------------------------------------------------------*/
function tableExists($table)
{
    global $db;
    $table_exit = $db->query('SHOW TABLES FROM ' . DB_NAME . ' LIKE "' . $db->escape($table) . '"');
    if ($table_exit) {
        if ($db->num_rows($table_exit) > 0)
            return true;
        else
            return false;
    }
}

/*--------------------------------------------------------------*/
/* Login with the data provided in $_POST,
/* coming from the login form.
/*--------------------------------------------------------------*/
function authenticate($username = '', $password = '')
{
    global $db;
    $username = $db->escape($username);
    $password = $db->escape($password);
    $sql = sprintf("SELECT id,username,password,user_level FROM users WHERE username ='%s' LIMIT 1", $username);
    $result = $db->query($sql);
    if ($db->num_rows($result)) {
        $user = $db->fetch_assoc($result);
        $password_request = sha1($password);
        if ($password_request === $user['password']) {
            return $user['id'];
        }
    }
    return false;
}

/*--------------------------------------------------------------*/
/* Login with the data provided in $_POST,
/* coming from the login_v2.php form.
/* If you used this method then remove authenticate function.
/*--------------------------------------------------------------*/
function authenticate_v2($username = '', $password = '')
{
    global $db;
    $username = $db->escape($username);
    $password = $db->escape($password);
    $sql = sprintf("SELECT id,username,password,user_level FROM users WHERE username ='%s' LIMIT 1", $username);
    $result = $db->query($sql);
    if ($db->num_rows($result)) {
        $user = $db->fetch_assoc($result);
        $password_request = sha1($password);
        if ($password_request === $user['password']) {
            return $user;
        }
    }
    return false;
}


/*--------------------------------------------------------------*/
/* Find current log in user by session id
/*--------------------------------------------------------------*/
function current_user()
{
    static $current_user;
    global $db;
    if (!$current_user) {
        if (isset($_SESSION['user_id'])):
            $user_id = intval($_SESSION['user_id']);
            $current_user = find_by_id('users', $user_id);
        endif;
    }
    return $current_user;
}

/*--------------------------------------------------------------*/
/* Find all user by
/* Joining users table and user gropus table
/*--------------------------------------------------------------*/
function find_all_user()
{
    global $db;
    $results = array();
    $sql = "SELECT u.id,u.name,u.username,u.user_level,u.status,u.last_login,";
    $sql .= "g.name as group_name ";
    $sql .= "FROM users u ";
    $sql .= "LEFT JOIN role_group g ";
    $sql .= "ON g.id=u.user_level ORDER BY u.name ASC";
    $result = find_by_sql($sql);
    return $result;
}

/*--------------------------------------------------------------*/
/* Function to update the last log in of a user
/*--------------------------------------------------------------*/

function updateLastLogIn($user_id)
{
    global $db;
    $date = make_date();
    $sql = "UPDATE users SET last_login='{$date}' WHERE id ='{$user_id}' LIMIT 1";
    $result = $db->query($sql);
    return ($result && $db->affected_rows() === 1 ? true : false);
}

/*--------------------------------------------------------------*/
/* Find all Group name
/*--------------------------------------------------------------*/
function find_by_groupName($val)
{
    global $db;
    $sql = "SELECT group_name FROM user_groups WHERE group_name = '{$db->escape($val)}' LIMIT 1 ";
    $result = $db->query($sql);
    return ($db->num_rows($result) === 0 ? true : false);
}

function find_by_roleName($val)
{
    global $db;
    $sql = "SELECT id FROM role WHERE name = '{$db->escape($val)}' LIMIT 1 ";
    $dbquery = $db->query($sql);
    $result = $db->fetch_assoc($dbquery);
    if ($result)
        return $result;
    else
        return null;


    #return($db->num_rows($result) === 0 ? true : false);
}

function find_by_role_group_name($val)
{
    global $db;
    $sql = "SELECT id FROM role_group WHERE name = '{$db->escape($val)}' LIMIT 1 ";
    $dbquery = $db->query($sql);
    $result = $db->fetch_assoc($dbquery);
    if ($result)
        return $result;
    else
        return null;


    #return($db->num_rows($result) === 0 ? true : false);
}

function is_exist_by_rule_name($name)
{
    global $db;
    $sql = "SELECT id FROM rule WHERE name = '{$db->escape($name)}' LIMIT 1 ";
    $result = $db->query($sql);
    return ($db->num_rows($result) === 0 ? true : false);
}

/*--------------------------------------------------------------*/
/* Find group level
/*--------------------------------------------------------------*/
function find_by_groupLevel($level)
{
    global $db;
    $sql = "SELECT group_level FROM user_groups WHERE group_level = '{$db->escape($level)}' LIMIT 1 ";
    $result = $db->query($sql);
    return ($db->num_rows($result) === 0 ? true : false);
}

//查找用户关联的角色
function find_by_role_group($role_group)
{
    global $db;
    $sql = "SELECT id, name, memo, status  FROM role_group WHERE id = '{$db->escape($role_group)}' LIMIT 1 ";
    $result = $db->query($sql);
    $result = $db->fetch_assoc($result);
    if ($result)
        return $result;
    else
        return null;
}

//isExists
function is_exist_rule($code, $role_group)
{
    global $db;
    $sql = "select distinct ru.* from users as u
            left join role_group as rg on rg.id=u.user_level
            left join role_group_roles as rgr on rgr.group_id=rg.id
            left join role as ro on ro.id=rgr.role_id
            left join role_rules as rr on rr.role_id=ro.id
            left join rule as ru on rr.rule_id=ru.id
            where ru.id is not null and rgr.group_id={$db->escape($role_group)} and ru.code='{$db->escape($code)}'";

    $result = $db->query($sql);
    $isExists = ($db->num_rows($result) === 0 ? false : true);
    return $isExists;
}

/*--------------------------------------------------------------*/
/* Function for cheaking which user level has access to page
/*--------------------------------------------------------------*/
function page_require_levelx($require_level)
{
    global $session;
    $current_user = current_user();
    $login_level = find_by_groupLevel($current_user['user_level']);
    //if user not login
    if (!$session->isUserLoggedIn(true)):
        $session->msg('d', 'Please login...');
        redirect('index.php', false);
    //if Group status Deactive
    elseif ($login_level['group_status'] === '0'):
        $session->msg('d', 'This level user has been band!');
        redirect('home.php', false);
    //cheackin log in User level and Require level is Less than or equal to
    elseif ($current_user['user_level'] <= (int)$require_level):
        return true;
    else:
        $session->msg("d", "Sorry! you dont have permission to view the page.");
        redirect('home.php', false);
    endif;

}

function page_require_level($rule_code)
{
    global $session;
    $current_user = current_user();

    //$rule_code = "00001";
    //if user not login
    if (!$session->isUserLoggedIn(true)) {
        $session->msg('d', 'Please login...');
        redirect('index.php', false);
    } else {
        if ($rule_code == 1)
            return;

        $login_level = find_by_role_group($current_user['user_level']);

        if ($login_level == null) {
            $session->msg('d', 'This level user has been band!');
            redirect('home.php', false);
            return;
        }

        $group_id = $login_level["id"];
        //if Group status Deactive
        if ($login_level['status'] === '0'):
            $session->msg('d', 'This level user has been band!');
            redirect('home.php', false);
        //        //cheackin log in User level and Require level is Less than or equal to
        elseif (is_exist_rule($rule_code, $group_id)):
            return true;
        else:
            $session->msg("d", "Sorry! you dont have permission to view the page.");
            redirect('home.php', false);
        endif;
    }
}

function page_rule_list($user_id)
{
    global $db;
    $sql = "select distinct ru.id as rule_id, ru.code ,ru.status from users as u
            left join role_group as rg on rg.id=u.user_level
            left join role_group_roles as rgr on rgr.group_id=rg.id
            left join role as ro on ro.id=rgr.role_id
            left join role_rules as rr on rr.role_id=ro.id
            left join rule as ru on rr.rule_id=ru.id
            where ru.id is not null and u.id={$db->escape($user_id)} and ro.status=1 and ru.status=1";

    $result_set = find_by_sql($sql);
    $jarr = array();
    foreach ($result_set as $result) {
        $count = count($result);//不能在循环语句中，由于每次删除 row数组长度都减小
        for ($i = 0; $i < $count; $i++) {
            unset($result[$i]);//删除冗余数据
        }
        array_push($jarr, $result);
    }

    return $jarr;

}

/*--------------------------------------------------------------*/
/* Function for Finding all product name
/* JOIN with categorie  and media database table
/*--------------------------------------------------------------*/
function join_product_table()
{
    global $db;
    $sql = " SELECT p.id,p.name,p.quantity,p.buy_price,p.sale_price,p.media_id,p.date,c.name";
    $sql .= " AS categorie,m.file_name AS image";
    $sql .= " FROM products p";
    $sql .= " LEFT JOIN categories c ON c.id = p.categorie_id";
    $sql .= " LEFT JOIN media m ON m.id = p.media_id";
    $sql .= " ORDER BY p.id ASC";
    return find_by_sql($sql);

}

/*--------------------------------------------------------------*/
/* Function for Finding all product name
/* Request coming from ajax.php for auto suggest
/*--------------------------------------------------------------*/

function find_product_by_title($product_name)
{
    global $db;
    $p_name = remove_junk($db->escape($product_name));
    $sql = "SELECT name FROM products WHERE name like '%$p_name%' LIMIT 5";
    $result = find_by_sql($sql);
    return $result;
}

/*--------------------------------------------------------------*/
/* Function for Finding all product info by product title
/* Request coming from ajax.php
/*--------------------------------------------------------------*/
function find_all_product_info_by_title($title)
{
    global $db;
    $sql = "SELECT * FROM products ";
    $sql .= " WHERE name ='{$title}'";
    $sql .= " LIMIT 1";
    return find_by_sql($sql);
}

/*--------------------------------------------------------------*/
/* Function for Update product quantity
/*--------------------------------------------------------------*/
function update_product_qty($qty, $p_id)
{
    global $db;
    $qty = (int)$qty;
    $id = (int)$p_id;
    $sql = "UPDATE products SET quantity=quantity -'{$qty}' WHERE id = '{$id}'";
    $result = $db->query($sql);
    return ($db->affected_rows() === 1 ? true : false);

}

/*--------------------------------------------------------------*/
/* Function for Display Recent product Added
/*--------------------------------------------------------------*/
function find_recent_product_added($limit)
{
    global $db;
    $sql = " SELECT p.id,p.name,p.sale_price,p.media_id,c.name AS categorie,";
    $sql .= "m.file_name AS image FROM products p";
    $sql .= " LEFT JOIN categories c ON c.id = p.categorie_id";
    $sql .= " LEFT JOIN media m ON m.id = p.media_id";
    $sql .= " ORDER BY p.id DESC LIMIT " . $db->escape((int)$limit);
    return find_by_sql($sql);
}

/*--------------------------------------------------------------*/
/* Function for Find Highest saleing Product
/*--------------------------------------------------------------*/
function find_higest_saleing_product($limit)
{
    global $db;
    $sql = "SELECT p.name, COUNT(s.product_id) AS totalSold, SUM(s.qty) AS totalQty";
    $sql .= " FROM sales s";
    $sql .= " LEFT JOIN products p ON p.id = s.product_id ";
    $sql .= " GROUP BY s.product_id";
    $sql .= " ORDER BY SUM(s.qty) DESC LIMIT " . $db->escape((int)$limit);
    return $db->query($sql);
}

/*--------------------------------------------------------------*/
/* Function for find all sales
/*--------------------------------------------------------------*/
function find_all_sale()
{
    global $db;
    $sql = "SELECT s.id,s.qty,s.price,s.date,p.name";
    $sql .= " FROM sales s";
    $sql .= " LEFT JOIN products p ON s.product_id = p.id";
    $sql .= " ORDER BY s.date DESC";
    return find_by_sql($sql);
}

/*--------------------------------------------------------------*/
/* Function for Display Recent sale
/*--------------------------------------------------------------*/
function find_recent_sale_added($limit)
{
    global $db;
    $sql = "SELECT s.id,s.qty,s.price,s.date,p.name";
    $sql .= " FROM sales s";
    $sql .= " LEFT JOIN products p ON s.product_id = p.id";
    $sql .= " ORDER BY s.date DESC LIMIT " . $db->escape((int)$limit);
    return find_by_sql($sql);
}

/*--------------------------------------------------------------*/
/* Function for Generate sales report by two dates
/*--------------------------------------------------------------*/
function find_sale_by_dates($start_date, $end_date)
{
    global $db;
    $start_date = date("Y-m-d", strtotime($start_date));
    $end_date = date("Y-m-d", strtotime($end_date));
    $sql = "SELECT s.date, p.name,p.sale_price,p.buy_price,";
    $sql .= "COUNT(s.product_id) AS total_records,";
    $sql .= "SUM(s.qty) AS total_sales,";
    $sql .= "SUM(p.sale_price * s.qty) AS total_saleing_price,";
    $sql .= "SUM(p.buy_price * s.qty) AS total_buying_price ";
    $sql .= "FROM sales s ";
    $sql .= "LEFT JOIN products p ON s.product_id = p.id";
    $sql .= " WHERE s.date BETWEEN '{$start_date}' AND '{$end_date}'";
    $sql .= " GROUP BY DATE(s.date),p.name";
    $sql .= " ORDER BY DATE(s.date) DESC";
    return $db->query($sql);
}

/*--------------------------------------------------------------*/
/* Function for Generate Daily sales report
/*--------------------------------------------------------------*/
function dailySales($year, $month)
{
    global $db;
    $sql = "SELECT s.qty,";
    $sql .= " DATE_FORMAT(s.date, '%Y-%m-%e') AS date,p.name,";
    $sql .= "SUM(p.sale_price * s.qty) AS total_saleing_price";
    $sql .= " FROM sales s";
    $sql .= " LEFT JOIN products p ON s.product_id = p.id";
    $sql .= " WHERE DATE_FORMAT(s.date, '%Y-%m' ) = '{$year}-{$month}'";
    $sql .= " GROUP BY DATE_FORMAT( s.date,  '%e' ),s.product_id";
    return find_by_sql($sql);
}

/*--------------------------------------------------------------*/
/* Function for Generate Monthly sales report
/*--------------------------------------------------------------*/
function monthlySales($year)
{
    global $db;
    $sql = "SELECT s.qty,";
    $sql .= " DATE_FORMAT(s.date, '%Y-%m-%e') AS date,p.name,";
    $sql .= "SUM(p.sale_price * s.qty) AS total_saleing_price";
    $sql .= " FROM sales s";
    $sql .= " LEFT JOIN products p ON s.product_id = p.id";
    $sql .= " WHERE DATE_FORMAT(s.date, '%Y' ) = '{$year}'";
    $sql .= " GROUP BY DATE_FORMAT( s.date,  '%c' ),s.product_id";
    $sql .= " ORDER BY date_format(s.date, '%c' ) ASC";
    return find_by_sql($sql);
}


function getRequestionCode()
{
    global $db;
    $sql = $db->query("SELECT number+1 as number, year, concat('QG', concat(year, LPAd(number+1, 4, '0'))) as code FROM requestion_code where year=year(now()) order by year,number desc limit 1");
    if ($result = $db->fetch_assoc($sql))
        return $result;
    else
        return null;
}

function getGoDownCode()
{
    global $db;
    $sql = $db->query("SELECT year(now()) as year, month(now()) as month, 
              day(now()) as day, number+1 as number, concat('RK', year(now()), LPAD(month(now()),2,'0'), LPAd(day(now()), 2, '0') , LPAd(number+1, 4, '0')) as code FROM godown_entry_code 
              where year=year(now()) order by year,number desc limit 1");
    if ($result = $db->fetch_assoc($sql))
        return $result;
    else
        return null;
}

function getOutgoingCode()
{
    global $db;
    $sql = $db->query("SELECT year(now()) as year, month(now()) as month, 
              day(now()) as day, number+1 as number, concat('CK', year(now()), LPAD(month(now()),2,'0'), LPAd(day(now()), 2, '0') , LPAd(number+1, 4, '0')) as code FROM outgoing_entry_code 
              where year=year(now()) order by year,number desc limit 1");
    if ($result = $db->fetch_assoc($sql))
        return $result;
    else
        return null;
}


function find_product()
{
    global $db;
    $sql = "select min(id) id, name from product group by name order by id";
    $result = $db->query($sql);
    $result_set = $db->while_loop($result);
    return $result_set;
}

function find_product_info()
{
    global $db;
    $sql = "SELECT a.*,b.name as username FROM product as a left join users as b  on a.initiator=b.id;";
    $result = $db->query($sql);
    $result_set = $db->while_loop($result);
    return $result_set;
}

function do_insert($sql)
{
    global $db;
    if ($db->query($sql)) {
        //sucess
        return true;
    } else {
        //failed
        return false;
    }
}

function get_last_auto_id()
{
    global $db;
    $sql = $db->query("select LAST_INSERT_ID() as id");
    if ($result = $db->fetch_assoc($sql))
        return $result['id'];
    else
        return null;

}

function count_by_sql($sql)
{
    global $db;
    $result = $db->query($sql);
    if ($result = $db->fetch_assoc($result))
        return $result['count'];
    else
        return null;
}

function find_id_by_sql($sql)
{
    global $db;
    $result = $db->query($sql);
    if ($result = $db->fetch_assoc($result)) {
        return $result['id'];
    } else {
        return null;
    }
}


function get_requestion_by_id($id)
{
    global $db;

    $sql = "select a.*, ifnull(b.name, '')  as  initiator_name,ifnull(c.name, '') as operator_name from requestion as a 
            left join users as b on a.initiator=b.id 
            left join users as c on a.operator=c.id 
            where a.id = {$db->escape($id)} order by date";
    $result = $db->query($sql);
    $result_set = $db->fetch_assoc($result);
    return $result_set;
}


function get_all_requestion()
{
    global $db;

    $sql = "select a.*, ifnull(b.name, '')  as  initiator_name,ifnull(c.name, '') as operator_name from requestion as a 
            left join users as b on a.initiator=b.id 
            left join users as c on a.operator=c.id 
            order by date";
    $result = $db->query($sql);
    $result_set = $db->while_loop($result);
    return $result_set;
}

function get_godown_entrys()
{
    global $db;

    $sql = "select  distinct a.*, ifnull(b.name, '')  as  initiator_name,ifnull(c.name, '') as operator_name from requestion as a 
            left join users as b on a.initiator=b.id 
            left join users as c on a.operator=c.id 
            left join requestion_details as rd on rd.requestion_id=a.id where rd.requestion_number > rd.godown_number
            order by date";
    $result = $db->query($sql);
    $result_set = $db->while_loop($result);
    return $result_set;
}

function get_requestion_by_code($code)
{
    global $db;

    $sql = "select a.*, ifnull(b.name, '') as initiator_name, ifnull(c.name, '') as operator_name from requestion as a  
            left join users as b on a.initiator=b.id
            left join users as c on a.operator =c.id
            where code='{$db->escape($code)}'";
    $result = $db->query($sql);
    $result_set = $db->while_loop($result);
    return $result_set;
}

function get_requestion_by_initiator($id, $status)
{
    global $db;
    $sql = "";
    if ($status < 0) {
        $sql = "select a.*, b.name as  initiator_name,c.name as operator_name from requestion as a left join users as b on a.initiator=b.id
        left join users as c on a.operator=c.id 
        where b.id={$db->escape($id)} order by date";
    } else {
        $sql = "select a.*, b.name as  initiator_name,c.name as operator_name from requestion as a left join users as b on a.initiator=b.id
        left join users as c on a.operator=c.id 
        where b.id={$db->escape($id)} and a.status={$db->escape($status)} order by date";
    }

    $result = $db->query($sql);
    $result_set = $db->while_loop($result);
    return $result_set;
}

//按操作员获取请购单信息
function get_requestion_by_operator($id, $status)
{
    global $db;
    $sql = "";
    if ($status < 0) {
        $sql = "select a.id, a.code,a.initiator, a.operator, date(date) as date, a.status, b.name as initiator_name,c.name as operator_name from requestion as a 
        left join users as c on a.operator=c.id 
        where c.id={$db->escape($id)} order by date";
    } else {
        $sql = "select a.id, a.code,a.initiator, a.operator, date(date) as date, a.status, b.name as initiator_name,c.name as operator_name from requestion as a 
			left join users as b on a.initiator=b.id
			left join users as c on a.operator=c.id 
			where c.id={$db->escape($id)} and a.status={$db->escape($status)} order by date";
    }

    $result = $db->query($sql);
    $result_set = $db->while_loop($result);

    $jarr = array();
    foreach ($result_set as $result) {
        $count = count($result);//不能在循环语句中，由于每次删除 row数组长度都减小
        for ($i = 0; $i < $count; $i++) {
            unset($result[$i]);//删除冗余数据
        }
        array_push($jarr, $result);
    }

    return $jarr;

    //return $result_set;

}

//获取请购单的详细物料信息
function get_requestion_details_by_id($id)
{
    global $db;
    $sql = "select a.id, rq.code, pj.name as project_name, a.requestion_id, DATE(a.requestion_date) as requestion_date, Date(a.expect_date) as expect_date, 
            a.reference,pt.name as product_type_name,
            b.name, b.specification, concat(b.name, ',', b.specification,',', b.model_number) as product_name, a.memo,
            q.qualification_info,a.is_test, a.is_reprocess, concat(a.requestion_number, ' ', b.unit) as requestion_info,
            b.model_number,  a.requestion_number, a.godown_number, b.unit from requestion_details as a 
            left join project as pj on a.project_id=pj.id
            left join product as b on a.product_id=b.id 
            left join qualification as q on a.qualification_id=q.id
            left join requestion as rq on a.requestion_id=rq.id
            left join product_type as pt on b.type = pt.id
            where a.requestion_id={$db->escape($id)} order by a.requestion_id,a.id";

    $result = $db->query($sql);
    $result_set = $db->while_loop($result);

    $jarr = array();
    foreach ($result_set as $result) {
        $count = count($result);//不能在循环语句中，由于每次删除 row数组长度都减小
        for ($i = 0; $i < $count; $i++) {
            unset($result[$i]);//删除冗余数据
        }
        array_push($jarr, $result);
    }

    return $jarr;
}

function get_godown_entry_summary()
{
    global $db;
    $sql = "select a.*,b.code as requestion_code, c.name supplier_name,u.name user_name from godown_entry as a left join requestion as b on a.requestion_id=b.id
            left join supplier as c on a.supplier_id=c.id
            left join users as u on a.initiator=u.id;";

    $result = $db->query($sql);
    $result_set = $db->while_loop($result);
    return $result_set;
}

function get_outgoing_entry_summary()
{
    global $db;
    $sql = "select a.*, u.name user_name from outgoing_entry as a 
            left join users as u on a.initiator=u.id;";

    $result = $db->query($sql);
    $result_set = $db->while_loop($result);
    return $result_set;
}

function get_godown_entry_summary_by_id($entry_id)
{
    global $db;
    $sql = "select a.*,b.code as requestion_code, c.name supplier_name,u.name user_name from godown_entry as a left join requestion as b on a.requestion_id=b.id
            left join supplier as c on a.supplier_id=c.id
            left join users as u on a.initiator=u.id 
            where a.id={$db->escape($entry_id)};";

    $result = $db->query($sql);
    $result_set = $db->fetch_assoc($result);
    return $result_set;
}

function get_outgoing_entry_summary_by_id($entry_id)
{
    global $db;
    $sql = "select a.*,'' supplier_name,u.name user_name from outgoing_entry as a 
            left join users as u on a.initiator=u.id
            where a.id={$db->escape($entry_id)};";

    $result = $db->query($sql);
    $result_set = $db->fetch_assoc($result);
    return $result_set;
}

function get_godown_entry_details_summary_by_id($entry_id)
{
    global $db;
    $sql = "select a.*,b.name as product_name, b.specification, b.model_number as model_name, b.unit,
			c.requestion_number,pj.name as project_name from godown_entry_details as a  
            left join requestion_details as c on c.id=a.requestion_details_id
            left join product as b on b.id = c.product_id
            left join project as pj on c.project_id=pj.id
            where a.godown_entry_id={$db->escape($entry_id)}";

    $result = $db->query($sql);
    $result_set = $db->while_loop($result);
    return $result_set;
}

function get_outgoing_entry_details_summary_by_id($entry_id)
{
    global $db;
    $sql = "select  distinct a.*,b.name as product_name, b.specification, b.model_number as model_name, 
            b.unit, s.name as supplier_name,pj.name as project_name, r.code as requestion_code, rq.requestion_number, rq.godown_number
            from outgoing_entry_details as a  
            left join product as b on b.id = a.product_id
            left join supplier as s on s.id=a.supplier_id
            left join project as pj on a.project_id=pj.id
            left join requestion_details as rq on rq.id=a.requestion_details_id
            left join requestion as r on r.id=rq.requestion_id
            where a.outgoing_entry_id={$db->escape($entry_id)}";

    $result = $db->query($sql);
    $result_set = $db->while_loop($result);
    return $result_set;
}

function get_godown_entry_details_by_id($entry_id)
{

}

//获取某个请购单的详细请购信息，按内容展示
function get_godown_details_by_id($requestion_id)
{
    global $db;
    $sql = "select a.*, b.name as product_name, concat(b.specification, ',', b.model_number) as product_model, b.unit,p.name as project_name from godown_entry_details as a 
            left join requestion_details as c on c.id=a.requestion_details_id
            left join product as b on b.id = c.product_id
            left join project as p on c.project_id = p.id
            where a.godown_entry_id={$db->escape($requestion_id)}";

    $result = $db->query($sql);
    $result_set = $db->while_loop($result);
    return $result_set;
}

function get_requestion_details_by_code($requestion_code)
{
    global $db;
    $sql = "select @rowno:=@rowno+1 as row_num, a.id, f.code as requestion_code, a.requestion_id, Date(a.requestion_date) as requestion_date, '分组' as usergroup, a.code, '请购人' as username, c.name project_name, b.specification, 
                b.model_number,  a.requestion_number, a.godown_number, b.unit, a.reference, b.name  as product_name, d.name as product_type_name, Date(a.expect_date) as expect_date, e.qualification_info,
                case a.is_test when 1 then '是'  else '否' end as is_test, case a.is_reprocess when 1 then '是'  else '否' end as is_reprocess, a.memo
                 from requestion_details as a 
                left join product as b on a.product_id=b.id 
                left join project as c on a.project_id =c.id
                left join product_type as d on b.type=d.id
                left join qualification as e on a.qualification_id=e.id 
                left join requestion as f on a.requestion_id=f.id
                inner join (select @rowno:=0) as t
                where f.code = '{$db->escape($requestion_code)}' and a.requestion_number>a.godown_number order by a.requestion_id,a.id ";

    $result = $db->query($sql);
    $result_set = $db->while_loop($result);
    return $result_set;
}

function get_outgoing_requestion_details()
{
    global $db;
    $sql = "select @rowno:=@rowno+1 as row_num,dt.* from(
select a.id, a.supplier_id,a.project_id,a.product_id, f.code as requestion_code, a.requestion_id, Date(a.requestion_date) as requestion_date, '分组' as usergroup, a.code, '请购人' as username, c.name project_name, b.specification, 
                b.model_number,  a.requestion_number, a.godown_number,a.outgoing_number, b.unit, a.reference, b.name  as product_name, d.name as product_type_name, Date(a.expect_date) as expect_date, e.qualification_info,
                case a.is_test when 1 then '是'  else '否' end as is_test, case a.is_reprocess when 1 then '是'  else '否' end as is_reprocess, a.memo
                 from requestion_details as a 
                left join product as b on a.product_id=b.id 
                left join project as c on a.project_id =c.id
                left join product_type as d on b.type=d.id
                left join qualification as e on a.qualification_id=e.id 
                left join requestion as f on a.requestion_id=f.id
                where a.godown_number>a.outgoing_number order by  b.name, b.specification, b.model_number, f.date,a.requestion_id,a.id ) as dt
                inner join (select @rowno:=0) as t";

    $result = $db->query($sql);
    $result_set = $db->while_loop($result);
    return $result_set;
}

function get_requestion_product_details($status)
{
    global $db;
    $sql = "select a.id, Date(rq.date) as date, rq.code, pj.name as project_name, a.requestion_id, DATE(a.requestion_date) as requestion_date, Date(a.expect_date) as expect_date, u.name as initiator_name,
            a.reference,pt.name as product_type_name,ifnull(s.name, '') as supplier_name, ifnull(s.id, 0) as supplier_id, a.status,
            b.name, b.specification, concat(b.name, ',', b.specification,',', b.model_number) as product_name, a.memo,
            q.qualification_info,a.is_test, a.is_reprocess, concat(a.requestion_number, ' ', b.unit) as requestion_info,
            b.model_number,  a.requestion_number, a.godown_number, b.unit from requestion_details as a 
            left join project as pj on a.project_id=pj.id
            left join product as b on a.product_id=b.id 
            left join qualification as q on a.qualification_id=q.id
            left join requestion as rq on a.requestion_id=rq.id
            left join product_type as pt on b.type = pt.id
            left join users as u on u.id=rq.initiator
            left join supplier as s on a.supplier_id=s.id 
            where (a.supplier_id is null or a.supplier_id=0)
            order by a.status, rq.date,u.name";

    $result = $db->query($sql);
    $result_set = $db->while_loop($result);

    $jarr = array();
    foreach ($result_set as $result) {
        $count = count($result);//不能在循环语句中，由于每次删除 row数组长度都减小
        for ($i = 0; $i < $count; $i++) {
            unset($result[$i]);//删除冗余数据
        }
        array_push($jarr, $result);
    }

    return $jarr;
}

?>
