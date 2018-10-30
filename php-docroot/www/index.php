<?php
    session_start();
    header("X-XSS-Protection: 0;");
    require 'common.php';

    require 'template_index.php';
?>
