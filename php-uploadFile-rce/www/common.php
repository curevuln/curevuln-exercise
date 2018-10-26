<?php
function connectDB() {
    $dbname = 'mysql:host=' . $_ENV['DATABASE_HOST'] . ';dbname=member;charset=utf8mb4';
    return new PDO($dbname, 'root', '');
}
?>
