<?php
  require_once 'vendor/autoload.php';

  function connectDB() {
      $dbname = 'pgsql:host=' . $_ENV['DATABASE_HOST'] . ';dbname=blog;port=5432';
      return new PDO($dbname, 'postgres', 'example');
  }
?>
