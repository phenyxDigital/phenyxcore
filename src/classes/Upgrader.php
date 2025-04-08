<?php

class Upgrader {

	public static function executeSqlRequest($query, $method) {

		switch ($method) {
		case 'execute':
			return Db::getInstance()->execute($query);
			break;
		case 'executeS':
			return Db::getInstance()->executeS($query);
			break;
		case 'getValue':
			return Db::getInstance()->getValue($query);
			break;
		case 'getRow':
			return Db::getInstance()->getRow($query);
			break;
		}

	}

	public static function instalTab($class_name, $name, $function = true, $plugin = null, $idParent = null, $parentName = null, $position = null, $openFunction = null, $divider = 0) {

		$translator = Language::getInstance();

		if (is_null($parentName) && is_null($idParent)) {
			return false;
		}

		if (!is_null($parentName)) {
			$idParent = (int) BackTab::getIdFromClassName($parentName);

			if (!$idParent) {
				return false;
			}

		}

		$idTab = (int) BackTab::getIdFromClassName($class_name);

		if (!$idTab) {
			$tab = new BackTab();

			if ($function) {

				if (!is_null($openFunction)) {
					$tab->function = $openFunction;
				} else {
					$tab->function = 'openAjaxController(\'' . $class_name . '\')';
				}

			}

			$tab->plugin = $plugin;
			$tab->id_parent = $idParent;
			$tab->class_name = $class_name;
			$tab->has_divider = $divider;
			$tab->active = 1;
			$tab->name = [];

			foreach (Language::getLanguages(true) as $lang) {
				$tab->name[$lang['id_lang']] = $translator->getGoogleTranslation($name, $lang['iso_code']);
			}

			unset($lang);
			$result = $tab->add(true, false, true, $position);
			return $this->deployPluginMeta(strtolower($class_name), $name, 'admin');
		} else {
			$tab = new BackTab($idTab);

			if ($function) {

				if (!is_null($openFunction)) {
					$tab->function = $openFunction;
				} else {
					$tab->function = 'openAjaxController(\'' . $class_name . '\')';
				}

			}

			$tab->plugin = $plugin;
			$tab->id_parent = $idParent;
			$tab->class_name = $class_name;
			$tab->has_divider = $divider;
			$tab->active = 1;
			$tab->name = [];

			foreach (Language::getLanguages(true) as $lang) {
				$tab->name[$lang['id_lang']] = $translator->getGoogleTranslation($name, $lang['iso_code']);
			}

			unset($lang);
			$result = $tab->update(true, false, $position);
			return self::deployMeta(strtolower($class_name), $name, 'admin');
		}

	}

	public static function deployMeta($page, $name, $type = 'front') {

		$result = true;
		$idMeta = Meta::getIdMetaByPage($page);

		if (!$idMeta) {
			$translator = Language::getInstance();
			$meta = new Meta();
			$meta->controller = $type;
			$meta->page = $page;
			$meta->plugin = $this->name;

			foreach (Language::getLanguages(true) as $lang) {
				$meta->title[$lang['id_lang']] = $translator->getGoogleTranslation($name, $lang['iso_code']);
				$meta->url_rewrite[$lang['id_lang']] = Tools::str2url($meta->title[$lang['id_lang']]);
			}

			$result = $meta->add();
		}

		return $result;
	}

	public static function generateNewVersion() {

		$recursive_directory = [
			'app/xml',
			'content/css',
			'content/mails',
			'content/pdf',
			'content/mp3',
			'content/pdf',
			'content/img/pdfWorker',
			'content/fonts',
			'content/js',
			'content/backoffice',
			'content/themes/phenyx-theme-default',
			'content/translations',
			'includes/classes',
			'includes/controllers',
			'includes/plugins',
			'vendor/phenyxdigital',
		];

		$zipPath = _EPH_UPGRADER_DIR_ . _EPH_VERSION_ . '.zip';
		$zip = new ZipArchive();

		if ($zip->open($zipPath, ZipArchive::CREATE) === true) {
			$iterator = new AppendIterator();

			foreach ($recursive_directory as $key => $directory) {

				if (is_dir(_EPH_ROOT_DIR_ . '/' . $directory . '/')) {
					$iterator->append(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(_EPH_ROOT_DIR_ . '/' . $directory . '/')));
				}

			}

			$iterator->append(new DirectoryIterator(_EPH_ROOT_DIR_ . '/content/themes/'));
			$iterator->append(new DirectoryIterator(_EPH_ROOT_DIR_ . '/app/'));

			foreach ($iterator as $file) {

				$filePath = $file->getPathname();
				$filePath = str_replace(_EPH_ROOT_DIR_ . '/', '', $filePath);
				$ext = pathinfo($file->getFilename(), PATHINFO_EXTENSION);

				if (is_dir($file->getPathname())) {
					continue;
				}

				if (in_array($file->getFilename(), ['.', '..', '.htaccess', 'composer.lock', 'settings.inc.php', '.gitattributes', '.user.ini', '.php-ini', '.php-version'])) {
					continue;
				}

				if ($ext == 'txt') {
					continue;
				}

				if ($ext == 'csv') {
					continue;
				}

				if ($ext == 'zip') {
					continue;
				}

				if ($ext == 'dat') {
					continue;
				}

				if (str_contains($filePath, 'custom_') && $ext == 'css') {
					continue;
				}

				if (str_contains($filePath, '/.git/')) {
					continue;
				}

				if (str_contains($filePath, '/uploads/')) {
					continue;
				}

				if (str_contains($filePath, '/cache/')) {
					continue;
				}

				if (str_contains($filePath, 'sitemap.xml')) {
					continue;
				}

				if (str_contains($filePath, 'truc')) {
					continue;
				}

				$zip->addFile($file->getPathname(), $filePath);

			}

			$zip->close();
		}

	}

}
