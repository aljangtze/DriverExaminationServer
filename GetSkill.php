<?php
	function getQuestionsByChapter($chapterId)
	{
		$module_id = $_GET["module_id"];
		$info = "bank_info";
		if($module_id == 3)
			$info = "skill_info";
		else if($module_id == 2)
			$info = "skill_info";
		else
			$info = "bank_info";
		
		$file_path = "info.ini";
		if(!file_exists($file_path)){
			echo "error, chapter file can't find";
			return;
		}
		
		$values = parse_ini_file($file_path, true, INI_SCANNER_NORMAL);
		$questionIdList = explode(",",$values[$info][$chapterId]);
		
		#print_r($questionIdList);
		$questionList = [];
		$index = 0;
		foreach($questionIdList as $questionId)
		{
			$fileName = "Questions/".$questionId.".txt";
			#echo $fileName;
			if(file_exists($fileName))
			{
				#echo $fileName;
				#$file = fopen($filename);
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
	
	//如果没有参数，返回列表 
	//if(!isset($_GET["id"]))
	if(is_array($_GET)&&count($_GET)>0)
	{
		//获取列表信息
		if(isset($_GET["id"]))
		{
			//传入id后就返回某个id的列表信息
			$id = $_GET["id"];
			
			getQuestionsByChapter($id);
		}
		else if(isset($_GET["subject_id"]) && isset($_GET["car_type"]) && isset($_GET["module_id"]))
		{
			$subject_id = $_GET["subject_id"];
			//按章节选取题目moudle_id=0,需要区分科目一和科目四
			//套题、技巧练习moudle_id=1,2，也需要区分科目一和科目四，根据设置的章节信息进行判断
			//moudle_id=3,易错题练习，也需要区分科目一和科目四
			//moudle_id=4，模拟考试，需要进行题目的计算并取得题目信息
			//moudle_id=5,模拟恢复资格考试
			$subject_id =$_GET["subject_id"];
			$car_type = $_GET["car_type"];
			$module_id = $_GET["module_id"];
			$values = parse_ini_file('info.ini', true, INI_SCANNER_NORMAL);
			
			$countInfo = "";
			$file_path = "BankInfo.txt";
			if($module_id == 1)
			{
				$file_path = "BankInfo.txt";
				$countInfo = "bank_info_count";
			}				
			else if($module_id == 2)
			{
				$file_path = "SkillInfo.txt";
				$countInfo = "skill_info_count";
			}
			else 
			{
				$file_path = "easy_error_info.txt";
				$countInfo = "skill_info_count";
			}
			
			if(!file_exists($file_path)){
				echo "error, no file find";
				return;
			}
			
			$file_arr = file($file_path);
			$chapterList = array();
			$index = 1;
			foreach($file_arr as &$line) 
			{
				$info =  explode("==>", $line);
				$chapterId = $info[1];
				
				$car_info = (int)$info[4]; 
				if($subject_id == 0)
				{
					//小车
					if($car_type == 0)
					{
						if($car_info !=3)
							continue;
					}
					else if($car_type == 1)
					{
						//货车
						if($car_info != 1 &&  $car_info != 3)
							continue;
					}
					else if($car_type == 2)
					{
						if($car_info != 2 &&  $car_info != 3)
							continue;
					}
					else if($car_type ==3)
					{
						if($car_info != 5)
							continue;
					}
				}
				else if($subject_id == 1)
				{
					if($car_type == 3)
					{
						if($car_info !=6)
							continue;
					}
					else
					{
						if($car_info != 4)
							continue;
					}
				}
				else
				{
					echo "params error";
					return;
				}
				
				$cur_arry = array("index"=>$index, "id"=>(int)$info[1],"tittle"=>$info[2], "car_type"=>$car_type, "subject_id"=>$subject_id, "count"=>(int)($values[$countInfo][$chapterId]));
				array_push($chapterList, $cur_arry);
				$index = $index + 1;
			}
			#print_r($chapterList);
			echo json_encode($chapterList);
		}
	}
	else
	{
		$file_path = "ChapterInfo.txt";
		
		if(!file_exists($file_path)){
			echo "error, no file find";
			return;
		}
		$file_arr = file($file_path);
		// $a=array("a"=>"red","b"=>"green");
		// array_push($a,"dblue","yellow");
		#print_r($a);

		$chapterList = array();
		$index = 0;
		foreach($file_arr as &$line) 
		{
			$info =  explode("==>", $line);
			$chapterId = $info[1];
			
			$cur_arry = array("index"=>$index, "id"=>(int)$info[0], "car_type"=>(int)$info[2], "tittle"=>$info[3]);
			array_push($chapterList, $cur_arry);
			
			// chapterList
			// echo $info[0].'<br/>';
			// echo $info[1];
			// print_r($info);
			#$chapterList.
			#echo json_encode($info);
			$index = $index + 1;
		}
		#print_r($chapterList);
		echo json_encode($chapterList);
	}
	
?>
