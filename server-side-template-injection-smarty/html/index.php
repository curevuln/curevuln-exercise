<?php
  require 'common.php';

  $smarty = new Smarty();
  $secure_dirs[] = '/tmp/';
  $smarty->setTemplateDir('./templates/');

  $html = "
    <h1>こんにちは、こんにちは</h1>
    <p>これはテストだよ</p>
  ";

  if (isset($_POST['html'])) {
    $html = $_POST['html'];
  }

  $smarty->assign('html', $html);
  $smarty->display('index.tpl');
?>
