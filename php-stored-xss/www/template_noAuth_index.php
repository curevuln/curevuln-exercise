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
            background-color: #6da6ff6b;
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
            <a href="/"><p>login</p></a>
        </div>
    </div>
    <div class="app">
        <h1>お問い合わせページへようこそ</h1>
        <h3>お問い合わせにはログインが必要です</h3>
        <h1>Login</h1>
        <form class="pure-form pure-form-aligned contacts" action="auth.php" method="post">
            <fieldset>
                <div class="pure-control-group">
                    <label for="login_id">LoginID</label><br>
                    <input id="loginId" type="text" name="loginID" placeholder="LoginID"><br>
                </div>

                <div class="pure-control-group">
                    <label for="foo">Password</label><br>
                    <input id="password" name="password" type="password" placeholder="password"><br>
                </div>

                <div class="pure-controls">
                    <button type="submit" class="pure-button pure-button-primary">送信</button>
                </div>
            </fieldset>
        </form>
        <?php echo "<p>".$error."</p>"; ?>
    </div>
</body>
</html>
