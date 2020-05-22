<?php
	if(!isset($_GET["total_fee"]) && !isset($_GET["openid"]))
	{
		$retErr = array("return_code"=>"FAIL","return_msg"=>"you must set openid and total_fee");
		
		echo json_encode($retErr);
		return;
	}
	
	$time_start = date("YmdHis");
	$time_expire= $time_start +  320;

	//订单号
	//订单金额
	$total_fee = (int)$_GET["total_fee"];
	$openid =  $_GET["openid"];

	$bookingNo = '11020103';
	// $total_fee = 1;
	// $openid = 'oLQL-0OeBet1sfmGWSW3V3vnfAR8';
	
	function getIp(){
		if (getenv("HTTP_CLIENT_IP") && strcasecmp(getenv("HTTP_CLIENT_IP"), "unknown"))
		$ip = getenv("HTTP_CLIENT_IP");
		else if (getenv("HTTP_X_FORWARDED_FOR") && strcasecmp(getenv("HTTP_X_FORWARDED_FOR"), "unknown"))
		$ip = getenv("HTTP_X_FORWARDED_FOR");
		else if (getenv("REMOTE_ADDR") && strcasecmp(getenv("REMOTE_ADDR"), "unknown"))
		$ip = getenv("REMOTE_ADDR");
		else if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], "unknown"))
		$ip = $_SERVER['REMOTE_ADDR'];
		else
		$ip = "unknown";
		return($ip);
	}
	$spbill_create_ip = getIp();
	$appid = 'wxbf1eec5f54967e02';
	$body = '商家名称-销售商品类目';
	$attach = "支付attach";
	$mch_id = "1487274452";//商户号
	$notify_url = 'https://smallprogram.xsjwang.com/onnotify.php';
	
	function getRandom($param){
		$str="0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
		$key = "";
		for($i=0;$i<$param;$i++)
		 {
			 $key .= $str{mt_rand(0,58)};    //生成php随机数
		 }
		 return $key;
	}
	$nonce_str = getRandom(32);
	$out_trade_no = getRandom(32); //订单号
	$url = "https://api.mch.weixin.qq.com/pay/unifiedorder";

	$values = array(
			'appid'=>$appid,
			'mch_id'=>$mch_id,
			'nonce_str'=>$nonce_str,
			'body'=> $body,
			'attach'=>$attach,
			'out_trade_no'=> $out_trade_no,
			'total_fee'=> $total_fee,
			'spbill_create_ip'=> $spbill_create_ip,
			'time_start'=>$time_start,
			'time_expire'=>$time_expire,
			'notify_url'=> $notify_url,
			'openid'=> $openid,
			'sign_type'=>'MD5',
			'trade_type'=> 'JSAPI',
			);
	
	
	$sign = MakeSign($values);
	
	$arraySign = array('sign'=>$sign);
	
	$values = array_merge($values, $arraySign);
	
	
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
	
	$xml = ToXml($values);
	
	//使用curl进行
	// $ch = curl_init();  //初始化curl  
    // curl_setopt ($ch, CURLOPT_URL, $url);  
    // curl_setopt ($ch, CURLOPT_POST, 1);  //使用post请求  
    // curl_setopt ($ch, CURLOPT_HEADER, 0);  
    // curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);  
    // curl_setopt ($ch, CURLOPT_POSTFIELDS, $xml);  //提交数据  
    // curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, true);  //重定向地址也输出  
	 
	// $response = curl_exec($ch);
	// //返回结果
	// if($response){
		// echo "curl成功";
		// curl_close($ch);
		// echo $response;
	// } else { 
		// $error = curl_errno($ch);
		// curl_close($ch);
		// echo "curl出错，错误码:$error";
	// }
	
	$response = DoPost($url, $xml);
	//返回结果
	if(!$response){
		$retErr = array("return_code"=>"FAIL","return_msg"=>"can't get response from qq server.");
		return $retErr;
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
	$values = FromXml($response);
	
	#printf_info($values);
	#echo json_encode($values);
	$prepay_id = $values['prepay_id'];
	$timeStamp= date("YmdHis");
	$nonce_str = getRandom(32);
	$data = array('appId'=>$appid,
		'timeStamp'=>$timeStamp,
		'nonceStr'=>$nonce_str,
		'package'=>'prepay_id='.$prepay_id,
		'signType'=>'MD5');
		
	$sign = MakeSign($data);
	
	$arraySign = array('pay_sign'=>$sign,"return_code"=>"SUCCESS","return_msg"=>"OK",'prepay_id'=>$prepay_id);
	
	$values = array_merge($data, $arraySign);
	echo json_encode($values);
	
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