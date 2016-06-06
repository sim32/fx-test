<?php
error_reporting(E_ALL); //Выводим все ошибки и предупреждения
include __DIR__ . "/vendor/autoload.php";

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

declare(ticks = 1);

class DataSql {
    private $_m1;
    private $_h1;
    private $_d1;

    private $_lastM1 = false;
    private $_lastH1 = false;
    private $_lastD1 = false;

    private $_dbConnector;
    private $_appConfig;
    private $_dbInstance;

    function __construct($appConfig, $databaseConnector) {
        $this->_appConfig = $appConfig;
        $this->_dbConnector = $databaseConnector;

        $this->_m1 = $this->_h1 = $this->_d1 = [];
        $this->initDb();

        $this->initM1();
        $this->initH1();
        $this->initD1();
    }

    function initDb() {
        try {

            $this->_dbInstance = new $this->_dbConnector([
                'database_type' => $this->_appConfig->database_type,
                'database_name' => $this->_appConfig->database_name,
                'server' => $this->_appConfig->server,
                'username' => $this->_appConfig->username,
                'password' => $this->_appConfig->password,
                'charset' => $this->_appConfig->charset
            ]);

        } catch(Exception $e) {
            echo $e;
        }
    }


    function getM1($sym) {
        return $this->_m1[$sym];
    }

    function getH1($sym) {
        return $this->_h1[$sym];
    }

    function getD1($sym) {
        return $this->_d1[$sym];
    }


    private function initM1() {
        $queryMinMax = "
            SELECT symbol, date, max(bid) as max_bid, min(bid) as min_bid, DATE_FORMAT(date, '%Y-%m-%d %H:%i') as date_time, date FROM `external_data` group by symbol, DATE_FORMAT(date, '%Y-%m-%d %H:%i') ORDER BY date DESC
        ";
        $resultMinMax = $this->_dbInstance->query($queryMinMax)->fetchAll();

        foreach($resultMinMax as $res) {
            $this->_m1[ $res['symbol'] ][ $res['date_time'] ] = array(
                'max_bid' => $res['max_bid'],
                'min_bid' => $res['min_bid'],
                'date' => $res['date_time']
            );
            $date = new DateTime($res['date'], new DateTimeZone('UTC'));
            if(empty($this->_lastM1[$res['symbol']]) || $date > $this->_lastM1[$res['symbol']]) {
                $this->_lastM1[$res['symbol']] = clone $date;
                unset($date);
            }
        }

        $queryOpenBid = "
            select symbol, bid, DATE_FORMAT(date, '%Y-%m-%d %H:%i') as date_time FROM `external_data` group by symbol, DATE_FORMAT(date, '%Y-%m-%d %H:%i') ORDER BY date ASC
        ";
        $queryCloseBid = "
            select symbol, bid, DATE_FORMAT(date, '%Y-%m-%d %H:%i') as date_time FROM `external_data` group by symbol, DATE_FORMAT(date, '%Y-%m-%d %H:%i') ORDER BY date DESC
        ";

        $resultOpenBid = $this->_dbInstance->query($queryOpenBid)->fetchAll();
        foreach($resultOpenBid as $res) {
            if(!empty($this->_m1[ $res['symbol'] ][ $res['date_time'] ])) {
                $this->_m1[ $res['symbol'] ][ $res['date_time'] ][ 'openBid' ] = $res['bid'];
            }
        }

        $resultCloseBid = $this->_dbInstance->query($queryCloseBid)->fetchAll();
        foreach($resultCloseBid as $res) {
            if(!empty($this->_m1[ $res['symbol'] ][ $res['date_time'] ])) {
                $this->_m1[ $res['symbol'] ][ $res['date_time'] ][ 'closeBid' ] = $res['bid'];
            }
        }

    }

