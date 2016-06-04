<?php
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);

require_once __DIR__ . "/vendor/autoload.php";

$collection = (new MongoDB\Client)->demo->persons;

$result = $collection->insertOne(['a'=>"123"]);

$person = $collection->find();
echo '<pre>';
var_dump($person);


