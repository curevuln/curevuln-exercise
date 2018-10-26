### 修正例

このアプリケーションで脆弱性が存在するのは，fileFunction.phpで検査を行わずにアップロードを受け入れている関数に問題があります。
まずファイルを受け入れている関数```fileUpload```をみていきます。

ここでは、一切のファイル拡張子確認を行なっていません。これが原因で今回の脆弱性が発生しました。

この脆弱性に仮対応するためにパターンマッチで拡張子の確認を行います。

```php
//ブラックリスト方式
if( preg_match('*( .php|.js|%00 )*',basename($_FILES['icon']['name']) ) ) {
    return (bool)false;
}
//ホワイトリスト方式
if( !preg_match('*( .png|.jpeg|.jpg|.gif)',basename($_FILES['icon']['name']) ) ) {
    return (bool)false;
}