    private function initH1() {
        $queryMinMax = "
            SELECT symbol, date, max(bid) as max_bid, min(bid) as min_bid, DATE_FORMAT(date, '%Y-%m-%d %H:%i') as date_time, date FROM `external_data` group by symbol, DATE_FORMAT(date, '%Y-%m-%d %H') ORDER BY date DESC
        ";
        $resultMinMax = $this->_dbInstance->query($queryMinMax)->fetchAll();

        foreach($resultMinMax as $res) {
            $this->_h1[ $res['symbol'] ][ $res['date_time'] ] = array(
                'max_bid' => $res['max_bid'],
                'min_bid' => $res['min_bid'],
                'date' => $res['date_time']
            );
            $date = new DateTime($res['date'], new DateTimeZone('UTC'));
            if(empty($this->_lastH1[$res['symbol']]) || $date > $this->_lastH1[$res['symbol']]) {
                $this->_lastH1[$res['symbol']] = clone $date;
                unset($date);
            }
        }

        $queryOpenBid = "
            select symbol, bid, DATE_FORMAT(date, '%Y-%m-%d %H:%i') as date_time FROM `external_data` group by symbol, DATE_FORMAT(date, '%Y-%m-%d %H') ORDER BY date ASC
        ";
        $queryCloseBid = "
            select symbol, bid, DATE_FORMAT(date, '%Y-%m-%d %H:%i') as date_time FROM `external_data` group by symbol, DATE_FORMAT(date, '%Y-%m-%d %H') ORDER BY date DESC
        ";

        $resultOpenBid = $this->_dbInstance->query($queryOpenBid)->fetchAll();
        foreach($resultOpenBid as $res) {
            if(!empty($this->_h1[ $res['symbol'] ][ $res['date_time'] ])) {
                $this->_h1[ $res['symbol'] ][ $res['date_time'] ][ 'openBid' ] = $res['bid'];
            }
        }

        $resultCloseBid = $this->_dbInstance->query($queryCloseBid)->fetchAll();
        foreach($resultCloseBid as $res) {
            if(!empty($this->_h1[ $res['symbol'] ][ $res['date_time'] ])) {
                $this->_h1[ $res['symbol'] ][ $res['date_time'] ][ 'closeBid' ] = $res['bid'];
            }
        }

    }

    private function initD1() {
        $queryMinMax = "
            SELECT symbol, date, max(bid) as max_bid, min(bid) as min_bid, DATE_FORMAT(date, '%Y-%m-%d %H:%i') as date_time, date FROM `external_data` group by symbol, DATE_FORMAT(date, '%Y-%m-%d') ORDER BY date DESC
        ";
        $resultMinMax = $this->_dbInstance->query($queryMinMax)->fetchAll();

        foreach($resultMinMax as $res) {
            $this->_d1[ $res['symbol'] ][ $res['date_time'] ] = array(
                'max_bid' => $res['max_bid'],
                'min_bid' => $res['min_bid'],
                'date' => $res['date_time']
            );
            $date = new DateTime($res['date'], new DateTimeZone('UTC'));
            if(empty($this->_lastD1[$res['symbol']]) || $date > $this->_lastD1[$res['symbol']]) {
                $this->_lastD1[$res['symbol']] = clone $date;
                unset($date);
            }
        }

        $queryOpenBid = "
            select symbol, bid, DATE_FORMAT(date, '%Y-%m-%d %H:%i') as date_time FROM `external_data` group by symbol, DATE_FORMAT(date, '%Y-%m-%d') ORDER BY date ASC
        ";
        $queryCloseBid = "
            select symbol, bid, DATE_FORMAT(date, '%Y-%m-%d %H:%i') as date_time FROM `external_data` group by symbol, DATE_FORMAT(date, '%Y-%m-%d') ORDER BY date DESC
        ";

        $resultOpenBid = $this->_dbInstance->query($queryOpenBid)->fetchAll();
        foreach($resultOpenBid as $res) {
            if(!empty($this->_d1[ $res['symbol'] ][ $res['date_time'] ])) {
                $this->_d1[ $res['symbol'] ][ $res['date_time'] ][ 'openBid' ] = $res['bid'];
            }
        }

        $resultCloseBid = $this->_dbInstance->query($queryCloseBid)->fetchAll();
        foreach($resultCloseBid as $res) {
            if(!empty($this->_d1[ $res['symbol'] ][ $res['date_time'] ])) {
                $this->_d1[ $res['symbol'] ][ $res['date_time'] ][ 'closeBid' ] = $res['bid'];
            }
        }

    }

