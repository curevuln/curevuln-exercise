<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <title>Vuln Shop Index</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://unpkg.com/purecss@1.0.0/build/pure-min.css" integrity="sha384-nn4HPE8lTHyVtfCBi5yW9d20FjT8BJwUXyWZT9InLYax14RDjBj46LmSztkmNP9w" crossorigin="anonymous">
    <style media="screen" type="text/css">
      .body{
        text-align: center;
      }
      form{
        width: 50%;
        margin: auto;
        font-size: 15px;
      }
      input {
        padding: 2.5px;
        margin: 2.5px;
      }
    </style>
</head>
<body>
<div id="app" class="body">
    <h1>お問い合わせフォーム</h1>
    <h3>下記の内容をお問い合わせします。</h3>
    <form class="contact" action="submit.php" method="get">
        <label for="title">題名 : </label>
        <b><?php echo $_GET['contactTitle']; ?></b>
        <input type="hidden" name="contactTitle"   id="contactTitle"   value="<?php echo $_GET['contactTitle']; ?>"/><br>
        <label for="title">内容 : </label>
        <b><?php echo $_GET['contactContent']; ?></b>
        <input type="hidden" name="contactContent" id="contactContent" value="<?php echo $_GET['contactContent']; ?>"/><br>
        <input type="submit" name="submit"><br>
    </form>
</div>
</body>
</html>
