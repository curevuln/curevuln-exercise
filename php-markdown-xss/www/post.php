<?php
  require 'common.php';

  $pdo = connectdb();

  if (isset($_POST['title']) && $_POST['title'] !== '') {
    $stmt = $pdo->prepare("INSERT INTO article (title, content) VALUES (:title, :content)");
    try {
      $converter = new \cebe\markdown\MarkdownExtra();
      $html = $converter->parse($_POST['content']);

      $stmt->bindValue(':title', $_POST['title'], PDO::PARAM_STR);
      $stmt->bindValue(':content', $html, PDO::PARAM_STR);
      $stmt->execute();
    } catch (PDOException $e) {
      header('Content-Type: text/plain; charset=UTF-8', true, 500);
      exit($e->getMessage());
    }
  }
  require 'templates/post.php'
?>
