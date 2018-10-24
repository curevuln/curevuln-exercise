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
    	<a class="item" href="/settings.php">
      	Settings
    	</a>
  	</div>
	</div>
	<div class="ui raised very padded text container segment">
 		<h2 class="ui header">Articles</h2>
    <div class="ui cards">
    <?php foreach ($result as $article): ?>
      <div class="card">
        <div class="content">
          <div class="header"><?=htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8')?></div>
          <div class="description">
            <?=htmlspecialchars($article['content'], ENT_QUOTES, 'UTF-8')?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
	  </div>
	</div>
</body>
</html>
