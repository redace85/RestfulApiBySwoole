#!/usr/bin/env php
<?php

if (PHP_SAPI != "cli") {
	echo 'Script should be used under CLI'.PHP_EOL;
	exit;
}

$http = new swoole_http_server("0.0.0.0", 9501);

$http->on('request', function ($request, $response) {
		var_dump($request->get, $request->post);
		$response->header("Content-Type", "text/html; charset=utf-8");
		$response->end("<h1>Hello Swoole. #".rand(1000, 9999)."</h1>");
		});

$http->start();

