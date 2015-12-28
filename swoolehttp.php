#!/usr/bin/env php
<?php

if (PHP_SAPI != "cli") {
	echo 'Script should be used under CLI'.PHP_EOL;
	exit;
}

$http = new swoole_http_server("0.0.0.0", 9501);
$http->set([
		'worker_num' => 8, //worker process num
		//'backlog' => 128,   //listen backlog
		'max_request' => 1000,
		'dispatch_mode'=>2,  //important
		'daemonize'=>false,
		'debug_mode'=>1,
]);

$http->on('request', function ($request, $response) {
		require_once 'hotlogic.php';
		HotLogic::handleRequest($request,$response);
		});

$http->start();

