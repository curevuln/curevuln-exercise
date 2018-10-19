<?php
require 'setting.php'

if ($_SESSION['id'] == '') {
    header("Location: / ");
    exit();
}

require 'common.php';
$id = Null;
try {
    $dbh = connectDB();
    $query =
    "INSERT INTO `review` (`id`,`product_id`,`user_id`,`title`, `review`) VALUES ( :id, :product_id, :user_id, :title, :review );";
    $stmt  = $dbh->prepare($query);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->bindValue(':product_id', $_POST['product_id'], PDO::PARAM_INT);
    $stmt->bindValue(':user_id', $_SESSION['id'], PDO::PARAM_INT);
    $stmt->bindParam(':title', $_POST['title'], PDO::PARAM_STR);
    $stmt->bindParam(':review', $_POST['review'], PDO::PARAM_STR);
    $stmt->execute();
} catch (PDOException $e) {
    echo $e;
}

header("Location: /");

?>
