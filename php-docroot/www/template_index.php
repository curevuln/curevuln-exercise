<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Us List - Index</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://unpkg.com/purecss@1.0.0/build/pure-min.css" integrity="sha384-nn4HPE8lTHyVtfCBi5yW9d20FjT8BJwUXyWZT9InLYax14RDjBj46LmSztkmNP9w" crossorigin="anonymous">
    <link rel="stylesheet" href="./data/app.css">
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
        <h1>管理者ページ</h1>
        <img src="./data/administrator.png" alt="管理者" width="200" height="200"><br>
        <a href="login.php">loginはこちら</a>
    </div>
</body>
</html>
