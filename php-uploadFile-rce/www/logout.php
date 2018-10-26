<?php
    require_once 'config.php';

    if ( $_SESSION['id'] == '' ) {
        header("Location: / ");
        exit;
    }

    session_destroy();
    header("Location: / ");
    exit;
?>
