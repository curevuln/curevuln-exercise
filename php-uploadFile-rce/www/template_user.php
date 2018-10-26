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
        <?php if ( $_SESSION['id'] != '' ): ?>
            <h1>user情報</h1>
            <img src="./img/<?php echo $usersData[0]['icon'] ?>" width="200" height="200">
            <form class="pure-form pure-form-aligned contacts" action="user.php" method="post" enctype="multipart/form-data">
                <fieldset>
                    <div class="pure-control-group">
                        <label for="icon">アイコン更新はこちら</label><br>
                        <div class="uploadButton">
                            ファイルを選択
                            <input type="file" id="icon" name="icon" onchange="uv.style.display='inline-block'; uv.value = this.value;" />
                            <input type="text" id="uv" class="uploadValue" disabled />
                        </div>
                    </div>
                    <div class="pure-controls">
                        <button type="submit" class="pure-button pure-button-primary">更新</button>
                    </div>
                </fieldset>
            </form>
            <h3>名前</h3>
            <p><?php echo $usersData[0]['name'] ?></p>
            <h3>住所</h3>
            <p><?php echo $usersData[0]['addr'] ?></p>
        <?php endif; ?>
    </div>
</body>
</html>
