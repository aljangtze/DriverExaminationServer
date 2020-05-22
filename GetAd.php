<?php
	$host= 'https://smallprogram.xsjwang.com/ad_Images/';
	function listDir($dir, $host)
	{
		$array = array();
		if(is_dir($dir))
		{
		  $filesnames = scandir($dir);
		  $index = 0;
		  foreach ($filesnames as $file) {
			  if($file!="." && $file!="..")
			  {
				  // echo $host.$file;
				  $array[$index] = $host.$file;
				  $index = $index + 1;
				  // echo '<br></br>';
			  }
		  }
		}
		
		return $array;
	}
	
	$arr= listDir("./ad_Images", $host);
	echo json_encode($arr);
?>