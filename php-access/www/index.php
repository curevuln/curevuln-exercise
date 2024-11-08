<?php
    session_start();
    header("X-XSS-Protection: 0;");

    require_once 'common.php';
    if ( isset($_SESSION['id']) ) {
        require 'template_auth_index.php';
    } else {
        require 'template_noAuth_index.php';
    }
?>
