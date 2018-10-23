<?php
    require_once 'config.php';

    if ( $_SESSION['id'] == '' ) {

        header('Location: /');
        exit;
    }
    require 'common.php';
    $dbh      = connectDB();
    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        $query  = " SELECT * FROM users WHERE id = :loginId; " ;
        $stmt   = $dbh->prepare($query);
        $stmt->bindParam(':loginId', $_SESSION['id'], PDO::PARAM_INT);
        $stmt->execute();
        $usersData = $stmt->fetchAll();
    } else
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {

        require_once "fileFunction.php";
        fileUpdata ( $dbh );
        $query  = " SELECT * FROM users WHERE id = :loginId; " ;
        $stmt   = $dbh->prepare($query);
        $stmt->bindParam(':loginId', $_SESSION['id'], PDO::PARAM_INT);
        $stmt->execute();
        $usersData = $stmt->fetchAll();

    }
    require 'template_user.php';
?>
