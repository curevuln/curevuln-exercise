<?php
    header("X-XSS-Protection: 0;");
    header('location: http://' . $_SERVER['HTTP_HOST'] . '/search.php');
?>
