<?php
    session_start();
    header("X-XSS-Protection: 0;");
    $_SESSION = array();
    session_destroy();
    header('Location: /');
?>
