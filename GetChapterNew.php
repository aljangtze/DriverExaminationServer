<?php
require_once('include/load.php');


if (isset($_GET["subject_id"]) && isset($_GET["car_type"]) && isset($_GET["module_id"])) {

    //按章节选取题目moudle_id=0,需要区分科目一和科目四
    //套题、技巧练习moudle_id=1,2，也需要区分科目一和科目四，根据设置的章节信息进行判断
    //moudle_id=3,易错题练习，也需要区分科目一和科目四
    //moudle_id=4，模拟考试，需要进行题目的计算并取得题目信息
    //moudle_id=5,模拟恢复资格考试

    //car_type 0 小车 1客车 2 货车 3摩托车 车辆类型
    //subject_id 0 科目一 1科目四 科目类型

    //classfication 1客车 2 货车 3 小车 4通用科目四 5 摩托车科目一 6 摩托车科目四
    //group type 0 章节 1技巧 2 强化 3 错题

    $car_type = $_GET["car_type"];
    $subject_id = $_GET["subject_id"];
    $module_id = $_GET["module_id"];
    $user_id = $_GET["user_id"];

    $data = getGroups($car_type, $subject_id, $module_id, $user_id);

    echo json_encode($data);
} else if (isset($_GET["id"])&& isset($_GET["user_id"])) {
    $group_id = $_GET["id"];
    $user_id= $_GET["user_id"];
    $data = getGroupQuestions($group_id, $user_id);

    $index= 0;
    $questionList = [];
    foreach ($data as $question) {
        $options = [];
        $optionNum = 4;
        if(intval($question["type"])==1)
        {
            $optionNum = 2;
        }
        for ($i = 1; $i <= $optionNum; $i++) {
            array_push($options, array("answer" => $question['option' . $i], "flag" => $question["answer" . $i], "result" => "0", "keyword" => $question['option' . $i . 'Emphasize']));
        }

        if(intval($question["type"])!=1) {
            #打乱答案的顺序
            shuffle($options);
        }

        $id = 0;
        $shuffleOptions = [];
        foreach ($options as $option) {
            $option["id"] = $id;
            array_push($shuffleOptions, $option);
            $id = $id + 1;
        }

        $cur_arry = array("index"=>$index, "id" => $question["id"], "tittle" => $question["tittle"], "image" => $question["image"], "video" => $question["flash"], "questionType" => intval($question["type"]), "isAnswered" => 0, "isRight" => 2, 'options' => $shuffleOptions, 'skill' => $question["skillEmphasize"], 'notice' => $question["notice"], 'keyword' => $question["tittleEmphasize"]);
        array_push($questionList, $cur_arry);
        $index++;
    }

    echo json_encode($questionList);
}
?>
