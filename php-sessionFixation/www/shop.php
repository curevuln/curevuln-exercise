<?php
    session_start();
    header("X-XSS-Protection: 0;");

    if ($_SESSION['id'] == '') {
        header("Location: / ");
        exit();
    }
    require 'common.php';
    require 'template_input.php';
?>
