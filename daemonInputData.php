<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 'on');
$childPid = pcntl_fork();

/*
 * 0 - Дочернему
 * pid - родителю
 * */
if ((bool)$childPid) {

    $fHandle = fopen('./'.basename(__FILE__, '.php').'.pid', 'w');
    fwrite($fHandle, $childPid);
    fclose($fHandle);
    exit;
}

posix_setsid();
$stopServer = false;

while (!$stopServer) {

    if(($stopServer = file_exists(__DIR__ . '/stop')) ) {
        unlink(__DIR__ . '/stop');
        unlink(__DIR__ . '/'. basename(__FILE__, '.php').'.pid');
    }
}