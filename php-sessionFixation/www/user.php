<?php
    require 'setting.php';
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
        $stmt->execute();
        $users = $stmt->fetchAll();

        $dbh = connectDB();
        $query = "SELECT shipping.name as name,shipping.addr as addr,product.title as title,shipping.price as price , shipping.num as num FROM shipping JOIN product on product.id = shipping.product_id WHERE user_id = :userid ;";
        $stmt  = $dbh->prepare($query);
        $stmt->bindParam(':userid',$_SESSION['id'],PDO::PARAM_INT);
        $stmt->execute();
        $shippings = $stmt->fetchAll();

    } catch (PDOException $e) {
        echo $e;
    }
    require 'template_user.php';

?>
