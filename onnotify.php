<?php
	$myfile = fopen("newfile.txt", "w") or die("Unable to open file!");
	$txt = "Bill Gates\n";
	
	$xml = file_get_contents('php://input');
	
	$values = FromXml($xml);
	function FromXml($xml)
	{	
        //将XML转为array
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $values = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);		
		return $values;
	}
	
	
	function ToUrlParams($values)
	{
		$buff = "";
		foreach ($values as $k => $v)
		{
			if($k != "sign" && $v != "" && !is_array($v)){
				$buff .= $k . "=" . $v . "&";
			}
		}
		
		$buff = trim($buff, "&");
		return $buff;
	}

	$params = ToUrlParams($values);
	
	echo $params;
	
	fwrite($myfile, $params);
	fclose($myfile);
?>