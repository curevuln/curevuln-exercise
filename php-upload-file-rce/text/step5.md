### 修正例

このアプリケーションで脆弱性が存在するのは，fileFunction.phpで検査を行わずにアップロードを受け入れている関数に問題があります。
まずファイルを受け入れている関数```fileUpload```をみていきます。

ここでは、一切のファイルの種類の確認を行なっていません。これが原因で今回の脆弱性が発生しました。

この脆弱性に対応するためにはまず、拡張子の確認を行います。

```php
//ホワイトリスト方式
if( !preg_match('*( .png|.jpeg|.jpg|.gif)',basename($_FILES['icon']['name']) ) ) {
    return (bool)false;
}
```

しかし、これだけでは `evil.php.gif` というようなファイル名にすることで回避することができます。  
この場合、Nginx や Apache などの設定によってはファイルの実行許可を与えてしまい、脆弱性は残り続けることになります。  

この問題に対応するために Nginx や Apache の設定を変更することに加え、アップロードされたファイルの  MIME Content-type を確認します。  
`mime_content_type` の結果がホワイトリストで定められた種類であることを確認します。

```php
string mime_content_type ( string $filename )
```
