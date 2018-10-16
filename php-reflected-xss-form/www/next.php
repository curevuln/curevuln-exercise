<?php
    require 'common.php';
    if ( empty($_GET['contactTitle']) || empty($_GET['contactContent']) ) {
      header("location: /");
    }
    require 'template_next.php';
?>
