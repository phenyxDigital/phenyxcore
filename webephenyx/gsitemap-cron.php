<?php


require(dirname(__FILE__).'/../../../../app/config.inc.php');
$sitemap = GsiteMap::getInstance();
ob_start();
try {
   $sitemap->createSitemap();
} catch (Exception $e) {
    PhenyxLogger::addLog('Cron error data trying creating sitemap : '.$e->getMessage(), 4, null, 'gsitemap-cron.php');
}

ob_end_flush();
