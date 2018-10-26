<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Us List - Index</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://unpkg.com/purecss@1.0.0/build/pure-min.css" integrity="sha384-nn4HPE8lTHyVtfCBi5yW9d20FjT8BJwUXyWZT9InLYax14RDjBj46LmSztkmNP9w" crossorigin="anonymous">
    <link rel="stylesheet" href="/css/master.css">
</head>
<body>
    <div class="head">
        <?php if ($_SESSION['id'] == ''): ?>
            <a href="login.php"><div class="top-button">
                ログイン
            </div></a>
            <a href="registration.php"><div class="top-button">
                新規登録
            </div></a>
        <?php else: ?>
            <a href="logout.php"><div class="top-button">
                ログアウト
            </div></a>
            <a href="user.php"><div class="top-button">
                ユーザー情報
            </div></a>
            <a href="/"><div class="top-button">
                トップ
            </div></a>
        <?php endif; ?>
    </div>
    <div class="app">
        <?php if ($_SESSION['id'] == ''): ?>
            <h1>Topページ！</h1>
            <div class="link-zone">
                <a href="login.php">ログインはこちら</a>
                <a href="registration.php">新規登録はこちら</a>
            </div>
        <?php else: ?>
            <h1>Topページ！</h1>
            <div class="link-zone">
                <a href="user.php">ユーザー情報はこちら</a>
                <a href="logout.php">ログアウトはこちら</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
