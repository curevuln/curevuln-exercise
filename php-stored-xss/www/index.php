<?php
    session_start();
    header("X-XSS-Protection: 0;");

    require_once 'common.php';
    $dbh      = connectDB();
    $query    = 'SELECT * FROM contact';
    $contacts = $dbh->query($query)->fetchAll();
    if ( !isset($_SESSION['id']) ) {
        require 'template_auuth_index.php';
    } else {
        require 'template_noAuth_index.php';
    }
?>
