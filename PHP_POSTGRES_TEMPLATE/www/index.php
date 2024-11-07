<?php
    session_start();
    header("X-XSS-Protection: 0;");

    $dbname = 'pgsql:host=' . $_ENV['DATABASE_HOST'] . ';dbname=chat;port=5432';
    $dbh = new PDO($dbname, 'postgres', 'example');
    $query    = 'SELECT * FROM blog';
    $contacts = $dbh->query($query)->fetchAll();
    var_dump($contacts);
?>
