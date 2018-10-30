<?php
require 'setting.php';
require_once 'common.php';
require './authFunction.php';
//変数定義
if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $dbh        = connectDB();
    $loginId    = $_POST['loginID'];
    $password   = $_POST['password'];
    $error      = "成功しました";
    if (!varidat($loginId, $password)) {
        $error = "パスワード、またはLoginIDが入力されていません。";
    } else {
        if (!login ($dbh, $loginId, $password) ) {
            $error = "パスワード、またはLoginIDが間違っています。";
        }
    }
    header("Location: / ");
    exit();
} else {
    require_once 'template_login.php';
}
