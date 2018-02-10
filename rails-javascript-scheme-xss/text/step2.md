# 対策

基本的にリンク先として出力するには`http`と`https`のみといった形でホワイトリストで制限します．  

Railsには`sanitize`ヘルパーがあるので，それを使用してみましょう．

```ruby
<td><%= sanitize link_to user.url, user.url %></td>
```

これで`javascript:`スキームが使用された場合でも，リンクとして出力されないため，XSSが生じないことが確認できます．
