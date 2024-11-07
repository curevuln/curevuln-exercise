<?php
  function connectDB() {
      $dbname = 'pgsql:host=' . $_ENV['DATABASE_HOST'] . ';dbname=sampledb;port=5432';
      return new PDO($dbname, 'postgres', 'example');
  }
?>
