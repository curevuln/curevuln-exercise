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
    	<a class="item" href="/">
      	Home
    	</a>
    	<a class="active item" href="/settings.php">
      	Settings
    	</a>
  	</div>
	</div>
	<div class="ui raised very padded text container segment">
 		<h2 class="ui header">WebHook Settings</h2>
		<form class="ui form" action="settings.php" method="post">
			<div class="field">
				<label>URL</label>
				<input type="text"
						 name="url"
						 placeholder="https://example.com/"
						 value="<?php echo htmlspecialchars($result['url'], ENT_QUOTES, 'UTF-8');?>"
				>
			</div>
			<button class="ui button" type="submit">変更する</button>
		</form>
	</div>
</body>
</html>
