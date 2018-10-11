<?php
    setcookie("ADMINSESSION", 'YWRtaW4=');

    require 'common.php';
    $dbh      = connectDB();
    $query    = 'SELECT * FROM contact';
    $contacts = $dbh->query($query)->fetchAll();
    require 'template_index.php';
?>
