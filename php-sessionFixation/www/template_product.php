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
        <h1><?php echo $products[0]['title']; ?></h1>
        <?php foreach ($products as $key => $product): ?>
            <div class="product">
                <img width="380" height="380"  src="./img/<?php echo $product['image'] ?>.jpg" alt="<?php echo $product['image'] ?>">
                <h3><?php echo $product['title']; ?></h3>
                <p><?php echo $product['content']; ?></p>
                <p>¥<?php echo $product['price']; ?></p>
                <a href="shipping.php?id=<?php echo $product['id'] ?>">購入はこちら</a>
            </div>
        <?php endforeach; ?>
        <h1>レビュー一覧</h1>
        <?php foreach ($reviews as $key => $review): ?>
            <div class="review">
                <div class="review-title">
                    <h3>レビュータイトル : <?php echo $review['title'] ?></h3>
                </div>
                <div class="review-content">
                    <?php echo $review['review'] ?>
                </div>
            </div>
        <?php endforeach; ?>
        <h1>レビュー投稿</h1>
        <form class="pure-form pure-form-aligned contacts" action="comment.php" method="post">
            <fieldset>
                <div class="pure-control-group">
                    <label for="title">レビュータイトル</label><br>
                    <input id="title" type="text" name="title" placeholder="Title"><br>
                </div>

                <div class="pure-control-group">
                    <label for="foo">内容</label><br>
                    <textarea id="review" name="review" type="text">

                    </textarea><br>
                </div>
                <input id="product_id" type="hidden" name="product_id" value="<?php echo $products[0]['id']?>"><br>

                <div class="pure-controls">
                    <button type="submit" class="pure-button pure-button-primary">送信</button>
                </div>
            </fieldset>
        </form>
    </div>
</body>
</html>
