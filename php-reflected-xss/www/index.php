<?php
    require 'common.php';
    $dbh = connectDB();
    $query = 'SELECT * FROM item';
    $items = $dbh->query($query)->fetchAll();
    require 'template_index.php';
?>
