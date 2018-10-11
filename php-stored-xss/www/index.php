<?php
    session_start();
    header("X-XSS-Protection: 0;");

    require_once 'common.php';
    $dbh      = connectDB();
    $query    = 'SELECT * FROM contact';
    $contacts = $dbh->query($query)->fetchAll();
    if ( !isset($_SESSION['id']) ) {
        require 'template_noAuth_index.php';
    } else if ($_SESSION['id'] == 1855 ) {
        require 'template_admin_index.php';
    } else {
        require 'template_guest_index.php';
    }
?>