    function updateM1() {
        foreach($this->_lastM1 as $sym => $date) {

            $queryMinMax = "
                SELECT symbol, date, max(bid) as max_bid, min(bid) as min_bid, DATE_FORMAT(date, '%Y-%m-%d %H:%i') as date_time, date
                FROM `external_data`
                WHERE symbol = '". $sym ."' AND date > '". $date->format('Y-m-d H:i') ."'
                GROUP BY symbol, DATE_FORMAT(date, '%Y-%m-%d %H:%i') ORDER BY date DESC
            ";

            $resultMinMax = $this->_dbInstance->query($queryMinMax)->fetchAll();

            foreach($resultMinMax as $res) {
                $_m1[ $res['symbol'] ][ $res['date_time'] ] = array(
                    'max_bid' => $res['max_bid'],
                    'min_bid' => $res['min_bid'],
                    'date' => $res['date_time']
                );
                $dt = new DateTime($res['date'], new DateTimeZone('UTC'));
                if(empty($this->_lastM1[$res['symbol']]) || $date > $this->_lastM1[$res['symbol']]) {
                    $this->_lastM1[$res['symbol']] = clone $dt;
                    unset($dt);
                }
            }


            $queryOpenBid = "
                SELECT symbol, bid, DATE_FORMAT(date, '%Y-%m-%d %H:%i') as date_time
                FROM `external_data`
                WHERE symbol = '". $sym ."' AND date > '". $date->format('Y-m-d H:i') ."'
                GROUP BY symbol, DATE_FORMAT(date, '%Y-%m-%d %H:%i') ORDER BY date ASC
            ";
            $queryCloseBid = "
                SELECT symbol, bid, DATE_FORMAT(date, '%Y-%m-%d %H:%i') as date_time
                FROM `external_data`
                WHERE symbol = '". $sym ."' AND date > '". $date->format('Y-m-d H:i') ."'
                GROUP BY symbol, DATE_FORMAT(date, '%Y-%m-%d %H:%i') ORDER BY date DESC
            ";

            $resultOpenBid = $this->_dbInstance->query($queryOpenBid)->fetchAll();
            foreach($resultOpenBid as $res) {
                if(!empty($_m1[ $res['symbol'] ][ $res['date_time'] ])) {
                    $this->_m1[ $res['symbol'] ][ $res['date_time'] ][ 'openBid' ] = $res['bid'];
                }
            }

            $resultCloseBid = $this->_dbInstance->query($queryCloseBid)->fetchAll();
            foreach($resultCloseBid as $res) {
                if(!empty($_m1[ $res['symbol'] ][ $res['date_time'] ])) {
                    $_m1[ $res['symbol'] ][ $res['date_time'] ][ 'closeBid' ] = $res['bid'];
                }
            }

        }
        $this->_m1 = array_merge($this->_m1, $_m1);
        return $_m1;
    }

    function updateH1() {
        var_dump($this->_lastH1);
        foreach($this->_lastH1 as $sym => $date) {

            $queryMinMax = "
                SELECT symbol, date, max(bid) as max_bid, min(bid) as min_bid, DATE_FORMAT(date, '%Y-%m-%d %H:%i') as date_time, date
                FROM `external_data`
                WHERE symbol = '". $sym ."' AND date > '". $date->format('Y-m-d H:i') ."'
                GROUP BY symbol, DATE_FORMAT(date, '%Y-%m-%d %H') ORDER BY date DESC
            ";

            $resultMinMax = $this->_dbInstance->query($queryMinMax)->fetchAll();

            foreach($resultMinMax as $res) {
                $_h1[ $res['symbol'] ][ $res['date_time'] ] = array(
                    'max_bid' => $res['max_bid'],
                    'min_bid' => $res['min_bid'],
                    'date' => $res['date_time']
                );
                $dt = new DateTime($res['date'], new DateTimeZone('UTC'));
                if(empty($this->_lastH1[$res['symbol']]) || $dt > $this->_lastH1[$res['symbol']]) {
                    $this->_lastH1[$res['symbol']] = clone $date;
                    unset($dt);
                }
            }


            $queryOpenBid = "
                SELECT symbol, bid, DATE_FORMAT(date, '%Y-%m-%d %H:%i') as date_time
                FROM `external_data`
                WHERE symbol = '". $sym ."' AND date > '". $date->format('Y-m-d H:i') ."'
                GROUP BY symbol, DATE_FORMAT(date, '%Y-%m-%d %H') ORDER BY date ASC
            ";
            $queryCloseBid = "
                SELECT symbol, bid, DATE_FORMAT(date, '%Y-%m-%d %H:%i') as date_time
                FROM `external_data`
                WHERE symbol = '". $sym ."' AND date > '". $date->format('Y-m-d H:i') ."'
                GROUP BY symbol, DATE_FORMAT(date, '%Y-%m-%d %H') ORDER BY date DESC
            ";

            $resultOpenBid = $this->_dbInstance->query($queryOpenBid)->fetchAll();
            foreach($resultOpenBid as $res) {
                if(!empty($_h1[ $res['symbol'] ][ $res['date_time'] ])) {
                    $_h1[ $res['symbol'] ][ $res['date_time'] ][ 'openBid' ] = $res['bid'];
                }
            }

            $resultCloseBid = $this->_dbInstance->query($queryCloseBid)->fetchAll();
            foreach($resultCloseBid as $res) {
                if(!empty($_h1[ $res['symbol'] ][ $res['date_time'] ])) {
                    $_h1[ $res['symbol'] ][ $res['date_time'] ][ 'closeBid' ] = $res['bid'];
                }
            }

        }

        $this->_lastH1 = array_merge($this->_lastH1, $_h1);
        return $_h1;
    }

