<?php
	$id = $_GET["id"];
	$fileName = "Questions/".$id.".txt";
	if(file_exists($fileName))
	{
		#$file = fopen($filename);
		#echo "hello";
		$values = parse_ini_file($fileName, true, INI_SCANNER_NORMAL);
		#print_r($values);
		echo json_encode($values, true);
	}
	else
	{
		#echo "Error";
		echo json_encode(array('x'=>"(xdfad)"));
	}
?>
