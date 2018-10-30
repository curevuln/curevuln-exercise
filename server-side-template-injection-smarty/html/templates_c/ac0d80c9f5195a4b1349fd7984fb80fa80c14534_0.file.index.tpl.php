<?php
/* Smarty version 3.1.33, created on 2018-10-30 08:50:34
  from '/var/www/html/templates/index.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.33',
  'unifunc' => 'content_5bd81b5a8f45e9_43934727',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'ac0d80c9f5195a4b1349fd7984fb80fa80c14534' => 
    array (
      0 => '/var/www/html/templates/index.tpl',
      1 => 1540888186,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_5bd81b5a8f45e9_43934727 (Smarty_Internal_Template $_smarty_tpl) {
?><!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>Blog Preview</title>
  <link rel="stylesheet" type="text/css" href="/static/css/semantic.min.css">
  <?php echo '<script'; ?>
 src="https://code.jquery.com/jquery-3.1.1.min.js"
    integrity="sha256-hVVnYaiADRTO2PzUGmuLJr8BLUSjGIZsDYGmIJLv2b8="
		crossorigin="anonymous">
	<?php echo '<script'; ?>
 src="/static/js/semantic.min.js"><?php echo '</script'; ?>
>
</head>
<body>
	<div class="ui raised very padded text container segment">
  	<?php $_template = new Smarty_Internal_Template('eval:'.$_smarty_tpl->tpl_vars['html']->value, $_smarty_tpl->smarty, $_smarty_tpl);echo $_template->fetch(); ?>
  	<form class="ui form" method="post" action="/">
    	<textarea name="html"></textarea>
    	<button class="ui button center primary" type="submit">Preview</button>
  	</form>
	</div>
</body>
</html>
<?php }
}