    function updateD1() {
        foreach($this->_lastD1 as $sym => $date) {

            $queryMinMax = "
                SELECT symbol, date, max(bid) as max_bid, min(bid) as min_bid, DATE_FORMAT(date, '%Y-%m-%d %H:%i') as date_time, date
                FROM `external_data`
                WHERE symbol = '". $sym ."' AND date > '". $date->format('Y-m-d H:i') ."'
                GROUP BY symbol, DATE_FORMAT(date, '%Y-%m-%d') ORDER BY date DESC
            ";

            $resultMinMax = $this->_dbInstance->query($queryMinMax)->fetchAll();

            foreach($resultMinMax as $res) {
                $_d1[ $res['symbol'] ][ $res['date_time'] ] = array(
                    'max_bid' => $res['max_bid'],
                    'min_bid' => $res['min_bid'],
                    'date' => $res['date_time']
                );
                $dt = new DateTime($res['date'], new DateTimeZone('UTC'));
                if(empty($this->_lastD1[$res['symbol']]) || $dt > $this->_lastD1[$res['symbol']]) {
                    $this->_lastD1[$res['symbol']] = clone $date;
                    unset($dt);
                }
            }


            $queryOpenBid = "
                SELECT symbol, bid, DATE_FORMAT(date, '%Y-%m-%d %H:%i') as date_time
                FROM `external_data`
                WHERE symbol = '". $sym ."' AND date > '". $date->format('Y-m-d H:i') ."'
                GROUP BY symbol, DATE_FORMAT(date, '%Y-%m-%d') ORDER BY date ASC
            ";
            $queryCloseBid = "
                SELECT symbol, bid, DATE_FORMAT(date, '%Y-%m-%d %H:%i') as date_time
                FROM `external_data`
                WHERE symbol = '". $sym ."' AND date > '". $date->format('Y-m-d H:i') ."'
                GROUP BY symbol, DATE_FORMAT(date, '%Y-%m-%d') ORDER BY date DESC
            ";

            $resultOpenBid = $this->_dbInstance->query($queryOpenBid)->fetchAll();
            foreach($resultOpenBid as $res) {
                if(!empty($_d1[ $res['symbol'] ][ $res['date_time'] ])) {
                    $_d1[ $res['symbol'] ][ $res['date_time'] ][ 'openBid' ] = $res['bid'];
                }
            }

            $resultCloseBid = $this->_dbInstance->query($queryCloseBid)->fetchAll();
            foreach($resultCloseBid as $res) {
                if(!empty($_d1[ $res['symbol'] ][ $res['date_time'] ])) {
                    $_d1[ $res['symbol'] ][ $res['date_time'] ][ 'closeBid' ] = $res['bid'];
                }
            }

        }
        $this->_d1 = array_merge($this->_d1, $_d1);
        return $_d1;
    }

    function cleanCache() {
        foreach($this->_m1 as $sym => $tick) {
            foreach($tick as $date => $value) {
                $dt = new DateTime($date, new DateTimeZone('UTC'));
                $dt->add(new DateTime('P1D'));
                if($dt < $this->_lastM1) {
                    unset($this->_m1[$sym][$date]);
                }
                unset($dt);
            }
        }

        foreach($this->_h1 as $sym => $tick) {
            foreach($tick as $date => $value) {
                $dt = new DateTime($date, new DateTimeZone('UTC'));
                $dt->add(new DateTime('P3D'));
                if($dt < $this->_lastH1) {
                    unset($this->_h1[$sym][$date]);
                }
                unset($dt);
            }
        }
    }
}


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
            if(!empty($msg->symbol) && !empty($msg->time)) {
                foreach($this->activeClientsConf as $sym => $clients) {
                    if($msg->symbol == $sym && !in_array(spl_object_hash($from), $this->activeClientsConf[$sym])) {
                        $this->activeClientsConf[$sym][] = spl_object_hash($from);
                    }

                    if($msg->symbol != $sym && $key = array_search(spl_object_hash($from), $this->activeClientsConf[$sym])) {
                        unset($this->activeClientsConf[$sym][$key]);
                    }
                }

                switch($msg->time) {
                    case 'M1':
                        $data = $this->internalData->getM1($msg->symbol);
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
            foreach($this->activeClientsConf[$symbol] as $client) {
                $this->clients[$client]->send($value);
            }
        }
    }

}

class IoServerRedefined extends IoServer {
    public static function factory(MessageComponentInterface $component, $port = 80, $address = '0.0.0.0') {
        $loop   = React\EventLoop\Factory::create();
        $socket = new React\Socket\Server($loop);
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