<?php
require_once 'setting.php';

require_once 'common.php';
$dbh      = connectDB();
$query    = 'SELECT `id`, `title`, `content`, `details`, `price`, `image` FROM product';
$products = $dbh->query($query)->fetchAll();
if ( !$_SESSION['id'] == '' ) {
    require 'template_auth_index.php';
} else {
    require 'template_noAuth_index.php';
}
?>
