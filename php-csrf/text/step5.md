### 修正例
XSSの脆弱性がこのシナリオではありましたがここではその修正を行いません。

セッション固定化攻撃に対する修正例です。
authFunction.php
```php
function login (object $dbh, string $login, string $password):bool {

    $query  = " SELECT * FROM users WHERE loginid = :loginId; " ;
    try {

        $stmt   = $dbh->prepare($query);
        $stmt->bindParam(':loginId', $login, PDO::PARAM_STR);
        $stmt->execute();
        $usersData = $stmt->fetchAll();

    } catch (PDOException $e) {
        return (bool)false ;
    }
    if ( !password_verify($password, $usersData[0]['password']) ) {
        return (bool)false;
    }
    //ここに追加
    session_regenerate_id(true);

    $_SESSION["userName"]   = $usersData[0]['loginId'];
    $_SESSION["id"]         = $usersData[0]['id'];

    return (bool)true;

}
```

次にHTTPonlyの修正例ですが、今回はphp.iniではなくphp内部で修正していきます。
setting.phpの最初の行
```php
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
```
