<?php
namespace App;
error_reporting(E_ALL); //Выводим все ошибки и предупреждения

include __DIR__ . "/vendor/autoload.php";
include __DIR__ . "/DataSql.php";

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

declare(ticks = 1);


class Ws implements MessageComponentInterface{
    protected $clients;
    protected $activeClientsConf;

    protected $internalData;

    public function __construct($_sonfig, $db) {
        $this->clients = [];//new \SplObjectStorage;
        $this->activeClientsConf = [];//new \SplObjectStorage;

        $this->internalData = new DataSql($_sonfig, $db);
    }


    function onOpen(ConnectionInterface $conn) {
        echo "new connect\n";
        $this->clients[spl_object_hash($conn)] = $conn;
    }


    function onClose(ConnectionInterface $conn) {
        $clientHash = spl_object_hash($conn);
        unset($this->clients[$clientHash]);

        foreach($this->activeClientsConf as $symbol => $clients) {
            if(FALSE !== ($key = array_search($clientHash, $clients)) ) {
                unset($this->activeClientsConf[$symbol][$key]);
                return;
            }
        }
    }


    function onError(ConnectionInterface $conn, \Exception $e) {
        unset($this->clients[spl_object_hash($conn)]);;
        $conn->close();
    }


    function onMessage(ConnectionInterface $from, $msg) {
        echo count($this->clients) . "активных клиентов\n";
        //{"symbol":"NQ", "time":"M1"}
        //{"symbol":"BRN", "time":"H1"}
        $msg = json_decode($msg);
        if(null == $msg) {
            $from->send('wrong request');
        } else {
            if(!empty($msg->symbol) && !empty($msg->time)) {
                foreach($this->activeClientsConf as $sym => $clients) {
                    /*if($msg->symbol == $sym && !in_array(spl_object_hash($from), $this->activeClientsConf[$sym])) {
                        $this->activeClientsConf[$sym][] = spl_object_hash($from);
                    }*/

                    if($msg->symbol != $sym && $key = array_search(spl_object_hash($from), $this->activeClientsConf[$sym])) {
                        unset($this->activeClientsConf[$sym][$key]);
                    }

                }

                $this->activeClientsConf[$msg->symbol][] = spl_object_hash($from);
                //var_dump($msg->time);
                switch($msg->time) {
                    case 'M1':
                        $data = $this->internalData->getM1($msg->symbol);
                        //echo "sending data: ";
                        //echo $msg->symbol . ": " . count($data);
                        $from->send(json_encode($data));
                        break;
                    case 'H1':
                        $data = $this->internalData->getH1($msg->symbol);
                        $from->send(json_encode($data));
                        break;

                    case 'D1':
                        $data = $this->internalData->getD1($msg->symbol);
                        $from->send(json_encode($data));
                        break;
                    default:
                        echo "smth wrong";
                        break;
                }

            }
        }

    }

    function onUpdateExternalData() {
        $dataM1 = $this->internalData->updateM1();
        $dataH1 = $this->internalData->updateH1();
        $dataD1 = $this->internalData->updateD1();

        foreach($dataM1 as $symbol => $value) {

            $value = json_encode($value);
            if(!empty($this->activeClientsConf[$symbol])) {
                foreach($this->activeClientsConf[$symbol] as $client) {
                    $this->clients[$client]->send($value);
                }
            }

        }
    }

}

class IoServerRedefined extends IoServer {
    public static function factory(MessageComponentInterface $component, $port = 80, $address = '0.0.0.0') {
        $loop   = \React\EventLoop\Factory::create();
        $socket = new \React\Socket\Server($loop);
        $socket->listen($port, $address);

        return new static($component, $socket, $loop);
    }
}

$app = new Ws(include(__DIR__ . '/config.php'), medoo);
$server = IoServerRedefined::factory(
    new HttpServer(
        new WsServer(
            $app
        )
    ),
    8889

);

/*
 * client
 *      symbol: []
 *      time:   M1|H1|D1
 *
 * */

$fHandle = fopen('./'.basename(__FILE__, '.php').'.pid', 'w');
fwrite($fHandle, posix_getpid());
fclose($fHandle);

//pcntl_signal(SIGUSR1, $app->onUpdateExternalData);
pcntl_signal(SIGUSR1, function($sig) use ($app){
    $app->onUpdateExternalData();
});

$server->run();