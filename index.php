<?php

// Construct the MongoDB Manager
$manager = new MongoDB\Driver\Manager("mongodb://localhost:27017");

// Construct and execute the listDatabases command
$listdatabases = new MongoDB\Driver\Command(["listDatabases" => 1]);
$result        = $manager->executeCommand("admin", $listdatabases);

/* The command returns a single result document, which contains the information
 * for all databases in a "databases" array field. */
$databases     = current($result->toArray());

foreach ($databases["databases"] as $database) {
    echo $database->name, "\n";

    // Construct and execute the listCollections command for each database
    $listcollections = new MongoDB\Driver\Command(["listCollections" => 1]);
    $result          = $manager->executeCommand($database->name, $listcollections);

    /* The command returns a cursor, which we can iterate on to access
     * information for each collection. */
    $collections     = $result->toArray();

    foreach ($collections as $collection) {
        echo "\t * ", $collection->name, "\n";
    }
}