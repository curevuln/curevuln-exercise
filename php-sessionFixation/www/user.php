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
        $dbh = connectDB();
        $query = "SELECT * FROM users WHERE id = :userid ;";
        $stmt  = $dbh->prepare($query);
        $stmt->bindParam(':userid',$_SESSION['id'],PDO::PARAM_INT);
        $users = $dbh->query($query)->fetchAll();
    } catch (PDOException $e) {
        echo $e;
    }
    require 'template_user.php';

?>
