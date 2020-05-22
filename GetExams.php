<?php
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

	if(!isset($_GET["subject_id"]) || !isset($_GET["car_type"]) || !isset($_GET["module_id"]))
	{
		echo "error params";
		return;
	}
		
	$fileName = "info.ini";
	if(file_exists($fileName))
	{
		//car_type=0&subject_id=1&module_id=4科目四模拟考试
		//car_type=0&subject_id=0&module_id=4科目一小车模拟考试
		//car_type=1&subject_id=0&module_id=4科目一汽车模拟考试
		//car_type=2&subject_id=0&module_id=4科目一货车模拟考试
		//car_type=0&subject_id=0&module_id=5科目一小车恢复资格考试
		//car_type=1&subject_id=0&module_id=5科目一汽车恢复资格考试
		//car_type=2&subject_id=0&module_id=5科目一货车恢复资格考试
		$car_type = $_GET["car_type"];
		$subject_id = $_GET["subject_id"];
		$module_id = $_GET["module_id"];//5恢复资格考试 4模拟考试
		//科目一
		//抽题章节比例
		$carRule = [40,22,23,15,0,0];
		$busRule = [37,21,22,14,0,6];
		$trunckRule = [37,21,22,14,6,0];
		
		$carRecoverRule = [23,10,12,5,0,0];
		$busRecoverRule = [20,10,10,5,0,5];
		$trunckRecoverRule = [20,10,10,5,5,0];
		
		//抽题类型比例,恢复资格考试
		//多选择、判断、单选
		$recoverExam=[0,20,30];
		$simulateExam0Rule = [0,40,60];
		
		//科目四
		//科目四抽题章节比例，章节从第7章开始
		$subject1Rule = [6,12,5,7,10,7,3];
	
		//科目四模拟考试抽题类型比例, 多选，判断 ，单选
		$simulateExam1Rule = [10,20,20];
		
		//摩托车
		// $subject0Moter = [40,60]
		// $subject1Moter = [20,30]
		// $moterExam0Rule=[40,60, 0]
		// $moterExam1Rule=[20,30, 0]
		
		//取小车的题目
		$values = parse_ini_file($fileName, true, INI_SCANNER_RAW);
		
		$chapterCountRule = [];
		$typeCountRule = [];
		if($module_id == 5)
		{
			if($car_type == 0)
				$chapterCountRule = $carRecoverRule;
			if($car_type == 1)
				$chapterCountRule = $busRecoverRule;
			if($car_type == 2)				
				$chapterCountRule = $trunckRecoverRule;
			
			$typeCountRule = $recoverExam;
		}
		
		if($module_id == 4)
		{
			if($subject_id == 0)
			{
				$typeCountRule = $simulateExam0Rule;
				if($car_type == 0)
					$chapterCountRule = $carRule;
				if($car_type == 1)
					$chapterCountRule = $busRule;
				if($car_type == 2)				
					$chapterCountRule = $trunckRule;
			}
			
			if($subject_id == 1)
			{
				$typeCountRule = $simulateExam1Rule;
				$chapterCountRule = $subject1Rule;
			}
		}
		// print_r($chapterCountRule);
		// print_r($typeCountRule);
		
		if($car_type == 3)
			$questionList = getQuestionIdOfMotor($values, $subject_id);
		else
			$questionIdList = getQuestionIdByRule($values, $chapterCountRule, $typeCountRule, $car_type,$subject_id, $module_id);
		
		getQuestionInfo($questionList);
	}
	else
	{
		echo 'error';
	}
	
	function getListFromInfo($values, $section, $questionType)
	{
		$questionList = $values[$section][$questionType];
		if($questionList == "")
			return [];
		$questionList = explode(",", $questionList);
		$len = count($questionList);
		$questionList = array_slice($questionList, 0, $len-1);
		shuffle($questionList);
		return $questionList;
	}
	
	function getQuestionsId($values, $chapterRule, $questionList, $questionCount, $start)
	{
		//values是列表
		//$questionList = [];
		$resultList = [];
		
		$curChapterRulle = [];
		for($i=0; $i<count($chapterRule); $i++)
		{
			$curChapterRulle[$i] = 0;
		}
		
		$getCount = 0;
		foreach($questionList as &$questionId)
		{
			$chapterId = $values['question_chapter_info'][(string)$questionId];
			
			$index = $chapterId-$start;
			if($index>=count($curChapterRulle))
				continue;
			$curCount = $curChapterRulle[$index];
			if($curCount >= $chapterRule[$index])
				continue;
			
			$resultList[$getCount] = $questionId;
			$getCount = $getCount + 1;
			if($questionCount <= $getCount)
				break;
		}
		
		#print_r($resultList);
		return $resultList;
	}

	function getQuestionIdOfMotor($values, $subject_id)
	{
		$resultQuestionList = [];
		if($subject_id == 0)
		{
			$questionList1 = getListFromInfo($values, 'motor_0_info', 1);
			shuffle($questionList1);
			$questionList2 = getListFromInfo($values, 'motor_0_info', 2);
			shuffle($questionList2);
			
			$resultQuestionList = array_merge($resultQuestionList, array_slice($questionList1, 0, 40));
			$resultQuestionList = array_merge($resultQuestionList, array_slice($questionList2, 0, 60));
		}
		else
		{
			$questionList1 = getListFromInfo($values, 'motor_1_info', 1);
			shuffle($questionList1);
			$questionList2 = getListFromInfo($values, 'motor_1_info', 2);
			shuffle($questionList2);
			
			$resultQuestionList = array_merge($resultQuestionList, array_slice($questionList1, 0, 20));
			$resultQuestionList = array_merge($resultQuestionList, array_slice($questionList2, 0, 30));
		}
		return $resultQuestionList;
	}
	function getQuestionIdByRule($values, $chapterCountRule, $typeRules, $carType, $subject_id, $module_id)
	{
		//typeRules类型规则 每种类型题目的数量 
		//每个章节需要的题目数量chapterCountRule
		//carType 车辆类型 0 汽车 1 客车 2货车
		//subject_id 0科目一 1科目四
		#$questionList = getListFromInfo($values, "car_0_info", 1);
		#print_r($questionList);
		
		#getListFromInfo($values, "bus_0_info", 1);
		#getListFromInfo($values, "trunck_0_info", 1);
		
		#getListFromInfo($values, "car_0_info", 2);
		#getListFromInfo($values, "bus_0_info", 2);
		#getListFromInfo($values, "trunck_0_info", 2);
		
		#getListFromInfo($values, "car_1_info", 3);
		#getListFromInfo($values, "car_1_info", 2);
		#getListFromInfo($values, "car_1_info", 1);
		
		// $carType = 2;
		// $subject_id = 1;
		
		// print_r($chapterCountRule);
		// print_r($typeRules);
		// print_r($carType);
		// print_r($subject_id);
		
		$index= 0;
		$questionType  = 1;
		$typeList = [3, 1, 2];
		$resultQuestionList = [];
		$start = 1;
		$resultQuestionType3List = [];
		foreach($typeRules as &$questionCount)
		{
			$questionType = $typeList[$index];
			if($questionCount > 0)
			{
				//科目一或者恢复资格考试
				if($subject_id == 0 || $module_id==5)
				{
					$questionList = getListFromInfo($values, "car_0_info", $questionType);
					if($carType==1)
					{
						$cur_questionList = getListFromInfo($values, "bus_0_info", $questionType);
						$questionList = array_merge($questionList, $cur_questionList);
					}
					if($carType==2)
					{
						$cur_questionList = getListFromInfo($values, "trunck_0_info", $questionType);
						$questionList = array_merge($questionList, $cur_questionList);
					}
				}
				else
				{
					$questionList = getListFromInfo($values, "car_1_info", $questionType);
				}
				
				//科目四从7开始
				if($subject_id == 1)
				{
					if($module_id == 5)
						$start = 1;
					else
						$start = 7;
				}
				
				
				//某类题目的列表
				$questionList = getQuestionsId($values, $chapterCountRule, $questionList, $questionCount, $start);
			
				//5 1 2
				if($questionType ==3)
					$resultQuestionType3List = $questionList;
				else
					$resultQuestionList = array_merge($resultQuestionList, $questionList);
				#print_r($questionList);
			}
			$index++;
		}
		
		$resultQuestionList = array_merge($resultQuestionList, $resultQuestionType3List);
		
		#print_r('==>'.count($resultQuestionList));
		
		#print_r(count($resultQuestionList));
		#print_r($resultQuestionList);
		
		return $resultQuestionList;
	}
	
	function getQuestionInfo($questionIdList)
	{
		$index = 0;
		$questionList = [];
		foreach($questionIdList as $questionId)
		{
			$fileName = "Questions/".$questionId.".txt";
			#echo $fileName;
			if(file_exists($fileName))
			{
				$values = parse_ini_file($fileName, true, INI_SCANNER_RAW);
				#print_r($values["QuesitonInfo"]["Id"]);
				$values["QuesitonInfo"]["Tittle"];
				
				$options =[];
				for($i = 1;$i<=4;$i++)
				{
					$option = $values["Options"]["Options".$i];
					if($option != '')
					{
						$optionKeyword = $values["Options"]["Image".$i];
						if(null == $optionKeyword)
							$optionKeyword = '';
						array_push($options, array("answer"=>$option, "flag"=>$values["AnswerInfo"]["Answer".$i],"result"=>"0", "keyword"=>$optionKeyword));
					}
				}
				#打乱答案的顺序
				shuffle($options);
				#$answers = $values["AnswerInfo"];
				// $options[0]
				$id = 0;
				$shuffleOptions = [];
				foreach($options as $option)
				{
					$option["id"] = $id;
					array_push($shuffleOptions, $option);
					$id = $id + 1;
				}
				
				$keyword = $values["QuesitonInfo"]["SkillEmphasize"];
				if(null == $keyword)
					$keyword = "";
				$cur_arry = array("index"=>$index, "id"=>$values["QuesitonInfo"]["Id"],"tittle"=>$values["QuesitonInfo"]["Tittle"], "image"=>$values["QuesitonInfo"]["ImagePath"], "video"=>str_replace('.swf', '', $values["QuesitonInfo"]["FlashPath"]), "questionType"=>$values["QuesitonInfo"]["Type"], "isAnswered"=>0,"isRight"=>2, 'options'=>$shuffleOptions, 'skill'=>$values["SkillInfo"]["SkillNotice"], 'notice'=>$values["SkillInfo"]["NormalNotice"], 'keyword'=>$keyword);
				#print_r($cur_arry);
				array_push($questionList, $cur_arry);
				#echo json_encode($values);
				$index = $index + 1;
			}
		}
		echo json_encode($questionList);
	}
?>
