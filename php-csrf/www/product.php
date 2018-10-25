<?php
require 'setting.php';
if ($_SESSION['id'] == '') {
    header("Location: / ");
    exit();
}
require 'common.php';
$id = Null;
try {

    $dbh = connectDB();
    $query = "SELECT * FROM product WHERE id = :productID ;";
    $stmt  = $dbh->prepare($query);
    $stmt->bindParam(':productID',$_GET['id'],PDO::PARAM_STR);
    $stmt->execute();
    $products = $stmt->fetchAll();

    $dbh = connectDB();
    $query = "SELECT * FROM review WHERE product_id = :productID ;";
    $stmt  = $dbh->prepare($query);
    $stmt->bindParam(':productID',$_GET['id'],PDO::PARAM_STR);
    $stmt->execute();
    $reviews =  $stmt->fetchAll();

} catch (PDOException $e) {
    echo $e;
}
require 'template_product.php';

?>
