<?php
if ($_SESSION['id'] == '') {
    header('Location: /');
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Us List - Index</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://unpkg.com/purecss@1.0.0/build/pure-min.css" integrity="sha384-nn4HPE8lTHyVtfCBi5yW9d20FjT8BJwUXyWZT9InLYax14RDjBj46LmSztkmNP9w" crossorigin="anonymous">
    <link rel="stylesheet" href="/css/app.css">

</head>
<body>
    <div class="head">
        <div class="top-button">
            <a href="/"><div>Top</div></a>
            <a href="user.php"><div>Info</div></a>
            <a href="logout.php"><div>Logout</div></a>
        </div>
    </div>
    <div class="app">
        <h1>ユーザー情報</h1>
        <h3>名前 : <?php echo htmlspecialchars( $users[0]['name'], ENT_QUOTES )?></h3>
        <h3>住所 : <?php echo htmlspecialchars( $users[0]['addr'], ENT_QUOTES ) ?></h3>
        <h3>電話番号 : <?php echo htmlspecialchars( $users[0]['tel'], ENT_QUOTES ) ?></h3>

        <div class="history">
            <h1>購入履歴</h1>
            <?php foreach ($shippings as $key => $shipping): ?>
            <div class="list">
                <h3 class="left"><?php echo $key+1; ?></h3>
                <h3>宛名 : <?php echo htmlspecialchars( $shipping['name'], ENT_QUOTES )?></h3>
                <h3>宛先 : <?php echo htmlspecialchars( $shipping['addr'], ENT_QUOTES )?></h3>
                <h3>商品名 : <?php echo htmlspecialchars( $shipping['title'], ENT_QUOTES )?></h3>
                <h3>個数 : <?php echo htmlspecialchars( $shipping['num'], ENT_QUOTES )?></h3>
                <h3>合計 : <?php echo htmlspecialchars( $shipping['price'], ENT_QUOTES )?></h3>
            </div>

            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
