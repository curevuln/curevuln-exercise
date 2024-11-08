<?php
function connectDB() {
    $dbname = 'pgsql:host=' . $_ENV['DATABASE_HOST'] . ';dbname=member;port=' . $_ENV['DATABASE_PORT'];
    return new PDO($dbname, 'postgres', 'example');
}
?>
