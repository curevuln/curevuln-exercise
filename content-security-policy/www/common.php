<?php
header("X-XSS-Protection: 0;");

function connectDB() {
    $dbname = 'pgsql:host=' . $_ENV['DATABASE_HOST'] . ';dbname=shop;port=5432';
    return new PDO($dbname, 'postgres', 'example');
}
?>
