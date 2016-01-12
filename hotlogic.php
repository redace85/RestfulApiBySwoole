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
				echo 'put'.PHP_EOL;
				var_dump($request->post);
				break;
			case 'PATCH':
				echo 'patch'.PHP_EOL;
				var_dump($request->post);
				break;
		}
		//echo $request->server['request_uri'].PHP_EOL;
		//var_dump($request->rawContent());

		$response->header("Content-Type", "application/vnd.collection+json");
		$response->end("Hello Swoole. #");
	}
};

?>
