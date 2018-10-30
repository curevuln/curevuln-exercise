### 対策方法解説
対策方法として、下記の２つを徹底することにより、認可制御を正しく実装することが必要となってきます。

 - 権限情報はsession変数に保持させ、外部から書き換えのできる箇所では保持しない。
 
    例 : GETやPOSTなどで```role=admin```のようなパラメータで権限に関わる値を書き込んではいけない。

- 与えられたURLに対しての表示や処理を行う前に、必要な権限をそのユーザーが有しているのかを毎回確認する。

#### 修正方法

```php
$stmt   = $dbh->prepare($query);
//$stmt->bindParam(':id', $_GET['id'], PDO::PARAM_INT);
$stmt->bindParam(':id', $_SESSION['id'], PDO::PARAM_INT);
$stmt->execute();
$usersData = $stmt->fetchAll();
```
- ```$_SESSION['id']```を直接利用し検索を行う事で外部から変更を行える箇所を極力減らすことができる。
