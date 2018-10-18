<?php
    session_start();
    header("X-XSS-Protection: 0;");

    $dbname = 'mysql:host=' . $_ENV['DATABASE_HOST'] . ';dbname=chat;charset=utf8mb4';
    $dbh = new PDO($dbname, 'root', '');
    $query    = 'SELECT * FROM blog';
    $contacts = $dbh->query($query)->fetchAll();
    var_dump($contacts);
?>
