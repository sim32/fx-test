<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 'on');
require_once __DIR__ . "/vendor/autoload.php";
$conf = include_once(__DIR__ . '/config.php');

$database = new medoo([
    // required
    'database_type' => $conf->database_type,
    'database_name' => $conf->database_name,
    'server' => $conf->server,
    'username' => $conf->username,
    'password' => $conf->password,
    'charset' => $conf->charset]);


$symbols = $database->query('SELECT distinct symbol FROM external_data')->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" type="text/css" href="/src/styles.css">
    <meta content="charset=utf-8" http-equiv="Content-Type">
</head>
<body>
<div class="container">
    <div id="chartContainer" style="width:100%; height:300px;"></div>
    <div class="buttons">
        <span id="btnStart" class="button">start</span>
        <span id="btnStop" class="button">stop</span>
    </div>
    <div class="filters">
        <div class="filter">
            <select id="selSymbol">
                <?foreach($symbols as $symbol):?>
                    <option><?=$symbol[0]?></option>
                <?endforeach?>
            </select>
        </div>

        <div class="filter">
            <select id="selTime">
                <option>D1</option>
                <option>H1</option>
                <option>M1</option>
            </select>
        </div>
    </div>
</div>
</body>
<script src="/src/canvasjs.min.js"></script>
<script src="/src/formater.js"></script>
<script src="/src/script.js"></script>

<script type="text/javascript">
    //---------------------------------------------------->
/*
    (function(){
        document.getElementById("btnStart").addEventListener('click', function (evt) {
            alert(456);
        })
        var serverData = [
            [
                {"max_bid":"4530.75","min_bid":"4527.25","date":"2016-06-07 17:37","openBid":"4525.25","closeBid":"4527.2"},
                {"max_bid":"4525.75","min_bid":"4527.25","date":"2016-06-07 17:38","openBid":"4528.25","closeBid":"4527.2"},
                {"max_bid":"4527.75","min_bid":"4527.25","date":"2016-06-07 17:39","openBid":"4527.25","closeBid":"4527.2"},
            ],
            {"max_bid":"4527.75","min_bid":"4527.25","date":"2016-06-07 17:40","openBid":"4527.25","closeBid":"4527.2"},
            {"max_bid":"4527.75","min_bid":"4527.25","date":"2016-06-07 17:40","openBid":"4527.25","closeBid":"4527.2"},
            {"max_bid":"4527.75","min_bid":"4527.25","date":"2016-06-07 17:40","openBid":"4527.25","closeBid":"4527.4"},
            {"max_bid":"4527.75","min_bid":"4527.25","date":"2016-06-07 17:41","openBid":"4527.25","closeBid":"4527.3"},
            [
                {"max_bid":"4530.75","min_bid":"4527.25","date":"2016-06-07 17:42","openBid":"4527.25","closeBid":"4527.2"},
                {"max_bid":"4525.75","min_bid":"4527.25","date":"2016-06-07 17:43","openBid":"4527.25","closeBid":"4527.2"},
                {"max_bid":"4527.75","min_bid":"4527.25","date":"2016-06-07 17:44","openBid":"4527.25","closeBid":"4527.2"},
            ],
        ]

        //---------------------------------------------------->

        var chartParams = {
            title:{
                text: "CNX Nifty's Monthly Stock Prices",
            },
            exportEnabled: true,
            axisY: {
                includeZero: false,
                prefix: "$",
            },
            axisX: {
                valueFormatString: "HH-mm",
            },
            data: [
                {
                    type: "candlestick",
                    risingColor: "#17EFDA",
                    dataPoints: []
                }
            ]
        }

        var chart = new CanvasJS.Chart("chartContainer", chartParams);
        var formater = new Formater(chart);

        setInterval(function(){
            var data = serverData.shift();

            formater.received(data);
        }, 1000);
    }())*/

</script>
</html>



