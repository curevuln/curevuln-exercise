### URLに埋め込まれた認証/認可情報の解説
また、URL内に認可に必要な秘密情報を埋め込む手法は、URLを複製できれば権限を得られてしまうため、好ましいものではありません。
    例 :
        http://curevuln.com/admin/info?check=e2F1dGhvcml6YXRpb246MSxyb2xlOiJhZG1pbiJ9

        e2F1dGhvcml6YXRpb246MSxyb2xlOiJhZG1pbiJ9はbase64でデコードすることにより```{authorization:1,role:"admin"}```のような認可に必要な秘密情報がJSON形式で埋め込まれていることがわかります。

このような実装による具体的なリスクとして、以下の事項に注意する必要があります。

 - RefererによるURLの漏洩
 - 利用者によるソーシャルネットワークなどでのURL開示
 - URLの検索エンジンへの登録
 - アドレスバーをみられることによるURLの窃取
