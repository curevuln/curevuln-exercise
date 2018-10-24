<?php
  require 'common.php';
  $pdo = connectdb();

  if (isset($_POST['title']) && $_POST['title'] !== '') {
    $stmt = $pdo -> prepare("INSERT INTO article (title, content) VALUES (:title, :content)");
    try {
      $stmt->bindValue(':title', $_POST['title'], PDO::PARAM_STR);
      $stmt->bindValue(':content', $_POST['content'], PDO::PARAM_STR);
      $stmt->execute();
      header('Location: /' , true, 301);
    } catch (PDOException $e) {
      header('Content-Type: text/plain; charset=UTF-8', true, 500);
      exit($e->getMessage());
    }
  }
  require 'templates/post.php'
?>
