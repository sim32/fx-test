<?php
namespace App;
//error_reporting(E_ALL); //Выводим все ошибки и предупреждения

include __DIR__ . "/vendor/autoload.php";
include __DIR__ . "/DataSql.php";

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\Wamp\Topic;
use Ratchet\Wamp\WampServerInterface;
use Ratchet\WebSocket\WsServer;
use Ratchet\Wamp\WampServer;

declare(ticks = 1);


$childPid = pcntl_fork();

/*
 * 0 - Дочернему
 * pid - родителю
 * */


if ((bool)$childPid) {

    $fHandle = fopen('./'.basename(__FILE__, '.php').'.pid', 'w');
    fwrite($fHandle, $childPid);
    fclose($fHandle);
    echo $childPid . "\n";
    exit;
}

posix_setsid();

class Ws implements MessageComponentInterface, WampServerInterface{
    protected $clients;
    protected $activeClientsConf;

    protected $internalData;

    public function __construct($_sonfig, $db) {
        $this->clients = [];//new \SplObjectStorage;
        $this->activeClientsConf = [];//new \SplObjectStorage;

        $this->internalData = new DataSql($_sonfig, $db);
    }


    function onOpen(ConnectionInterface $conn) {
        //echo "new connect\n";
        $this->clients[spl_object_hash($conn)] = $conn;
    }


    function onClose(ConnectionInterface $conn) {
        //echo "onClose\n";
        $clientHash = spl_object_hash($conn);
        unset($this->clients[$clientHash]);

        foreach($this->activeClientsConf as $symbol => $clients) {
            if(FALSE !== ($key = array_search($clientHash, $clients)) ) {
                unset($this->activeClientsConf[$symbol][$key]);
                break;
            }
        }
        //echo count($this->clients) . " активных клиентов\n";
    }


    function onError(ConnectionInterface $conn, \Exception $e) {
        //echo "onErro\n";
        $objHash = spl_object_hash($conn);

        unset($this->clients[$objHash]);
        foreach($this->activeClientsConf as $symbol => $clients) {
            if(FALSE !== ($key = array_search($objHash, $clients))) {
                unset($this->activeClientsConf[$symbol][$key]);
                break;
            }
        }

        $conn->close();
        //echo count($this->clients) . " активных клиентов\n";
    }


    function onMessage(ConnectionInterface $from, $msg) {
        //echo "onMess\n";
        //echo count($this->clients) . " активных клиентов\n";
        //{"symbol":"NQ", "time":"M1"}
        //{"symbol":"BRN", "time":"H1"}
        if($msg == 'update') {
            $this->onUpdateExternalData();
        } else {
            $msg = json_decode($msg);
            if(null == $msg) {
                $from->send('wrong request');
            } else {
                if(!empty($msg->symbol) && !empty($msg->time)) {
                    //если такой клиент уже подписан на что-то другое удаляем его из подписчиков
                    foreach($this->activeClientsConf as $sym => $clients) {
                        if($msg->symbol != $sym && FALSE !== ($key = array_search(spl_object_hash($from), $this->activeClientsConf[$sym])) ) {
                            unset($this->activeClientsConf[$sym][$key]);
                        }
                    }

                    //добавляем в активных подписчиков
                    $this->activeClientsConf[$msg->symbol][] = spl_object_hash($from);
                    //var_dump($msg->time);
                    switch($msg->time) {
                        case 'M1':
                            $data = $this->internalData->getM1($msg->symbol);
                            $data = array_values($data);
                            $from->send(json_encode($data));
                            break;
                        case 'H1':
                            $data = $this->internalData->getH1($msg->symbol);
                            $data = array_values($data);
                            $from->send(json_encode($data));
                            break;

                        case 'D1':
                            $data = $this->internalData->getD1($msg->symbol);
                            $data = array_values($data);
                            $from->send(json_encode($data));
                            break;
                        default:
                            //echo "smth wrong";
                            break;
                    }

                }
            }
        }

    }

