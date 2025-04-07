<?php

header("access-control-allow-origin: *");
$timer_start = microtime(true);
require('../../../../app/config.inc.php');
ob_start();
$json = file_get_contents('php://input');
$data = json_decode($json);
$license_key = $data->license_key;
$license = License::getLicenceBykey($license_key);
$action = $data->action;
if($action == 'licenseSubscription') {
    return License::registerNewSubscription($data);
}
if(is_object($license)) {

    $result = Tools::encrypt_decrypt('decrypt', $data->crypto_key, $license->crypto_key, _COOKIE_KEY_);
    $verif = explode("/", $result);
    

    $valid = License::checkLicense($verif[0], $verif[1]);
    if(!$valid) {
       
	   header($_SERVER['SERVER_PROTOCOL'].' 401 Unauthorized');
       header('WWW-Authenticate: Basic realm="Welcome to Phenyx Io Webservice, please enter the authentication key as the login. No password required."');
       die('401 Unauthorized');
    }
} else {
    header($_SERVER['SERVER_PROTOCOL'].' 401 Unauthorized');
    header('WWW-Authenticate: Basic realm="Welcome to Phenyx Io Webservice, please enter the authentication key as the login. No password required."');
    die('401 Unauthorized');
}


switch($action) {
	case 'getActualite':		
		$actualites = Actualite::getMasterActualite();
        
		$actualites = json_encode($actualites);
		if (ob_get_length() != 0) {
			header('Content-Type: application/json');
		} 
		$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';
		header($status);
		header('Content-Type: application/json');
		echo $actualites;
		break;
    case 'updateIsoLang':		
		$iso_langs = $data->iso_langs;
        
		$license->iso_langs = $iso_langs;
        $license->update();
		if (ob_get_length() != 0) {
    		header('Content-Type: application/json');
		} 
		$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';
		header($status);
		header('Content-Type: application/json');
		break;
    case 'updatePlugins':		
		$plugins = $data->plugins;
        $plugins = Tools::jsonDecode(Tools::jsonEncode($plugins), true);
		$license->plugins = $plugins;
        $license->update();
		if (ob_get_length() != 0) {
    		header('Content-Type: application/json');
		} 
		$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';
		header($status);
		header('Content-Type: application/json');
		break;
    case 'getHashFlag':		
		$iso_code = $data->iso_code;
		$flag = Flags::getFlagByIso($iso_code);
        $flag = json_encode($flag);
		if (ob_get_length() != 0) {
    		header('Content-Type: application/json');
		} 
		$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';
		header($status);
		header('Content-Type: application/json');
        echo $flag;
		break;
	
	
    case 'getShopJsonFile':
		$md5List = Tools::generateShopFile($data->iso_langs, $data->plugins, $data->excludes);
		if (ob_get_length() != 0) {
    		header('Content-Type: application/json');
		} 
		$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';

		header($status);
		header('Content-Type: application/json');
		echo json_encode($md5List);
		break;
    case 'getDigitalJsonFile':
		$md5List = Tools::generateDigitalFiles($data->iso_langs, $data->plugins);
		if (ob_get_length() != 0) {
    		header('Content-Type: application/json');
		} 
		$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';

		header($status);
		header('Content-Type: application/json');
		echo json_encode($md5List);
		break;
    case 'regenerateVersion':
        $phenyxType = $data->phenyxType;	
        if($phenyxType == 'shop') {
            Tools::generateLastPhenyxShopZip();
        }
		if (ob_get_length() != 0) {
    		header('Content-Type: application/json');
		} 
		$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';
		header($status);
		header('Content-Type: application/json');
		break;
    case 'deleteFile':
        $file = $data->file;
		Tools::deleteFiles($file);
		if (ob_get_length() != 0) {
    		header('Content-Type: application/json');
		} 
		$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';

		header($status);
		header('Content-Type: application/json');
		break;	
    case 'exportLanguages':
        $iso = $data->iso;
        $theme = $data->theme;
        $plugins = $data->plugins;
		PhenyxTools::exportLang($iso, $theme, $plugins);
		if (ob_get_length() != 0) {
    		header('Content-Type: application/json');
		} 
		$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';

		header($status);
		header('Content-Type: application/json');
		break;	
}

ob_end_flush();