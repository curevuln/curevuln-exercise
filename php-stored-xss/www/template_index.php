<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Us List - Index</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://unpkg.com/purecss@1.0.0/build/pure-min.css" integrity="sha384-nn4HPE8lTHyVtfCBi5yW9d20FjT8BJwUXyWZT9InLYax14RDjBj46LmSztkmNP9w" crossorigin="anonymous">
    <style media="screen" type="text/css">

        p {
            margin: 0px;
        }
        body {
            text-align: center;
        }
        body > .app > .contacts{
            width: 60%;
            margin: auto;
            border: groove;
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
    </style>
</head>
<body>
    <div class="app">
        <h1>お問い合わせ一覧</h1>
    <?php
    foreach ($contacts as $key => $contact) {
        $html   = "";
        $html  .= "<div class='contacts'><div class='title'> <p> タイトル : ";
        $html  .= $contact['title'];
        $html  .= "</p></div><div class='content'> <p>";
        $html  .= $contact['content'];
        $html  .= "</p></div></div>";
        echo $html;
    }
    ?>
    <a href="input.php">問い合わせ入力はこちら</a>
    </div>
</body>
</html>
