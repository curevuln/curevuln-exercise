<?php
require_once 'setting.php';

if ($_SESSION['id'] == '') {
    header("Location: / ");
    exit();
}
session_destroy();
header("Location: / ");
exit();
