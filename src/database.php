<?php 
    $host = 'localhost';
    $username = 'root';
    $password = '';
    $mainDatabaseName = 'testpol';
    $connection = new mysqli($host, $username, $password, $mainDatabaseName);
    if ($connection->connect_error) exit("ERROR. Failed to connect to database."); // exit from the session if unable to connect to database 
?>