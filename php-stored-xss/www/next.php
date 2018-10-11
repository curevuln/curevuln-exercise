<?php
    session_start();
    header("X-XSS-Protection: 0;");

    if ($_SESSION['id'] == '') {
        header("Location: / ");
        exit();
    }

    require 'common.php';
    $dbh = connectDB();
    $id = Null;
    try {
        $query = "INSERT INTO `contact` (`id`, `title`, `content`) VALUES ( :id, :title, :content );";
        $stmt  = $dbh->prepare($query);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':title', $_GET['title'], PDO::PARAM_STR);
        $stmt->bindParam(':content', $_GET['content'], PDO::PARAM_STR);
        $stmt->execute();
    } catch (PDOException $e) {
        echo $e;
    }
    require 'template_next.php';

?>
