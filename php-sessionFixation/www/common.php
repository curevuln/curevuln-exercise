<?php
require 'setting.php';

function connectDB() {
    $dbname = 'pgsql:host=' . $_ENV['DATABASE_HOST'] . ';dbname=shop;port=' . $_ENV['DATABASE_PORT'];
    return new PDO($dbname, 'postgres', 'example');
}
?>
