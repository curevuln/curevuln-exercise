<?php
header("X-XSS-Protection: 0;");
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title></title>
</head>
<body>
<?php
if (!empty($_GET['name']) && !empty($_GET['address'])) {
    echo "名前: " . htmlspecialchars($_GET['name'], ENT_QUOTES, "UTF-8") . "<br />";
    echo "住所: " . htmlspecialchars($_GET['address'], ENT_QUOTES, "UTF-8") . "<br />";
    echo "で登録しました．";
}
?>
<form action="safe.php" method="GET">
    <p>名前</p>
    <input type="text" name="name" />
    <p>住所</p>
    <input type="text" name="address" />
    <button type="submit">確認する</button>
</form>
</body>
</html>
