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
            <div class="username">
                <?php echo $_SESSION["userName"] ?>さんこんにちは！
            </div>
        </div>
    </div>
    <div class="app">
        <?php if ($_SERVER["REQUEST_METHOD"] == "GET") {?>
        <h1>購入手続き</h1>
        <h3>購入商品 :  <?php echo htmlspecialchars($products[0]['title'], ENT_QUOTES) ?></h3>
        <h3>価格　: ¥  <?php echo htmlspecialchars($products[0]['price'], ENT_QUOTES) ?></h3>
        <form class="pure-form pure-form-aligned contacts" action="shopping.php" method="post">
            <fieldset>
                <div class="pure-control-group">
                    <label for="title">宛名</label><br>
                    <input id=name type="text" name=name value="<?php echo htmlspecialchars($users[0]['name'], ENT_QUOTES )?>"><br>
                </div>
                <div class="pure-control-group">
                    <label for="title">宛先</label><br>
                    <input id=addr type="text" name=addr value="<?php echo htmlspecialchars($users[0]['addr'], ENT_QUOTES )?>"><br>
                </div>
                <div class="pure-control-group">
                    <label for="title">購入個数</label><br>
                    <input id="num" type="number" name="num" value="1" min="1" max="99"><br>
                </div>
                <input id="product_id" type="hidden" name="product_id" value="<?php echo $products[0]['id']?>"><br>

                <div class="pure-controls">
                    <button type="submit" class="pure-button pure-button-primary">購入</button>
                </div>
            </fieldset>
        </form>
    <?php } else { ?>
        <h1>ご購入ありがとうございました。</h1>
        <h3>下記の内容で購入いたしましたことをお伝えいたします。</h3>
        <p>宛名 : <?php echo htmlspecialchars( $_POST['name'],ENT_QUOTES);?></p>
        <p>宛先 : <?php echo htmlspecialchars( $_POST['addr'],ENT_QUOTES);?></p>
        <p>購入品名 : <?php echo htmlspecialchars( $products[0]['title'],ENT_QUOTES);?></p>
        <p>購入個数 : <?php echo htmlspecialchars( $_POST['num'],ENT_QUOTES);?></p>
        <p>単価 : <?php echo htmlspecialchars( $products[0]['price'],ENT_QUOTES);?></p>
        <p>値段 : <?php echo htmlspecialchars( $price,ENT_QUOTES);?></p>
        <a href="/">TOPへ戻る</a>
    <?php } ?>
    </div>
</body>
</html>
