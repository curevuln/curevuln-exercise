<?php
    require 'common.php';
    $dbh = connectDB();
    echo $_GET['title'];
    try {
        $query = "INSERT INTO `contact` (`title`, `content`) VALUES ( ':title' , ':content' );";
        $stmt  = $dbh->prepare($query);
        $stmt->bindParam(':title', $_GET['title'], PDO::PARAM_STR);
        $stmt->bindParam(':content', $_GET['content'], PDO::PARAM_STR);
        $stmt->execute();
    } catch (PDOException $e) {

    }
    require 'template_next.php';
?>
