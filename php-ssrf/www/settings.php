<?php
  require 'common.php';
  $pdo = connectdb();

  if (isset($_POST['url']) && $_POST['url'] !== '') {
    # id = 1 決め打ちで
    $stmt = $pdo->prepare("update webhook set url = :url where id = 1");
    try {
      $stmt->bindValue(':url', $_POST['url'], PDO::PARAM_STR);
      $stmt->execute();
    } catch (PDOException $e) {
      header('Content-Type: text/plain; charset=UTF-8', true, 500);
      exit($e->getMessage());
    }
  }

  $stmt = $pdo->query("SELECT * FROM webhook LIMIT 1");
  $result = $stmt->fetch();
  require 'templates/settings.php';
?>
