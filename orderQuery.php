<?php

	$appid = 'wxbf1eec5f54967e02';
	$mch_id = "1487274452";//商户号
	
	function getRandom($param){
		$str="0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
		$key = "";
		for($i=0;$i<$param;$i++)
		 {
			 $key .= $str{mt_rand(0,58)};    //生成php随机数
		 }
		 return $key;
	}

	$url = 'https://api.mch.weixin.qq.com/pay/orderquery';
	$nonce_str = getRandom(32);
	if(!isset($_GET["transaction_id"]) && !isset($_GET["out_trade_no"]))
	{
		echo "必须设置一个transaction_id或out_trade_no";
		return;
	}

	if(isset($_GET["transaction_id"]))
	{
		$transaction_id = $_GET["transaction_id"];
		//$transaction_id = '4200000031201712282901826538';
		$values = array(
			'appid'=>$appid,
			'mch_id'=>$mch_id,
			'nonce_str'=>$nonce_str,
			'transaction_id'=>$transaction_id,
			'sign_type'=>'MD5',
			);
	}
	if(isset($_GET["out_trade_no"]))
	{
		$out_trade_no = $_GET["out_trade_no"];
		$values = array(
			'appid'=>$appid,
			'mch_id'=>$mch_id,
			'nonce_str'=>$nonce_str,
			'out_trade_no' =>$out_trade_no,
			'sign_type'=>'MD5',
			);
	}

	
	$sign = MakeSign($values);

	$arraySign = array('sign'=>$sign);
	
	$values = array_merge($values, $arraySign);
	
	$xml = ToXml($values);

	$response = DoPost($url, $xml);
	//返回结果
	if(!$response){
		$retErr = array("return_code"=>"FAIL","return_msg"=>"can't get response from qq server.");
		return $retErr;
	} 

	//echo $response;
	
	$values = FromXml($response);

	printf_info($values);
	
	function MakeSign($values)
	{
		//签名步骤一：按字典序排序参数
		ksort($values);
		$string = ToUrlParams($values);
		//签名步骤二：在string后加入KEY
		$string = $string . "&key=".'1gB2Eb89pF2FO9PE1njatx6evAIc0bTL';
		//签名步骤三：MD5加密
		$string = md5($string);
		//签名步骤四：所有字符转为大写
		$result = strtoupper($string);
		return $result;
	}

	function ToXml($values)
	{
		$xml = "<xml>";
		foreach ($values as $key=>$val)
		{
			if (is_numeric($val)){
				$xml.="<".$key.">".$val."</".$key.">";
			}else{
				$xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
			}
		}
		$xml.="</xml>";
		
		return $xml;
	}


	function FromXml($xml)
	{	
		if(!$xml){
			{
				$retErr = array("return_code"=>"FAIL","return_msg"=>"recevie xml data can't param！");
				return $retErr;
			}
		}
        //将XML转为array
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $values = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);		
		return $values;
	}
	
	function printf_info($data)
	{
		foreach($data as $key=>$value){
			echo "<font color='#00ff55;'>$key</font> : $value <br/>";
		}
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
	
	
	function DoPost($url, $xml = null) {
        $content = $xml;
        $content_length = strlen($xml);
        $options = array(
            'http' => array(
                'method' => 'POST',
                'header' =>
                "Content-type: application/x-www-form-urlencoded\r\n" .
                "Content-length: $content_length\r\n",
                'content' => $content
            )
        );
        return file_get_contents($url, false, stream_context_create($options));
    }
	
	
?>