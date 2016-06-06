<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 'on');
require_once __DIR__ . "/vendor/autoload.php";
$_conf = include_once(__DIR__ . '/config.php');

$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
if(!($socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP))) {
    die( "socket_create() error: " . socket_strerror(socket_last_error()) );
}

if(!($socketConnect = socket_connect($socket, $_conf->external_server_addr, $_conf->external_server_port))) {
    die("socket_connect() error: " . socket_strerror(socket_last_error($socketConnect)));
} else {
    echo "соединение установлено, читаю данные\n";
}


$database = false;

while ($out = socket_read($socket, 1024, PHP_NORMAL_READ)) {
    if(!$database) {
        $database = dbConnect($_conf);
    }


    $isSuccess = (bool)preg_match('/S=(\w+);T=([\w|\-|:]+);B=([\d|.]+)/', $out, $matches);
    if(!$isSuccess) {
        continue;
    }
    array_shift($matches);
    list($symbol, $date, $bid) = $matches;

    try {
        $database->insert("external_data", [
            "symbol" => $symbol,
            "bid" => $bid,
            "date" => $date
        ]);
    } catch (Exception $e) {
        echo($e);
    }

}

socket_close($socket);

