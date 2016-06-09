<?php

namespace App;

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
            SELECT symbol, date, max(bid) as max_bid, min(bid) as min_bid, DATE_FORMAT(date, '%Y-%m-%d %H:%i') as date_time, date FROM `external_data` WHERE date > DATE_SUB(NOW(), INTERVAL 1 DAY) group by symbol, DATE_FORMAT(date, '%Y-%m-%d %H:%i') ORDER BY date DESC
        ";
        $resultMinMax = $this->_dbInstance->query($queryMinMax)->fetchAll();

        foreach($resultMinMax as $res) {

            $this->_m1[ $res['symbol'] ][ $res['date_time'] ] = array(
                'max_bid' => $res['max_bid'],
                'min_bid' => $res['min_bid'],
                'date' => $res['date_time']
            );
            $date = new \DateTime($res['date'], new \DateTimeZone('UTC'));
            if(empty($this->_lastM1[$res['symbol']]) || $date > $this->_lastM1[$res['symbol']]) {
                $this->_lastM1[$res['symbol']] = clone $date;
                unset($date);
            }
        }

        $queryOpenBid = "
            select symbol, bid, DATE_FORMAT(date, '%Y-%m-%d %H:%i') as date_time FROM `external_data` WHERE date > DATE_SUB(NOW(), INTERVAL 1 DAY) group by symbol, DATE_FORMAT(date, '%Y-%m-%d %H:%i') ORDER BY date ASC
        ";
        $queryCloseBid = "
            select symbol, bid, DATE_FORMAT(date, '%Y-%m-%d %H:%i') as date_time FROM `external_data` WHERE date > DATE_SUB(NOW(), INTERVAL 1 DAY) group by symbol, DATE_FORMAT(date, '%Y-%m-%d %H:%i') ORDER BY date DESC
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

        /*foreach($this->_m1 as $key => $data) {
            echo $key . ' - ' . count($data) . " values \n";
        }*/

        //var_dump(array_keys($this->_m1)); die();
    }

    private function initH1() {
        $queryMinMax = "
            SELECT symbol, date, max(bid) as max_bid, min(bid) as min_bid, DATE_FORMAT(date, '%Y-%m-%d %H:%i') as date_time, date FROM `external_data` WHERE date > DATE_SUB(NOW(), INTERVAL 3 DAY) group by symbol, DATE_FORMAT(date, '%Y-%m-%d %H') ORDER BY date DESC
        ";
        $resultMinMax = $this->_dbInstance->query($queryMinMax)->fetchAll();

        foreach($resultMinMax as $res) {
            $this->_h1[ $res['symbol'] ][ $res['date_time'] ] = array(
                'max_bid' => $res['max_bid'],
                'min_bid' => $res['min_bid'],
                'date' => $res['date_time']
            );
            $date = new \DateTime($res['date'], new \DateTimeZone('UTC'));
            if(empty($this->_lastH1[$res['symbol']]) || $date > $this->_lastH1[$res['symbol']]) {
                $this->_lastH1[$res['symbol']] = clone $date;
                unset($date);
            }
        }

        $queryOpenBid = "
            select symbol, bid, DATE_FORMAT(date, '%Y-%m-%d %H:%i') as date_time FROM `external_data` WHERE date > DATE_SUB(NOW(), INTERVAL 3 DAY) group by symbol, DATE_FORMAT(date, '%Y-%m-%d %H') ORDER BY date ASC
        ";
        $queryCloseBid = "
            select symbol, bid, DATE_FORMAT(date, '%Y-%m-%d %H:%i') as date_time FROM `external_data` WHERE date > DATE_SUB(NOW(), INTERVAL 3 DAY) group by symbol, DATE_FORMAT(date, '%Y-%m-%d %H') ORDER BY date DESC
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
            $date = new \DateTime($res['date'], new \DateTimeZone('UTC'));
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
                $dt = new \DateTime($res['date'], new \DateTimeZone('UTC'));
                if(empty($this->_lastM1[$res['symbol']]) || $dt > $this->_lastM1[$res['symbol']]) {
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
                    $_m1[ $res['symbol'] ][ $res['date_time'] ][ 'openBid' ] = $res['bid'];
                }
            }

            $resultCloseBid = $this->_dbInstance->query($queryCloseBid)->fetchAll();
            foreach($resultCloseBid as $res) {
                if(!empty($_m1[ $res['symbol'] ][ $res['date_time'] ])) {
                    $_m1[ $res['symbol'] ][ $res['date_time'] ][ 'closeBid' ] = $res['bid'];
                }
            }

        }
        foreach($_m1 as $symbol => $data) {
            if(!empty($this->_m1[$symbol])) {
                //соеденит
                $this->_m1[$symbol] = array_merge($this->_m1[$symbol], $_m1[$symbol]);
            } else {
                //присовить
                $this->_m1[$symbol] = $_m1[$symbol];
            }
        }
        return $_m1;
    }

    function updateH1() {
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
                $dt = new \DateTime($res['date'], new \DateTimeZone('UTC'));
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

        foreach($_h1 as $symbol => $data) {
            if(!empty($this->_h1[$symbol])) {
                //соеденит
                $this->_h1[$symbol] = array_merge($this->_h1[$symbol], $_h1[$symbol]);
            } else {
                //присовить
                $this->_h1[$symbol] = $_h1[$symbol];
            }
        }
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
                $dt = new \DateTime($res['date'], new \DateTimeZone('UTC'));
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

        foreach($_d1 as $symbol => $data) {
            if(!empty($this->_d1[$symbol])) {
                //соеденит
                $this->_d1[$symbol] = array_merge($this->_d1[$symbol], $_d1[$symbol]);
            } else {
                //присовить
                $this->_d1[$symbol] = $_d1[$symbol];
            }
        }
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
                $dt = new \DateTime($date, new \DateTimeZone('UTC'));
                $dt->add(new \DateTime('P3D'));
                if($dt < $this->_lastH1) {
                    unset($this->_h1[$sym][$date]);
                }
                unset($dt);
            }
        }
    }
}