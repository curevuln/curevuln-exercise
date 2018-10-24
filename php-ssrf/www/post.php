<?php
  require 'common.php';
  require 'webhook.php';

  $pdo = connectdb();

  if (isset($_POST['title']) && $_POST['title'] !== '') {
    $stmt = $pdo->prepare("INSERT INTO article (title, content) VALUES (:title, :content)");
    try {
      $stmt->bindValue(':title', $_POST['title'], PDO::PARAM_STR);
      $stmt->bindValue(':content', $_POST['content'], PDO::PARAM_STR);
      $stmt->execute();

      # webhook
      try {
        $stmt = $pdo->query("SELECT url FROM webhook LIMIT 1");
        $result = $stmt->fetch();

        $response = webhook($result['url'], $_POST['title']);
      } catch (PDOException $e) {
        throw $e;
      }
    } catch (PDOException $e) {
      header('Content-Type: text/plain; charset=UTF-8', true, 500);
      exit($e->getMessage());
    }
  }
  require 'templates/post.php'
?>
