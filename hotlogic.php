<?php

class HotLogic{
	static public function handleRequest($request,$response){
		switch($request->server['request_method']){
			case 'GET':
				echo 'g';
				break;
			case 'POST':
				echo 'p';
				break;
			case 'PUT':
				echo 'put';
				break;
			case 'PATCH':
				echo 'patch';
				break;
		}
		echo $request->server['request_uri'].PHP_EOL;
		//var_dump($request->rawContent());

		$response->header("Content-Type", "text/html; charset=utf-8");
		$response->end("Hello Swoole. #".rand(1000, 9999));
	}
};

?>
