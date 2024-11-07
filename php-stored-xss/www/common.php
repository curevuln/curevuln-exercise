<?php
session_start();
header("X-XSS-Protection: 0;");

function connectDB() {
    $dbname = 'pgsql:host=' . $_ENV['DATABASE_HOST'] . ';dbname=chat;port=5432';
    return new PDO($dbname, 'postgres', 'example');
}
?>
