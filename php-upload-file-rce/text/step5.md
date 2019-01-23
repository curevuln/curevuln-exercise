### 修正例

このアプリケーションで脆弱性が存在するのは，fileFunction.phpで検査を行わずにアップロードを受け入れている関数に問題があります。
まずファイルを受け入れている関数```fileUpload```をみていきます。

ここでは、一切のファイルの種類の確認を行なっていません。これが原因で今回の脆弱性が発生しました。

1. 拡張子の確認を行う

本来はユーザーがアップロード時に使用したファイル名をそのまま使わずに uuid など一意となるファイル名を自動で作成した上で対応した拡張子を付与するのが望ましいでしょう。  
ここでは、簡単のために拡張子の確認を行います。

```php
//ホワイトリスト方式
if( !preg_match('/\.(png|jpeg|jpg|gif)$/', basename($_FILES['icon']['name'])) ) {
  return (bool)false;
}
```

しかし、これだけでは `evil.php.gif` というようなファイル名にすることで回避することができます。  
この場合、Nginx や Apache などの設定によってはファイルの実行許可を与えてしまい、脆弱性は残り続けることになります。  

2. MIME Type を確認する

アップロードされたファイルの  MIME Content-type を確認します。  
`mime_content_type` の結果がホワイトリストで定められた種類であることを確認します。

```php
$allow_types = array("image/png", "image/jpeg", "image/gif");
$type = mime_content_type("wei.gif");
if (!in_array($type, $allow_types, true)) {
  return (bool)false;
}
```

3. Nginx や Apache の設定で `.php` 以外のときは実行しないようにする

今回は既に設定していますが、そのほかにもアップロード先のディレクトリの実行権限を落とすなどの設定も必要です。  

このように多重防御を行うことが重要となります。
