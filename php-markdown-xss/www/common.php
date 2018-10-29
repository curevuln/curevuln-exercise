<?php
  require_once 'vendor/autoload.php';

  function connectdb() {
    $db = 'mysql:host=' . $_ENV['DATABASE_HOST'] . ';dbname=blog;charset=utf8mb4';
    return new PDO($db, 'root', '');
  }
?>
