<?php
session_start();
header("X-XSS-Protection: 0;");

if ($_SESSION['id'] == '') {
    header("Location: / ");
    exit();
}
session_destroy();
header("Location: / ");
exit();
