<?php


require(dirname(__FILE__).'/../../../../app/config.inc.php');
@ini_set('max_execution_time', 0);
ob_start();
try {
   License::executeFileCron();
} catch (Exception $e) {
    PhenyxLogger::addLog('Cron error data : '.$e->getMessage(). ' IP origine : '.$Ip, 4, null, 'cronOF.php');
}

ob_end_flush();
