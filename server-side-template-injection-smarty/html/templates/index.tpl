<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>Blog Preview</title>
  <link rel="stylesheet" type="text/css" href="/static/css/semantic.min.css">
  <script src="https://code.jquery.com/jquery-3.1.1.min.js"
    integrity="sha256-hVVnYaiADRTO2PzUGmuLJr8BLUSjGIZsDYGmIJLv2b8="
		crossorigin="anonymous">
	<script src="/static/js/semantic.min.js"></script>
</head>
<body>
	<div class="ui raised very padded text container segment">
  	{eval var=$html}
  	<form class="ui form" method="post" action="/">
    	<textarea name="html"></textarea>
    	<button class="ui button center primary" type="submit">Preview</button>
  	</form>
	</div>
</body>
</html>
