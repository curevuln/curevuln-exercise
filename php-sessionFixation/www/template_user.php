<?php
session_start();
if ($_SESSION['id'] == '') {
    header("Location: / ");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Us List - Index</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://unpkg.com/purecss@1.0.0/build/pure-min.css" integrity="sha384-nn4HPE8lTHyVtfCBi5yW9d20FjT8BJwUXyWZT9InLYax14RDjBj46LmSztkmNP9w" crossorigin="anonymous">
    <style media="screen" type="text/css">
        body > .head {
            height: 70px;
            background-color: #248447a3;
            box-shadow: -1px 2px 17px 0px #b3b3b3;
        }
        body > .head > .top-button {
            margin-right: 30px;
            float: right;
        }
        body > .head > .top-button > a {
            color: #000;
            text-decoration	:none;
            line-height: 70px;
            font-size: 25px;
        }
        p {
            margin: 0px;
        }
        p {
            margin: 0px;
        }
        label{
            margin: 0;
        }
        body {
            text-align: center;
        }
        body > .app > .contacts{
            width: 60%;
            margin: auto;
            margin-top: 3.5px;
            margin-bottom: 3.5px;
        }
        body > .app > .contacts > .title{
            padding: 10px;
            background-color: #6472c370;
            box-shadow: -2px 1px 9px #0000007a;
        }
        body > .app > .contacts > .content{
            padding: 10px;
            background-color: rgba(0, 0, 0, 0.13);
        }
        .pure-form-aligned .pure-control-group label{
            text-align: center;

        }
        .pure-form-aligned .pure-control-group input, .pure-form-aligned .pure-control-group textarea{
            width: 100%;
        }
        .pure-form-aligned .pure-controls{
                margin: auto;
        }
    </style>
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
        <h3>名前</h3>
        <p><?php echo $users[0]['name'] ?></p>
        <h3>住所</h3>
        <p><?php echo $users[0]['addr'] ?></p>
        <h3>電話番号</h3>
        <p><?php echo $users[0]['addr'] ?></p>
    </div>
</body>
</html>
