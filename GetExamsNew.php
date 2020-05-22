<?php

require_once('include/load.php');
// #moudle_id 模块名称
// #car_type 0小车 1客车 2货车 3摩托车
// #subject_id 0科目一 1科目四
// #抽题规则
// #Rules=小车、bus、客车、科目四、恢复资格考试
// Car=40,22,23,15,0,0
// Bus=37,21,22,14,0,6
// Truck=37,21,22,14,6,0
//
// Subject2=6,12,5,7,10,7,3
// RecoverExam=23,10,12,5,0,0
// RecoverExamBus=20,10,10,5,0,5
// RecoverExamTruck=20,10,10,5,5,0
// [ProblemTypeCountRule]
// Subject1=40,60,0科目一
// Subject2=19,26,5科目四
// RecoverExam=20,30,0
// motor_exam=20,30
//chapterId info
// 1==>道路交通安全法律、法规和规章==>1==>3==>465
// 2==>道路交通信号==>1==>3==>312
// 3==>安全行车、文明驾驶基础知识==>1==>3==>187
// 4==>机动车驾驶操作相关基础知识==>1==>3==>109
// 5==>货车专用知识==>1==>2==>71
// 6==>客车专用知识==>1==>1==>63
// 科目四
// 7==>违法行为综合判断与案例分析==>1==>4==>37
// 8==>安全行车常识==>1==>4==>268
// 9==>常见交通标志、标线和交警手势信号辨识==>1==>4==>215
// 10==>驾驶职业道德和文明驾驶常识==>1==>4==>73
// 11==>恶劣气候和复杂道路条件下驾驶常识==>1==>4==>178
// 12==>紧急情况下避险常识==>1==>4==>94
// 13==>交通事故救护及常见危化品处置常识==>1==>4==>35
// 14==>摩托车科目一==>1==>5==>400
// 15==>摩托车科目四==>1==>6==>204

if (!isset($_GET["subject_id"]) || !isset($_GET["car_type"]) || !isset($_GET["module_id"])) {
    echo "error params";
    return;
}


//按章节选取题目moudle_id=0
//套题、技巧练习moudle_id=1,2
//moudle_id=3,易错题练习，也需要区分科目一和科目四
//moudle_id=4，模拟考试，需要进行题目的计算并取得题目信息
//moudle_id=5,模拟恢复资格考试
//subject_id=0科目一
//subject_id=1科目二
//
//car_type=0&subject_id=1&module_id=4科目四模拟考试
//car_type=0&subject_id=0&module_id=4科目一小车模拟考试
//car_type=1&subject_id=0&module_id=4科目一汽车模拟考试
//car_type=2&subject_id=0&module_id=4科目一货车模拟考试
//car_type=0&subject_id=0&module_id=5科目一小车恢复资格考试
//car_type=1&subject_id=0&module_id=5科目一汽车恢复资格考试
//car_type=2&subject_id=0&module_id=5科目一货车恢复资格考试

//car_type 0 小车 1客车 2 货车 3摩托车 车辆类型
//subject_id 0 科目一 1科目四 科目类型

//classfication 1客车 2 货车 3 小车 4通用科目四 5 摩托车科目一 6 摩托车科目四
//$moudle_id 用于区分分组
//group type 0 章节 1技巧 2 强化 3 错题

$car_type = $_GET["car_type"];
$subject_id = $_GET["subject_id"];
$module_id = $_GET["module_id"];//5恢复资格考试 4模拟考试
//科目一
//抽题章节比例
//科目一的前4个章节及货车、客车
//数据库中的id[34,35,36,37,38,39]
$carRule = [40, 22, 23, 15, 0, 0];
$busRule = [37, 21, 22, 14, 0, 6];
$trunkRule = [37, 21, 22, 14, 6, 0];

//恢复资格考试
$carRecoverRule = [23, 10, 12, 5, 0, 0];
$busRecoverRule = [20, 10, 10, 5, 0, 5];
$trunkRecoverRule = [20, 10, 10, 5, 5, 0];

//抽题类型比例,恢复资格考试
$recoverExam = [0, 20, 30];
$simulateExam0Rule = [0, 40, 60];

//科目四
//科目四抽题章节比例，章节从第7章开始
//科目四数据库中的id[40,41,42,43,44,45,46]
$subject1Rule = [6, 12, 5, 7, 10, 7, 3];

//科目四模拟考试抽题类型比例,多选，判断 ，单选
$simulateExam1Rule = [10, 20, 20];

//摩托车
$Moter0Rule = [100];
$Moter1Rule = [50];
$moter0Exam = [0, 40, 60];
$moter1Exam = [0, 20, 30];
//$moterRecoverExam = [0, 20, 30];


$chapterCountRule = [];
$typeCountRule = [];
//恢复资格考试
if ($module_id == 5) {
    if ($car_type == 0)
        $chapterCountRule = $carRecoverRule;
    else if ($car_type == 1)
        $chapterCountRule = $busRecoverRule;
    else if ($car_type == 2)
        $chapterCountRule = $trunkRecoverRule;
    else if ($car_type == 3)
        $chapterCountRule = $Moter1Rule;

    $typeCountRule = $recoverExam;
}

