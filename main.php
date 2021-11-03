#!/usr/bin/env php
<?php

/*
 *---------------------------------------------------------------
 * Pure php webserver
 *---------------------------------------------------------------
 *
 * This is the source code for the tutorial:
 */

use ClanCats\Station\PHPServer\Exceptions\BindException;
use ClanCats\Station\PHPServer\Exceptions\PortException;
use ClanCats\Station\PHPServer\Request;
use ClanCats\Station\PHPServer\Response;
use ClanCats\Station\PHPServer\Server;

require 'vendor/autoload.php';

$cliOption = getopt("p:", ['port:']);

if (count($cliOption) === 0) {
    $port = 8000;
} else {
    $port = (int)$cliOption['port'] ?? (int)$cliOption['p'];
}

// create a new server instance
$server = new Server('127.0.0.1', $port);
register_shutdown_function(function () use ($server) {
    $server->shutdownServer();
});
echo("Server listening on 127.0.0.1:$port" . PHP_EOL);

// start listening
$server->listen(function (Request $request) {
    // print information that we received the request
    echo date("Y-m-d H:i:s") . ' - ' . $request->method() . ' ' . $request->uri() . PHP_EOL;
    // return a response containing the request information
    $responseBody = '<pre>Method: ' . $request->method() . '<hr>Query Params:<br><br>' . print_r($request->parameters, 1). '</pre>';
    return new Response($responseBody, 200);
});
