<?php
	if(!isset($_GET["code"]))
	{
		echo "{'error':'no code'}";
		return;
	}
	
	$code = $_GET["code"];
	$url = 'https://api.weixin.qq.com/sns/jscode2session?appid=wxbf1eec5f54967e02&secret=1f2babeabe34b8a1dc55eefc9c439032&js_code='.$code.'&grant_type=authorization_code'; 
	#$ch = curl_init(); 
	#$timeout = 5; 
	#curl_setopt($ch, CURLOPT_URL, $url); 
	#curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
	#curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout); 
	//在需要用户检测的网页里需要增加下面两行 
	//curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY); 
	//curl_setopt($ch, CURLOPT_USERPWD, US_NAME.":".US_PWD); 
	#$contents = curl_exec($ch); 
	#curl_close($ch); 
	
	$contents = file_get_contents($url);
	echo $contents; 
	#081g9KEg2UmXrC0Z4PHg27vzEg2g9KEG
	#appid: wxbf1eec5f54967e02
	#appSecret:1f2babeabe34b8a1dc55eefc9c439032	
	#密钥：1gB2Eb89pF2FO9PE1njatx6evAIc0bTL
	
?>