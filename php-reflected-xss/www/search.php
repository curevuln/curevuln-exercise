<?php
    require 'common.php';
    $dbh = connectDB();
    $query = 'SELECT * FROM item WHERE name LIKE ?';
    $stmt = $dbh->prepare($query);

    if (isset($_GET['name'])) {
        $name = $_GET['name'];
    } else {
        $name = '';
    }
    $stmt->bindValue(1, '%' . addcslashes($name, '\_%') . '%', PDO::PARAM_STR);
    $stmt->execute();
    $items = $stmt->fetchAll();
    require 'template_search.php';
?>
