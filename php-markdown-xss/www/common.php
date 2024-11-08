<?php
  require_once 'vendor/autoload.php';

  function connectDB() {
      $dbname = 'pgsql:host=' . $_ENV['DATABASE_HOST'] . ';dbname=blog;port=' . $_ENV['DATABASE_PORT'];
      return new PDO($dbname, 'postgres', 'example');
  }
?>
