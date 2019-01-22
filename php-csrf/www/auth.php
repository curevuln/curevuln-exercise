<?php

require_once 'setting.php';
require_once 'common.php';
require './authFunction.php';

$dbh = connectDB();
$loginId = $_POST['loginID'];
$password = $_POST['password'];
$error = "成功しました";
if (!validate($loginId, $password)) {
  $error = "パスワード、またはLoginIDが入力されていません。";
} else {
  if (!login ($dbh, $loginId, $password) ) {
    $error = "パスワード、またはLoginIDが間違っています。";
  }
}
header("Location: / ");
exit();
