<?php
error_reporting(E_ALL); //Выводим все ошибки и предупреждения
require_once __DIR__ . '/vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

class Ws implements MessageComponentInterface{
    protected $clients;

    public function __construct() {
        $this->clients = [];//new \SplObjectStorage;
    }


    function onOpen(ConnectionInterface $conn) {
        $this->clients[spl_object_hash($conn)] = $conn;
    }


    function onClose(ConnectionInterface $conn) {
        unset($this->clients[spl_object_hash($conn)]);
    }


    function onError(ConnectionInterface $conn, \Exception $e) {
        unset($this->clients[spl_object_hash($conn)]);;
        $conn->close();
    }


    function onMessage(ConnectionInterface $from, $msg) {
        $msg = json_decode($msg);
        if(null == $msg) {
            $from->send('wrong request');
        } else {
          $from->send(213);
        }
        /*
        try {
            var_dump($msg);
            switch($msg) {
                case "getAllData":
                    $this->allDataSend($from, '');
                    break;
                case "subscribeToUpdate":
                    break;
            }
        } catch (Exception $e) {
            $from->send('wrong request');
        }*/

    }

    private function allDataSend(ConnectionInterface $conn, $time) {
        //отправить данные за период
    }
}

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new Ws()
        )
    ),
    8889

);

/*
 * client
 *      action: getAllData|subscribeToUpdate
 *      time:   M1|H1|D1
 *
 * */

$server->run();