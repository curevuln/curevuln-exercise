<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>Webhook 設定</title>
  <link rel="stylesheet" type="text/css" href="/static/css/semantic.min.css">
  <script src="https://code.jquery.com/jquery-3.1.1.min.js"
    integrity="sha256-hVVnYaiADRTO2PzUGmuLJr8BLUSjGIZsDYGmIJLv2b8="
    crossorigin="anonymous">
  </script>
  <script src="/static/js/semantic.min.js"></script>
</head>
<body>
	<div class="ui inverted segment">
  	<div class="ui inverted secondary pointing menu">
      <a class="active item" href="/">
      	Home
    	</a>
  	</div>
	</div>
	<div class="ui raised very padded text container segment">
 		<h2 class="ui header">投稿</h2>
		<form class="ui form" action="/post.php" method="post">
			<div class="field">
				<label>タイトル</label>
				<input type="text"
						 name="title"
				>
			</div>
      <div class="field">
        <label>内容</label>
        <textarea name="content" rows="2"></textarea>
      </div>
			<button class="ui button primary" type="submit">投稿する</button>
		</form>
	</div>
</body>
</html>
