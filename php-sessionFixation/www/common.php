<?php
session_start();

function connectDB() {
    $dbname = 'mysql:host=' . $_ENV['DATABASE_HOST'] . ';dbname=chat;charset=utf8mb4';
    return new PDO($dbname, 'root', '');
}
?>
