<?php
	header("Access-control-allow-origin: *");
	require('../../../../app/config.inc.php');
	ob_start();
	
	$family = explode(":", Tools::getValue('family'));
	$subset = Tools::getValue('subset');
	
	$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';
	header('Content-Type: text/css');
	echo  Webfont::getWebfontApi($family[0], $family[1],$subset);
	ob_end_flush();
?>