<?php

header("access-control-allow-origin: *");
$timer_start = microtime(true);
require('../../../../app/config.inc.php');
ob_start();
$json = file_get_contents('php://input');
$data = json_decode($json);
if(isset($data->crypto_key) && isset($data->license_key)) {
    $phenyxConfig = Configuration::getInstance();
    $result = Tools::encrypt_decrypt('decrypt', $data->crypto_key, _PHP_ENCRYPTION_KEY_, $phenyxConfig->get('_EPHENYX_LICENSE_KEY_'));
    $verif = explode("/", $result);
    $valid = Tools::checkLicense($data->license_key, $verif[1]);

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
$action = $data->action;
Hook::getInstance()->exec('actionWebEphenyx', ['data' => $data]);
$phenyxTool = new PhenyxTools();
switch($action) {			
     case 'checkLicence':
        if (ob_get_length() != 0) {
    		header('Content-Type: application/json');
		} 
		$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';

		header($status);
		header('Content-Type: application/json');
		echo json_encode($license);
		break;	
     case 'getPhenyxPlugins':
        $installedPlugins = $data->plugins;            
        $plugins = IoPlugin::getPhenyxPluginsOnDisk($license->id, $installedPlugins);
        
		if (ob_get_length() != 0) {
    		header('Content-Type: application/json');
		} 
		$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';

		header($status);
		header('Content-Type: application/json');
        Context::getContext()->license = [];
		echo json_encode($plugins);
		break;	
    case 'getZipPlugin':	
		$plugin = $data->plugin;
        $link = [
            'pluginLink' =>IoPlugin::generatePluginZip($plugin)
        ];
		if (ob_get_length() != 0) {
    		header('Content-Type: application/json');
		} 
		$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';
		header($status);
		header('Content-Type: application/json');
        echo json_encode($link);
		break;
	case 'getJsonFile':
        $md5List = $phenyxTool->generateCurrentJson();
		if (ob_get_length() != 0) {
    		header('Content-Type: application/json');
		} 
		$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';

		header($status);
		header('Content-Type: application/json');
		echo json_encode($md5List);
		break;	
    case 'getOwnJsonFile':
        $result = $phenyxTool->generateOwnCurrentJson();        
        if($result) {
            
            $status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';
        } else {
            $status = $_SERVER['SERVER_PROTOCOL'] . ' 400 Error';
            
        }		
		header($status);
		header('Content-Type: application/json');
		echo $status;
		break;
    case 'getDefaultTheme':
        $default_theme = $phenyxTool->default_theme;
		if (ob_get_length() != 0) {
    		header('Content-Type: application/json');
		} 
		$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';
		header($status);
		header('Content-Type: application/json');
		echo $default_theme;
		break;
    case 'getdBParam':
		$md5List = [
			'_DB_SERVER_' => _DB_SERVER_,
            '_DB_NAME_' => _DB_NAME_,
			'_DB_USER_' => _DB_USER_,
			'_DB_PASSWD_' => _DB_PASSWD_
		];	
		if (ob_get_length() != 0) {
    		header('Content-Type: application/json');
		} 
		$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';
		header($status);
		header('Content-Type: application/json');
		echo json_encode($md5List);
		break;
    case 'getInstalledLangs':
		$langs = Tools::getIoLangs();
		if (ob_get_length() != 0) {
    		header('Content-Type: application/json');
		} 
		$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';

		header($status);
		header('Content-Type: application/json');
		echo json_encode($langs);
		break;	
    case 'getPluginOnDisk':
		$plugins = $phenyxTool->plugins;
		if (ob_get_length() != 0) {
    		header('Content-Type: application/json');
		} 
		$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';

		header($status);
		header('Content-Type: application/json');
		echo json_encode($plugins);
		break;	
    
    case 'executeCron':
		CronJobs::runTasksCrons();
		if (ob_get_length() != 0) {
    		header('Content-Type: application/json');
		} 
		$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';
		header($status);
		header('Content-Type: application/json');
		echo $status;
		break;
    case 'indexBookAccount':
        $account = StdAccount::getInstance();
		$account->archiveRequest();
		if (ob_get_length() != 0) {
    		header('Content-Type: application/json');
		} 
		$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';
		header($status);
		header('Content-Type: application/json');
		echo $status;
		break;
	
	case 'getOject':
		$query = $data->query;
		$class = $data->classe;
		$idObject =  Upgrader::executeSqlRequest($query, 'getValue');
		$object = new $class($idObject);
		if (ob_get_length() != 0) {
    		header('Content-Type: application/json');
		}
		$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';
		header($status);
		header('Content-Type: application/json');
		echo json_encode($object);
		break;
     case 'getTranslation':
        $google_api_key = Configuration::get('EPH_GOOGLE_TRANSLATE_API_KEY');
        Translation::getInstance();
        $iso = $data->iso;
        $origin = $data->origin;
        $translation = Tools::getGoogleTranslation($google_api_key, $origin, $iso);
        if (ob_get_length() != 0) {
    		header('Content-Type: application/json');
		} 
		$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';

		header($status);
		header('Content-Type: application/json');
		echo json_encode($translation);
		break;	
    case 'getTranslations':
        $iso_codes = $data->iso_codes;
        $translation = new Translation(null, $iso_codes);
        if (ob_get_length() != 0) {
    		header('Content-Type: application/json');
		} 
		$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';

		header($status);
		header('Content-Type: application/json');
		echo json_encode($translation->translations);
		break;	
    case 'createTranslation':
		$object = $data->object;
        $result = Translation::addTranslation($object);
		if (ob_get_length() != 0) {
    		header('Content-Type: application/json');
		} 
		$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';
		header($status);
		header('Content-Type: application/json');
		echo $result;
		break;
    case 'createNotification':
		$object = $data->object;
        $result = PhenyxNotification::addNotification($object);
		if (ob_get_length() != 0) {
    		header('Content-Type: application/json');
		} 
		$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';
		header($status);
		header('Content-Type: application/json');
		echo $result;
		break;
	case 'cleanDirectory':
		$path = $data->directory;
		Tools::removeEmptyDirs($path);
		$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';
		header($status);
		header('Content-Type: application/json');
		break;	
	case 'cleanEmptyDirectory':
		Tools::cleanEmptyDirectory();
		if (ob_get_length() != 0) {
    		header('Content-Type: application/json');
		} 
		$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';

		header($status);
		header('Content-Type: application/json');
		break;	
    case 'deleteBulkFile':
        $files = $data->files;
		Tools::deleteBulkFiles($files);
		if (ob_get_length() != 0) {
    		header('Content-Type: application/json');
		} 
		$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';

		header($status);
		header('Content-Type: application/json');
		break;	
    case 'pushSqlRequest':
		$query = $data->query;
		$method = $data->method;
		$request =  Upgrader::executeSqlRequest($query, $method);	
		if (ob_get_length() != 0) {
    		header('Content-Type: application/json');
		}
		$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';
		header($status);
		header('Content-Type: application/json');
		echo json_encode($request);
		break;    
    case 'getGenerateTabs':
        $tool = PhenyxTool::getInstance();
		$topbars = $tool->getTabs();
		if (ob_get_length() != 0) {
    		header('Content-Type: application/json');
		} 
		$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';

		header($status);
		header('Content-Type: application/json');
		echo json_encode($topbars);
		break;	
    case 'showTab':
        $tool = PhenyxTool::getInstance();
        $id_back_tab = $data->id_back_tab;
        $backTab = BackTab::getInstance($id_back_tab);
        $backTab->active = 1;
        $backTab->update();
        $tool->generateTabs(false);
		if (ob_get_length() != 0) {
    		header('Content-Type: application/json');
		} 
		$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';

		header($status);
		header('Content-Type: application/json');
		echo $status;
		break;	
    case 'hideTab':
        $tool = PhenyxTool::getInstance();
        $id_back_tab = $data->id_back_tab;
        $backTab = BackTab::getInstance($id_back_tab);
        $backTab->active = 0;
        $backTab->update();
        $tool->generateTabs(false);
		if (ob_get_length() != 0) {
    		header('Content-Type: application/json');
		} 
		$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';

		header($status);
		header('Content-Type: application/json');
		echo $status;
		break;	
    case 'writeNewSettings':
		$version = $data->version;
        $result = $phenyxTool->writeNewSettings($version);
		if (ob_get_length() != 0) {
    		header('Content-Type: application/json');
		} 
		$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';
		header($status);
		header('Content-Type: application/json');
		echo $result;
		break;
    case 'cleanBckTab':
        $result = $phenyxTool->cleanBackTabs();
		if (ob_get_length() != 0) {
    		header('Content-Type: application/json');
		} 
		$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';
		header($status);
		header('Content-Type: application/json');
		echo $result;
		break;
    case 'cleanMeta':
        $result = $phenyxTool->cleanMetas();
		if (ob_get_length() != 0) {
    		header('Content-Type: application/json');
		} 
		$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';
		header($status);
		header('Content-Type: application/json');
		echo $result;
		break;
    case 'cleanPlugin':
        $result = $phenyxTool->cleanPlugins();
		if (ob_get_length() != 0) {
    		header('Content-Type: application/json');
		} 
		$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';
		header($status);
		header('Content-Type: application/json');
		echo $result;
		break;
    case 'cleanHook':
        $result = $phenyxTool->cleanHook();
		if (ob_get_length() != 0) {
    		header('Content-Type: application/json');
		} 
		$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';
		header($status);
		header('Content-Type: application/json');
		echo $result;
		break;
    case 'alterSqlTable':
		$table = $data->table;
        $column = $data->column;
        $type = $data->type;
        $after = $data->after;
        $result = $phenyxTool->alterSqlTable($table, $column, $type, $after);
		if (ob_get_length() != 0) {
    		header('Content-Type: application/json');
		} 
		$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';
		header($status);
		header('Content-Type: application/json');
		echo $result;
		break;
    case 'mergeLanuages':		
        $result = $phenyxTool->mergeLanguages();
		if (ob_get_length() != 0) {
    		header('Content-Type: application/json');
		} 
		$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';
		header($status);
		header('Content-Type: application/json');
		echo $result;
		break;
    case 'mergeGlobalLanuages':
        $translations = $data->translations;
        $translation = new Translation();
        $translation->updateGlobalTranslations($translations);
		if (ob_get_length() != 0) {
    		header('Content-Type: application/json');
		} 
		$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';
		header($status);
		header('Content-Type: application/json');
		echo $status;
		break;
    case 'generatePhenyxData':
		$request =  PhenyxBackup::generatePhenyxData();
		if (ob_get_length() != 0) {
    		header('Content-Type: application/json');
		}
		$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';
		header($status);
		header('Content-Type: application/json');
		echo json_encode($request);
		break;
    case 'buidlIndexation':
		Search::indexation();
		if (ob_get_length() != 0) {
    		header('Content-Type: application/json');
		}
		$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';
		header($status);
		header('Content-Type: application/json');
		echo json_encode($request);
		break;
    case 'generateClassIndex':
		Tools::generateIndex();
		if (ob_get_length() != 0) {
    		header('Content-Type: application/json');
		}
		$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';
		header($status);
		header('Content-Type: application/json');
		echo $status;
		break;
    case 'downloadFile':
		$content = $data->content;
        $destination = $data->destination;
        $result = $phenyxTool->getIoFiles($content, $destination);
		if (ob_get_length() != 0) {
    		header('Content-Type: application/json');
		}
		$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';
		header($status);
		header('Content-Type: application/json');
		echo $result;
		break;
    case 'downloadZipFile':        
		$content = $data->content;
        $filename = $data->filename;
        $result = file_put_contents(_EPH_ROOT_DIR_.'/'.$filename, $content);
		if (ob_get_length() != 0) {
    		header('Content-Type: application/json');
		}
		$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';
		header($status);
		header('Content-Type: application/json');
		echo $result;
		break;
    case 'deleteFile':
		$file = $data->file;
        if(file_exists(_EPH_ROOT_DIR_.$file)) {
            $result = unlink(_EPH_ROOT_DIR_.$file);
        }
       
		if (ob_get_length() != 0) {
    		header('Content-Type: application/json');
		}
		$status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';
		header($status);
		header('Content-Type: application/json');
		echo $result;
		break;
    
}
ob_end_flush();