//模拟考试
if ($module_id == 4) {
    //科目一
    if ($subject_id == 0) {
        $typeCountRule = $simulateExam0Rule;
        if ($car_type == 0)
            $chapterCountRule = $carRule;
        else if ($car_type == 1)
            $chapterCountRule = $busRule;
        else if ($car_type == 2)
            $chapterCountRule = $trunkRule;
        else if ($car_type == 3) {
            $chapterCountRule = $Moter0Rule;
        }
    }

    //科目四
    if ($subject_id == 1) {
        if ($car_type == 3) {
            $typeCountRule = $moter1Exam;
            $chapterCountRule = $Moter1Rule;
        } else {
            $typeCountRule = $simulateExam1Rule;
            $chapterCountRule = $subject1Rule;
        }
    }
}

//typeRules类型规则 每种类型题目的数量
//每个章节需要的题目数量chapterCountRule
//carType 车辆类型 0 汽车 1 客车 2货车
//subject_id 0科目一 1科目四

$start = 0;
$group_ids = "34,35,36,37,38,39";
if ($subject_id == 1) {
    if ($car_type == 3) {
        $group_ids = "48";
        $start = 48;
    } else {
        $group_ids = "40,41,42,43,44,45,46";
        $start = 40;
    }
} else {
    if ($car_type == 1) {
        $group_ids = "34,35,36,37,39";
        $start = 34;
    } else if ($car_type == 2) {
        $group_ids = "34,35,36,37,38";
        $start = 34;
    } else if ($car_type == 0) {
        $group_ids = "34,35,36,37,38,39";
        $start = 34;
    } else if ($car_type == 3) {
        $group_ids = "47";
        $start = 47;
    }
}

$questionList = getQuestionIdByRule($chapterCountRule, $typeCountRule, $group_ids, $start);

echo json_encode($questionList);

function getQuestionsId($chapterRule, $questionList, $questionCount, $start)
{
    $resultList = [];

    $curChapterRule = [];
    for ($i = 0; $i < count($chapterRule); $i++) {
        $curChapterRule[$i] = 0;
    }

    $getCount = 0;
    foreach ($questionList as $question) {
        $chapterId = intval($question['group_id']);

        $index = $chapterId - $start;
        $curCount = $curChapterRule[$index];
        if ($curCount >= $chapterRule[$index])
            continue;

        $resultList[$getCount] = intval($question['id']);
        $getCount = $getCount + 1;
        if ($questionCount <= $getCount)
            break;
    }

    return $resultList;
}

//获取id列表
function getQuestionIdByRule($chapterCountRule, $typeRules, $group_ids, $start)
{
    //typeRules类型规则 每种类型题目的数量
    //每个章节需要的题目数量chapterCountRule
    //carType 车辆类型 0 汽车 1 客车 2货车
    //subject_id 0科目一 1科目四
    /*
        $group_ids = "34,35,36,37,38,39";
        if ($subject_id == 1) {
            $group_ids = "40,41,42,43,44,45,46";
        } else {
            if ($carType == 1) {
                $group_ids = "34,35,36,37,39";
            } else if ($carType == 2) {
                $group_ids = "34,35,36,37,38";
            } else {
                $group_ids = "34,35,36,37,38,39";
            }
        }
      */

    //$data = getGroupsQuestions($group_ids, $questionType);


    $index = 0;
    $typeList = [3, 1, 2];
    $ids1 = "0";
    $ids2 = "0";
    $ids3  = "0";
    foreach ($typeRules as &$questionCount) {
        $questionType = $typeList[$index];

        if ($questionCount > 0) {
            $data = getGroupsQuestions($group_ids, $questionType);

            shuffle($data);

            //某类题目的列表
            $questionIdList = getQuestionsId($chapterCountRule, $data, $questionCount, $start);

            $ids = implode($questionIdList, ",");
            //5 1 2
            if ($questionType == 3)
                $ids3 = $ids;
            else if ($questionType == 2)
                $ids2 = $ids;
            else
                $ids1 = $ids;
        }
        $index++;
    }

    if($ids1)
        $resultQuestionType1List = getQuestionInfo($ids1, 0);
    else
        $resultQuestionType1List = [];

    $resultQuestionType2List = getQuestionInfo($ids2, count($resultQuestionType1List));
    $resultQuestionType3List = getQuestionInfo($ids3, count($resultQuestionType2List) +count($resultQuestionType1List));

    $resultQuestionList = array_merge(array_merge($resultQuestionType1List, $resultQuestionType2List), $resultQuestionType3List);

    return $resultQuestionList;
}

function getQuestionInfo($questionIdList, $index)
{
    $data = getQuestionsByIds($questionIdList);
    $questionList = [];
    foreach ($data as $question) {
        $options = [];
        $optionNum = 4;
        if (intval($question["type"]) == 1) {
            $optionNum = 2;
        }
        for ($i = 1; $i <= $optionNum; $i++) {
            array_push($options, array("answer" => $question['option' . $i], "flag" => $question["answer" . $i], "result" => "0", "keyword" => $question['option' . $i . 'Emphasize']));
        }
        #打乱答案的顺序
        shuffle($options);

        $id = 0;
        $shuffleOptions = [];
        foreach ($options as $option) {
            $option["id"] = $id;
            array_push($shuffleOptions, $option);
            $id = $id + 1;
        }

        $cur_arry = array("index" => $index, "id" => $question["id"], "tittle" => $question["tittle"], "image" => $question["image"], "video" => $question["flash"], "questionType" => intval($question["type"]), "isAnswered" => 0, "isRight" => 2, 'options' => $shuffleOptions, 'skill' => $question["skillEmphasize"], 'notice' => $question["notice"], 'keyword' => $question["tittleEmphasize"]);
        array_push($questionList, $cur_arry);
        $index++;
    }

    return $questionList;
    //echo json_encode($questionList);
}