    function onUpdateExternalData() {
        //echo "upd data\n";
        $dataM1 = $this->internalData->updateM1();
        $dataH1 = $this->internalData->updateH1();
        $dataD1 = $this->internalData->updateD1();

        foreach($dataM1 as $symbol => $value) {
            $value = array_values($value);
            $value = json_encode($value);

            if(!empty($this->activeClientsConf[$symbol])) {
                //если есть активные подписчики шлём данные
                foreach($this->activeClientsConf[$symbol] as $key => $client) {
                    if(empty($this->clients[$client]) || !($this->clients[$client] instanceof ConnectionInterface)) {
                        unset($this->activeClientsConf[$symbol][$key]);
                    } else {
                        $this->clients[$client]->send($value);
                        /*
                        $self = $this;
                        $loop->futureTick(function() use ($self, $client, $value){
                            $self->clients[$client]->send($value);
                            echo "atad dpu\n";
                        });*/
                    }
                }
            }

        }

    }

    /**
     * An RPC call has been received
     * @param \Ratchet\ConnectionInterface $conn
     * @param string $id The unique ID of the RPC, required to respond to
     * @param string|Topic $topic The topic to execute the call against
     * @param array $params Call parameters received from the client
     */
    function onCall(ConnectionInterface $conn, $id, $topic, array $params)
    {
        //echo "onCall\n";
        // TODO: Implement onCall() method.
    }

    /**
     * A request to subscribe to a topic has been made
     * @param \Ratchet\ConnectionInterface $conn
     * @param string|Topic $topic The topic to subscribe to
     */
    function onSubscribe(ConnectionInterface $conn, $topic)
    {
        //echo "onSubs\n";
        // TODO: Implement onSubscribe() method.
    }

    /**
     * A request to unsubscribe from a topic has been made
     * @param \Ratchet\ConnectionInterface $conn
     * @param string|Topic $topic The topic to unsubscribe from
     */
    function onUnSubscribe(ConnectionInterface $conn, $topic)
    {
        //echo "onUnsBSCR\n";
        // TODO: Implement onUnSubscribe() method.
    }

    /**
     * A client is attempting to publish content to a subscribed connections on a URI
     * @param \Ratchet\ConnectionInterface $conn
     * @param string|Topic $topic The topic the user has attempted to publish to
     * @param string $event Payload of the publish
     * @param array $exclude A list of session IDs the message should be excluded from (blacklist)
     * @param array $eligible A list of session Ids the message should be send to (whitelist)
     */
    function onPublish(ConnectionInterface $conn, $topic, $event, array $exclude, array $eligible)
    {
        //echo "onPublish\n";
        // TODO: Implement onPublish() method.
    }
}


    $app = new Ws(include(__DIR__ . '/config.php'), medoo);
    $loop   = \React\EventLoop\Factory::create();

    // Listen for the web server to make a ZeroMQ push after an ajax request
    /*$context = new \React\ZMQ\Context($loop);
    $pull = $context->getSocket(\ZMQ::SOCKET_PULL);
    $pull->bind('tcp://127.0.0.1:5555'); // Binding to 127.0.0.1 means the only client that can connect is itself
    //$pull->on('message', array($pusher, 'onBlogEntry'));
    $pull->on('message', function() use($app){
        $app->onUpdateExternalData();
    });*/

    // Set up our WebSocket server for clients wanting real-time updates
    $webSock = new \React\Socket\Server($loop);
    $webSock->listen(8889, '0.0.0.0'); // Binding to 0.0.0.0 means remotes can connect
    $webServer = new \Ratchet\Server\IoServer(
        new \Ratchet\Http\HttpServer(
            new \Ratchet\WebSocket\WsServer(
                $app
            )
        ),
        $webSock
    );

    $loop->run();


$fHandle = fopen('./'.basename(__FILE__, '.php').'.pid', 'w');
fwrite($fHandle, posix_getpid());
fclose($fHandle);