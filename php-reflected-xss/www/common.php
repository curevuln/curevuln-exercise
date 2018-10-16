<?php
header("X-XSS-Protection: 0;");

function connectDB() {
    $dbname = 'mysql:host=' . $_ENV['DATABASE_HOST'] . ';dbname=shop;charset=utf8mb4';
    return new PDO($dbname, 'root', '');
}
?>
