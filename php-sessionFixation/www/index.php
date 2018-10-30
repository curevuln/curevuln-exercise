<?php
require 'setting.php';

require_once 'common.php';
$dbh      = connectDB();
$query    = 'SELECT `id`, `title`, `content`, `details`, `price`, `image` FROM product';
$products = $dbh->query($query)->fetchAll();
require 'template_index.php';
?>
