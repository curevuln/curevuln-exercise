<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Us List - Index</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://unpkg.com/purecss@1.0.0/build/pure-min.css" integrity="sha384-nn4HPE8lTHyVtfCBi5yW9d20FjT8BJwUXyWZT9InLYax14RDjBj46LmSztkmNP9w" crossorigin="anonymous">
    <link rel="stylesheet" href="/data/app.css">
</head>
<body>
    <div class="head">
        <?php if (!$_SESSION['id'] == ''): ?>
            <div class="top-button">
                <a href="logout.php"><p>Logout</p></a>
            </div>
        <?php endif; ?>
    </div>
    <div class="app">
        <?php if ($_SESSION['id'] == ''): ?>
            <h1>Login</h1>
            <img src="./data/administrator.png" alt="管理者" width="200" height="200"><br>
            <form class="pure-form pure-form-aligned contacts" action="login.php" method="post">
                <fieldset>
                    <div class="pure-control-group">
                        <label for="login_id">LoginID</label><br>
                        <input id="loginID" type="text" name="loginID" placeholder="LoginID"><br>
                    </div>

                    <div class="pure-control-group">
                        <label for="foo">Password</label><br>
                        <input id="password" name="password" type="password" placeholder="password"><br>
                    </div>
                    <?php echo $error ; ?>
                    <div class="pure-controls">
                        <button type="submit" class="pure-button pure-button-primary">送信</button>
                    </div>
                </fieldset>
            </form>
        <?php else: ?>
            <h1> loginに成功しました！</h1>
        <?php endif; ?>
    </div>
</body>
</html>
