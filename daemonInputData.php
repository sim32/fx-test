<?php
$child_pid = pcntl_fork();

/*
 * 0 - Дочернему
 * pid - родителю
 * */
if ((bool)$child_pid) {

    print $child_pid . "\n";
    exit;
}

posix_setsid();
$stopServer = false;

while (!$stopServer) {



    if(file_exists(__DIR__ . '/stop')) {
        $stopServer = true;
        unlink(__DIR__ . '/stop');
        exit;
    }
}