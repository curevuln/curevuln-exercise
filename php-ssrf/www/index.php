<?php
  require 'common.php';
  $pdo = connectdb();

  $result = $pdo->query('SELECT * FROM article')->fetchAll(PDO::FETCH_ASSOC);
  require 'templates/index.php'
?>
