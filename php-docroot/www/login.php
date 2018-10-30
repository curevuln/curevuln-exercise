<?php
    session_start();
    header("X-XSS-Protection: 0;");
    require 'common.php';
    require_once 'loginFunction.php';
    $error = '';
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
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
    }
    require 'template_login.php';
?>
