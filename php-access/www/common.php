<?php
session_start();
header("X-XSS-Protection: 0;");

function connectDB() {
    $dbname = 'mysql:host=' . $_ENV['DATABASE_HOST'] . ';dbname=info;charset=utf8mb4';
    return new PDO($dbname, 'root', '');
}
?>
