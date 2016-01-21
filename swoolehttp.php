#!/usr/bin/env php
<?php

if (PHP_SAPI != 'cli') {
	echo 'Script should be used under CLI'.PHP_EOL;
	exit;
}
if(!extension_loaded('swoole') || !extension_loaded('redis')){
	echo 'swoole and redis extensions is needed'.PHP_EOL;
	exit;
}

require 'json_collection.php';

class HttpServer
{
	protected $redis;
	protected $json_col;
	protected $serv;
	
	function __construct(){
		$this->serv = new swoole_http_server('0.0.0.0', 9501);
		$this->json_col = new Json_Collection;
		$this->redis = new Redis;
	}
	
	public function setting()
	{
		$this->serv->set([
				'worker_num' => 4, //worker process num
				//'backlog' => 128,   //listen backlog
				'max_request' => 10,
				'dispatch_mode'=>2,  //important
				'daemonize'=>false,
				'debug_mode'=>1,
				]);

		$this->serv->on('request',[$this,'handleReq']);
	}

	public function getRedisIns()
	{
		$this->redis->pconnect(
				'127.0.0.1',6379,3,'Restful');
		return $this->redis;
	}

	static public function hotLogic($request,$redis)
	{
		// hot deployment
		require_once 'hotlogic.php';

		$uri=$request->server['request_uri'];
		switch($request->server['request_method']){
			case 'POST':
				$post_data = $request->post;
				echo 'Post '.print_r($post_data,true).PHP_EOL;

				$res_arr=HotLogic::handlePostReq(
						$uri,$post_data,$redis);
				break;
			case 'DELETE':
				echo 'Del '.$uri.PHP_EOL;
				$res_arr=HotLogic::handleDelReq($uri,$redis);
				break;
			case 'PUT':
				$post_data = $request->post;
				echo 'Put '.print_r($post_data,true).PHP_EOL;
				$res_arr=HotLogic::handlePutReq(
						$uri,$post_data,$redis);
				break;
			case 'PATCH':
				$post_data = $request->post;
				echo 'Patch '.print_r($post_data,true).PHP_EOL;
				$res_arr=HotLogic::handlePatchReq(
						$uri,$post_data,$redis);
				break;
			case 'GET':
				echo 'Get '.$uri.PHP_EOL;
				// search
				if(0===substr_compare($uri,'search',-6))
				{
					$get_args=isset($request->get) ? $request->get : null;
					$res_arr=HotLogic::handleSearchReq($uri,$get_args,$redis);
				}
				else
				{
					$res_arr=HotLogic::handleGetReq($uri,$redis);
				}
				break;
			default:
				// bad request
				$res_arr=['code'=>400];
				break;
		}
		return $res_arr;
	}

	public function handleReq($request, $response)
	{
		//content filter
		if(isset($request->header['content-type']) &&
				'application/vnd.collection+json'==$request->header['content-type'])
		{
			$response->status(406);
			$response->end('');
		}

		// logic layer
		$res_arr = static::hotLogic($request,$this->getRedisIns());

		$response->header('Accept',
				'application/vnd.collection+json');
		$response->header('Accept-Charset',
				'utf-8');
		$response->header('Content-Type',
				'application/vnd.collection+json');
		
		if(array_key_exists('code',$res_arr))
		{
			$code=$res_arr['code'];
			$response->status($code);
			if(201==$code)
			{
				//created extra header
				$response->header('Location',
						Json_Collection::$host.$res_arr['location']);
			}
		}
		else
		{
			// 200 is the default code
			$this->json_col->fillWithArr($res_arr['arr']);
			$ret_str = $this->json_col->getEncodedStr();
			$this->json_col->cleanUp();
		}

		$httpcontent = isset($ret_str)? $ret_str : '';
		unset($ret_str);
		$response->end($httpcontent);
	}

	public function run()
	{
		$this->serv->start();
	}
}

$http = new HttpServer;
$http->setting();
$http->run();
