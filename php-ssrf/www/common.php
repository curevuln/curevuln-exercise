<?php
  function connectdb() {
    $dbname = 'mysql:host=' . $_ENV['DATABASE_HOST'] . ';dbname=sampledb;charset=utf8mb4';
    return new PDO($dbname, 'root', '');
  }
?>
