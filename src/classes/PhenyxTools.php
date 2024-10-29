<?php

use \Curl\Curl;

/**
 * Class PhenyxToolsCore
 *
 * @since 1.9.1.0
 */
class PhenyxTools {

	protected static $instance;

	protected $_url;

	protected $_crypto_key;

	public $context;

	public $default_theme;

	public $plugins = [];

	public $license;

	public function __construct() {

		$this->context = Context::getContext();
        if (!isset($this->context->phenyxConfig)) {
            $this->context->phenyxConfig =  Configuration::getInstance();
            
        }
		$this->context->company = new Company($this->context->phenyxConfig->get('EPH_COMPANY_ID'));
		$this->context->theme = new Theme((int) $this->context->company->id_theme);
		$this->default_theme = $this->context->theme->directory;
		$this->context->language = Tools::jsonDecode(Tools::jsonEncode(Language::buildObject('Language', $this->context->phenyxConfig->get('EPH_LANG_DEFAULT'))));

		$this->_url = _EPH_PHENYX_API_;
		$string = $this->context->phenyxConfig->get('_EPHENYX_LICENSE_KEY_', null, false) . '/' . $this->context->company->company_url;
		$this->_crypto_key = Tools::encrypt_decrypt('encrypt', $string, _PHP_ENCRYPTION_KEY_, _COOKIE_KEY_);

		$this->license = $this->checkLicense();
		$this->context->license = $this->license;

		$this->plugins = $this->getInstalledPluginsDirOnDisk();

	}

	public function getInstalledPluginsDirOnDisk() {

		$cacheId = 'getInstalledPluginsDirOnDisk';

		if ($this->context->cache_enable && is_object($this->context->cache_api)) {
			$value = $this->context->cache_api->getData($cacheId);
			$temp = empty($value) ? null : Tools::jsonDecode($value, true);

			if (!empty($temp)) {
				return $temp;
			}

		}

		$plugins = [];
		$pluginList = [];
		$plugs = scandir(_EPH_PLUGIN_DIR_);

		foreach ($plugs as $name) {

			if (in_array($name, ['.', '..'])) {
				continue;
			}

			if (is_file(_EPH_PLUGIN_DIR_ . $name)) {
				continue;
			} else

			if (is_dir(_EPH_PLUGIN_DIR_ . $name . DIRECTORY_SEPARATOR) && file_exists(_EPH_PLUGIN_DIR_ . $name . '/' . $name . '.php')) {

				if (!Validate::isPluginName($name)) {
					throw new PhenyxException(sprintf('Plugin %s is not a valid plugin name', $name));
				}

				$pluginList[] = $name;
			}

			$plugs = scandir(_EPH_SPECIFIC_PLUGIN_DIR_);

			foreach ($plugs as $name) {

				if (in_array($name, ['.', '..'])) {
					continue;
				}

				if (is_file(_EPH_SPECIFIC_PLUGIN_DIR_ . $name)) {
					continue;
				} else

				if (is_dir(_EPH_SPECIFIC_PLUGIN_DIR_ . $name . DIRECTORY_SEPARATOR) && file_exists(_EPH_SPECIFIC_PLUGIN_DIR_ . $name . DIRECTORY_SEPARATOR . $name . '.php')) {

					if (!Validate::isPluginName($name)) {
						throw new PhenyxException(sprintf('Plugin %s is not a valid plugin name', $name));
					}

					$pluginList[] = $name;
				}

			}

			foreach ($pluginList as $plugin) {

				if (in_array($plugin, ['.', '..'])) {
					continue;
				}

				if (Plugin::isInstalled($plugin)) {
					$plugins[$plugin] = true;
				}

			}

		}

		if ($this->context->cache_enable && is_object($this->context->cache_api)) {
			$temp = $plugins === null ? null : Tools::jsonEncode($plugins);
			$this->context->cache_api->putData($cacheId, $temp, 3600);
		}

		return $plugins;

	}

	public static function getInstance() {

		if (!PhenyxTools::$instance) {
			PhenyxTools:$instance = new PhenyxTools();
		}

		return PhenyxTools::$instance;
	}

	public function generateCurrentJson($use_cache = true) {

		if ($use_cache && file_exists(_EPH_CONFIG_DIR_ . 'json/new_json.json')) {
			$md5List = file_get_contents(_EPH_CONFIG_DIR_ . 'json/new_json.json');
			unlink(_EPH_CONFIG_DIR_ . 'json/new_json.json');
			return Tools::jsonDecode($md5List, true);
		}

		$directories = Theme::getInstalledThemeDirectories();

		$recursive_directory = [
			'app/xml',
			'content/backoffice',
			'content/css',
			'content/fonts',
			'content/js',
			'content/localization',
			'content/img/pdfWorker',
			'content/mails',
			'content/mp3',
			'content/pdf',
			'content/themes/phenyx-theme-default',
			'includes/classes',
			'includes/controllers',
			'vendor/phenyxdigitale',
			'webephenyx',
		];
		$iso_langs = [];
		$languages = Language::getLanguages(false);

		foreach ($languages as $language) {
			$recursive_directory[] = 'content/translations/' . $language['iso_code'];
			$iso_langs[] = $language['iso_code'];
		}

		foreach ($this->plugins as $plugin => $installed) {

			if (is_dir(_EPH_PLUGIN_DIR_ . $plugin)) {
				$recursive_directory[] = 'includes/plugins/' . $plugin;
			}

		}

		$iterator = new AppendIterator();
		$iterator->append(new DirectoryIterator(_EPH_ROOT_DIR_ . '/content/themes/'));

		foreach ($recursive_directory as $key => $directory) {

			if (is_dir(_EPH_ROOT_DIR_ . '/' . $directory)) {
				$iterator->append(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(_EPH_ROOT_DIR_ . '/' . $directory . '/')));
			}

		}

		$iterator->append(new DirectoryIterator(_EPH_ROOT_DIR_ . '/app/'));
		$iterator->append(new DirectoryIterator(_EPH_ROOT_DIR_ . '/'));

		foreach ($directories as $directory) {

			if ($directory == 'phenyx-theme-default') {
				continue;
			}

			$excludes[] = '/' . $directory . '/css/';
			$excludes[] = '/' . $directory . '/fonts/';
			$excludes[] = '/' . $directory . '/font/';
			$excludes[] = '/' . $directory . '/img/';
			$excludes[] = '/' . $directory . '/js/';
			$excludes[] = '/' . $directory . '/plugins/';
			$excludes[] = '/' . $directory . '/pdf/';
			$excludes[] = '/' . $directory . '/docs/';
		}

		foreach ($iterator as $file) {
			$filePath = $file->getPathname();
			$filePath = str_replace(_EPH_ROOT_DIR_, '', $filePath);

			if (in_array($file->getFilename(), ['.', '..', '.htaccess', 'composer.lock', 'settings.inc.php', '.php-ini', '.php-version'])) {
				continue;
			}

			$inExclude = false;

			foreach ($excludes as $exclude) {

				if (str_contains($filePath, $exclude)) {
					$inExclude = true;
					continue;
				}

			}

			if ($inExclude) {
				continue;
			}

			if (is_dir($file->getPathname())) {

				continue;
			}

			$ext = pathinfo($file->getFilename(), PATHINFO_EXTENSION);

			if ($ext == 'txt') {
				continue;
			}

			if ($ext == 'zip') {
				continue;
			}

			if (str_contains($filePath, '/plugins/') && str_contains($filePath, '/translations/')) {

				foreach ($this->plugins as $plugin) {

					if (str_contains($filePath, '/plugins/' . $plugin . '/translations/')) {
						$test = str_replace('/includes/plugins/' . $plugin . '/translations/', '', $filePath);
						$test = str_replace('.php', '', $test);

						if (!in_array($test, $iso_langs)) {
							continue;

						}

					}

				}

			}

			if (str_contains($filePath, 'custom_') && $ext == 'css') {
				continue;
			}

			if (str_contains($filePath, '/uploads/')) {
				continue;
			}

			if (str_contains($filePath, 'sitemap.xml')) {
				continue;
			}

			if (str_contains($filePath, '/cache/')) {
				continue;
			}

			if (str_contains($filePath, '/views/docs/')) {
				continue;
			}

			$md5List[$filePath] = md5_file($file->getPathname());
		}

		return $md5List;

	}

	public function generateOwnCurrentJson() {

		if (!file_exists(_EPH_CONFIG_DIR_ . 'json/new_json.json')) {
			$md5List = $this->generateCurrentJson(false);

			if (is_array($md5List)) {
				file_put_contents(
					_EPH_CONFIG_DIR_ . 'json/new_json.json',
					json_encode($md5List, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
				);
				chmod(_EPH_CONFIG_DIR_ . 'json/new_json.json', 0777);
			}

		}

	}
    
    public static function getConfiguration($tags) {
        
        return Context::getContext()->phenyxConfig->get($tags);
    }

	public static function addJsDef($jsDef) {

		return Context::getContext()->media->addJsDef($jsDef);

	}

	public static function addJsDefL($params, $content, $smarty = null, &$repeat = false) {

		return Context::getContext()->media->addJsDefL($params, $content, $smarty, $repeat);
	}

	public function checkLicense() {

		$data_array = [
			'action'      => 'checkLicence',
			'license_key' => $this->context->phenyxConfig->get('_EPHENYX_LICENSE_KEY_', null, false),
			'crypto_key'  => $this->_crypto_key,
		];
		$curl = new Curl();
		$curl->setDefaultJsonDecoder($assoc = true);
		$curl->setHeader('Content-Type', 'application/json');
		$curl->setTimeout(6000);
		$curl->post($this->_url, json_encode($data_array));
		return $curl->response;

	}

	public function getPhenyxPlugins() {

		$data_array = [
			'action'      => 'getPhenyxPlugins',
			'license_key' => $this->context->phenyxConfig->get('_EPHENYX_LICENSE_KEY_', null, false),
			'crypto_key'  => $this->_crypto_key,
		];
		$curl = new Curl();
		$curl->setDefaultJsonDecoder($assoc = true);
		$curl->setHeader('Content-Type', 'application/json');
		$curl->setTimeout(6000);
		$curl->post($this->_url, json_encode($data_array));
		$plugins = $curl->response;

		if (is_array($plugins)) {
			file_put_contents(
				_EPH_CONFIG_DIR_ . 'json/plugin_sources.json',
				json_encode($plugins, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
			);
			chmod(_EPH_CONFIG_DIR_ . 'json/plugin_sources.json', 0777);
			return true;
		}

		return false;

	}

	public function writeNewSettings($version) {

		$seeting_files = _EPH_CONFIG_DIR_ . 'settings.inc.php';

		$mysqlEngine = (defined('_MYSQL_ENGINE_') ? _MYSQL_ENGINE_ : 'InnoDB');

		copy($seeting_files, str_replace('.php', '.old.php', $seeting_files));
		$confFile = fopen($seeting_files, 'w');
		fwrite($confFile, '<?php' . PHP_EOL . PHP_EOL);

		$caches = ['CacheMemcache', 'CacheApc', 'FileBased', 'AwsRedis', 'CacheMemcached', 'CacheXcache'];
		$current_cache = !(empty($this->context->phenyxConfig->get('EPH_PAGE_CACHE_TYPE'))) ? $this->context->phenyxConfig->get('EPH_PAGE_CACHE_TYPE') : 'FileBased';

		$datas = [
			['_EPH_CACHING_SYSTEM_', (defined('_EPH_CACHING_SYSTEM_') && in_array(_EPH_CACHING_SYSTEM_, $caches)) ? _EPH_CACHING_SYSTEM_ : $current_cache],
			['_DB_NAME_', _DB_NAME_],
			['_MYSQL_ENGINE_', $mysqlEngine],
			['_DB_SERVER_', _DB_SERVER_],
			['_DB_USER_', _DB_USER_],
			['_DB_PASSWD_', _DB_PASSWD_],
			['_DB_PREFIX_', _DB_PREFIX_],
			['_COOKIE_KEY_', _COOKIE_KEY_],
			['_COOKIE_IV_', _COOKIE_IV_],
			['_EPH_CREATION_DATE_', defined("_EPH_CREATION_DATE_") ? _PS_CREATION_DATE_ : date('Y-m-d')],
			['_RIJNDAEL_KEY_', _RIJNDAEL_KEY_],
			['_RIJNDAEL_IV_', _RIJNDAEL_IV_],
			['_EPH_VERSION_', $version],
			['_EPH_VENDOR_DIR_', _EPH_VENDOR_DIR_],
			['_PHP_ENCRYPTION_KEY_', _PHP_ENCRYPTION_KEY_],
		];

		if (defined('_FORUM_MODE_')) {
			$datas[] = ['_FORUM_MODE_', _FORUM_MODE_];
		}

		if (defined('_BLOG_MODE_')) {
			$datas[] = ['_BLOG_MODE_', _BLOG_MODE_];
		}

		if (defined('_WIKI_MODE_')) {
			$datas[] = ['_WIKI_MODE_', _WIKI_MODE_];
		}

		if (defined('_EPHENYX_MODE_')) {
			$datas[] = ['_EPHENYX_MODE_', _EPHENYX_MODE_];
		}

		foreach ($datas as $data) {
			fwrite($confFile, 'define(\'' . $data[0] . '\', \'' . $this->checkString($data[1]) . '\');' . PHP_EOL);
		}

		return true;
	}

	public function alterSqlTable($table, $column, $type, $after) {

		$query = 'SELECT `COLUMN_NAME`
            FROM `INFORMATION_SCHEMA`.`COLUMNS`
            WHERE `TABLE_SCHEMA`="' . _DB_NAME_ . '"
            AND `TABLE_NAME`= "' . _DB_PREFIX_ . $table . '"
            AND `COLUMN_NAME`= "' . $column . '"';

		$result = Db::getInstance()->getValue(trim($query));

		if ($result != $column) {
			$sql = 'ALTER TABLE `' . _DB_PREFIX_ . $table . '` ADD `' . $column . '` ' . $type . ' AFTER `' . $after . '`';
			Db::getInstance()->execute(trim($sql));
		}

	}

	public function checkString($string) {

		if (!is_numeric($string)) {
			$string = addslashes($string);
		}

		return $string;
	}

	public static function cleanBackTabs() {

		$query = 'SELECT id_back_tab  FROM `' . _DB_PREFIX_ . 'back_tab_lang` WHERE id_lang = 1 ORDER BY id_back_tab ASC';
		$tabLangs = Db::getInstance()->executeS($query);

		foreach ($tabLangs as $tabLang) {
			$parent = Db::getInstance()->getValue(
				(new DbQuery())
					->select('`id_back_tab`')
					->from('back_tab')
					->where('`id_back_tab` = ' . (int) $tabLang['id_back_tab'])
			);

			if (!$parent) {
				$sql = 'DELETE FROM `' . _DB_PREFIX_ . 'back_tab_lang` WHERE id_back_tab = ' . $tabLang['id_back_tab'];
				Db::getInstance()->execute($sql);
			}

		}

		$query = 'SELECT id_back_tab  FROM `' . _DB_PREFIX_ . 'employee_access` ORDER BY id_back_tab ASC';
		$tabAccess = Db::getInstance()->executeS($query);

		foreach ($tabAccess as $access) {
			$parent = Db::getInstance()->getValue(
				(new DbQuery())
					->select('`id_back_tab`')
					->from('back_tab')
					->where('`id_back_tab` = ' . (int) $access['id_back_tab'])
			);

			if (!$parent) {
				$sql = 'DELETE FROM `' . _DB_PREFIX_ . 'employee_access` WHERE id_back_tab = ' . $access['id_back_tab'];
				Db::getInstance()->execute($sql);
			}

		}

		$query = 'SELECT id_back_tab  FROM `' . _DB_PREFIX_ . 'back_tab` ORDER BY id_back_tab ASC';

		$tabs = Db::getInstance()->executeS($query);

		$i = 1;

		foreach ($tabs as $tab) {

			$parents = Db::getInstance()->executes(
				(new DbQuery())
					->select('`id_back_tab`')
					->from('back_tab')
					->where('`id_parent` = ' . (int) $tab['id_back_tab'])
			);

			foreach ($parents as $parent) {
				$sql = 'UPDATE `' . _DB_PREFIX_ . 'back_tab` SET id_parent = ' . $i . ' WHERE id_back_tab = ' . $parent['id_back_tab'];
				Db::getInstance()->execute($sql);
			}

			$sql = 'UPDATE `' . _DB_PREFIX_ . 'back_tab` SET id_back_tab = ' . $i . ' WHERE id_back_tab = ' . $tab['id_back_tab'];
			Db::getInstance()->execute($sql);
			$sql = 'UPDATE `' . _DB_PREFIX_ . 'back_tab_lang` SET id_back_tab = ' . $i . ' WHERE id_back_tab = ' . $tab['id_back_tab'];
			Db::getInstance()->execute($sql);
			$sql = 'UPDATE `' . _DB_PREFIX_ . 'employee_access` SET id_back_tab = ' . $i . ' WHERE id_back_tab = ' . $tab['id_back_tab'];
			Db::getInstance()->execute($sql);
			$i++;
		}

	}

	public static function cleanMetas() {

		$query = 'SELECT id_meta  FROM `' . _DB_PREFIX_ . 'meta_lang` WHERE id_lang = 1 ORDER BY id_meta ASC';
		$metaLangs = Db::getInstance()->executeS($query);

		foreach ($metaLangs as $metaLang) {
			$parent = Db::getInstance()->getValue(
				(new DbQuery())
					->select('`id_meta`')
					->from('meta')
					->where('`id_meta` = ' . (int) $metaLang['id_meta'])
			);

			if (!$parent) {
				$sql = 'DELETE FROM `' . _DB_PREFIX_ . 'meta_lang` WHERE id_meta = ' . $metaLang['id_meta'];
				Db::getInstance()->execute($sql);
			}

		}

		$query = 'SELECT id_meta  FROM `' . _DB_PREFIX_ . 'theme_meta` ORDER BY id_meta ASC';
		$themeMetas = Db::getInstance()->executeS($query);

		foreach ($themeMetas as $themeMeta) {
			$parent = Db::getInstance()->getValue(
				(new DbQuery())
					->select('`id_meta`')
					->from('meta')
					->where('`id_meta` = ' . (int) $themeMeta['id_meta'])
			);

			if (!$parent) {
				$sql = 'DELETE FROM `' . _DB_PREFIX_ . 'theme_meta` WHERE id_meta = ' . $themeMeta['id_meta'];
				Db::getInstance()->execute($sql);
			}

		}

		$query = 'SELECT id_meta  FROM `' . _DB_PREFIX_ . 'meta` ORDER BY id_meta ASC';

		$metas = Db::getInstance()->executeS($query);

		$i = 1;

		foreach ($metas as $meta) {

			$sql = 'UPDATE `' . _DB_PREFIX_ . 'meta` SET id_meta = ' . $i . ' WHERE id_meta = ' . $meta['id_meta'];
			Db::getInstance()->execute($sql);
			$sql = 'UPDATE `' . _DB_PREFIX_ . 'meta_lang` SET id_meta = ' . $i . ' WHERE id_meta = ' . $meta['id_meta'];
			Db::getInstance()->execute($sql);
			$sql = 'UPDATE `' . _DB_PREFIX_ . 'theme_meta` SET id_meta = ' . $i . ' WHERE id_meta = ' . $meta['id_meta'];
			Db::getInstance()->execute($sql);
			$i++;
		}

	}

	public static function cleanPlugins() {

		$query = 'SELECT DISTINCT(id_plugin)  FROM `' . _DB_PREFIX_ . 'plugin_access`  ORDER BY id_plugin ASC';
		$pluginAccess = Db::getInstance()->executeS($query);

		foreach ($pluginAccess as $access) {
			$parent = Db::getInstance()->getValue(
				(new DbQuery())
					->select('`id_plugin`')
					->from('plugin')
					->where('`id_plugin` = ' . (int) $access['id_plugin'])
			);

			if (!$parent) {
				$sql = 'DELETE FROM `' . _DB_PREFIX_ . 'plugin_access` WHERE id_plugin = ' . $access['id_plugin'];
				Db::getInstance()->execute($sql);
			}

		}

		$query = 'SELECT DISTINCT(id_plugin)  FROM `' . _DB_PREFIX_ . 'plugin_carrier`  ORDER BY id_plugin ASC';
		$pluginCarriers = Db::getInstance()->executeS($query);

		foreach ($pluginCarriers as $carrier) {
			$parent = Db::getInstance()->getValue(
				(new DbQuery())
					->select('`id_plugin`')
					->from('plugin')
					->where('`id_plugin` = ' . (int) $carrier['id_plugin'])
			);

			if (!$parent) {
				$sql = 'DELETE FROM `' . _DB_PREFIX_ . 'plugin_carrier` WHERE id_plugin = ' . $carrier['id_plugin'];
				Db::getInstance()->execute($sql);
			}

		}

		$query = 'SELECT DISTINCT(id_plugin)  FROM `' . _DB_PREFIX_ . 'plugin_country`  ORDER BY id_plugin ASC';
		$pluginCountries = Db::getInstance()->executeS($query);

		foreach ($pluginCountries as $country) {
			$parent = Db::getInstance()->getValue(
				(new DbQuery())
					->select('`id_plugin`')
					->from('plugin')
					->where('`id_plugin` = ' . (int) $country['id_plugin'])
			);

			if (!$parent) {
				$sql = 'DELETE FROM `' . _DB_PREFIX_ . 'plugin_country` WHERE id_plugin = ' . $country['id_plugin'];
				Db::getInstance()->execute($sql);
			}

		}

		$query = 'SELECT DISTINCT(id_plugin)  FROM `' . _DB_PREFIX_ . 'plugin_currency`  ORDER BY id_plugin ASC';
		$pluginCountries = Db::getInstance()->executeS($query);

		foreach ($pluginCountries as $country) {
			$parent = Db::getInstance()->getValue(
				(new DbQuery())
					->select('`id_plugin`')
					->from('plugin')
					->where('`id_plugin` = ' . (int) $country['id_plugin'])
			);

			if (!$parent) {
				$sql = 'DELETE FROM `' . _DB_PREFIX_ . 'plugin_currency` WHERE id_plugin = ' . $country['id_plugin'];
				Db::getInstance()->execute($sql);
			}

		}

		$query = 'SELECT DISTINCT(id_plugin)  FROM `' . _DB_PREFIX_ . 'plugin_group`  ORDER BY id_plugin ASC';
		$pluginCountries = Db::getInstance()->executeS($query);

		foreach ($pluginCountries as $country) {
			$parent = Db::getInstance()->getValue(
				(new DbQuery())
					->select('`id_plugin`')
					->from('plugin')
					->where('`id_plugin` = ' . (int) $country['id_plugin'])
			);

			if (!$parent) {
				$sql = 'DELETE FROM `' . _DB_PREFIX_ . 'plugin_group` WHERE id_plugin = ' . $country['id_plugin'];
				Db::getInstance()->execute($sql);
			}

		}

		$query = 'SELECT DISTINCT(id_plugin)  FROM `' . _DB_PREFIX_ . 'hook_plugin`  ORDER BY id_plugin ASC';
		$pluginCountries = Db::getInstance()->executeS($query);

		foreach ($pluginCountries as $country) {
			$parent = Db::getInstance()->getValue(
				(new DbQuery())
					->select('`id_plugin`')
					->from('plugin')
					->where('`id_plugin` = ' . (int) $country['id_plugin'])
			);

			if (!$parent) {
				$sql = 'DELETE FROM `' . _DB_PREFIX_ . 'hook_plugin` WHERE id_plugin = ' . $country['id_plugin'];
				Db::getInstance()->execute($sql);
			}

		}

		$query = 'SELECT DISTINCT(id_plugin)  FROM `' . _DB_PREFIX_ . 'hook_plugin_exceptions`  ORDER BY id_plugin ASC';
		$pluginCountries = Db::getInstance()->executeS($query);

		foreach ($pluginCountries as $country) {
			$parent = Db::getInstance()->getValue(
				(new DbQuery())
					->select('`id_plugin`')
					->from('plugin')
					->where('`id_plugin` = ' . (int) $country['id_plugin'])
			);

			if (!$parent) {
				$sql = 'DELETE FROM `' . _DB_PREFIX_ . 'hook_plugin_exceptions` WHERE id_plugin = ' . $country['id_plugin'];
				Db::getInstance()->execute($sql);
			}

		}

		$query = 'SELECT DISTINCT(id_plugin)  FROM `' . _DB_PREFIX_ . 'payment_mode`  ORDER BY id_plugin ASC';
		$pluginCountries = Db::getInstance()->executeS($query);

		foreach ($pluginCountries as $country) {
			$parent = Db::getInstance()->getValue(
				(new DbQuery())
					->select('`id_plugin`')
					->from('plugin')
					->where('`id_plugin` = ' . (int) $country['id_plugin'])
			);

			if (!$parent) {
				$sql = 'DELETE FROM `' . _DB_PREFIX_ . 'payment_mode` WHERE id_plugin = ' . $country['id_plugin'];
				Db::getInstance()->execute($sql);
			}

		}

		$query = 'SELECT *  FROM `' . _DB_PREFIX_ . 'plugin` ORDER BY id_plugin ASC';
		$plugins = Db::getInstance()->executeS($query);

		foreach ($plugins as $plugin) {

			if (file_exists(_EPH_PLUGIN_DIR_ . $plugin['name'] . '/' . $plugin['name'] . '.php')) {
				continue;
			} else

			if (file_exists(_EPH_SPECIFIC_PLUGIN_DIR_ . $plugin['name'] . '/' . $plugin['name'] . '.php')) {
				continue;
			}

			$sql = 'DELETE FROM `' . _DB_PREFIX_ . 'plugin` WHERE id_plugin = ' . $plugin['id_plugin'];
			Db::getInstance()->execute($sql);
			$sql = 'DELETE FROM `' . _DB_PREFIX_ . 'plugins_perfs` WHERE plugin = \'' . $plugin['name'] . '\'';
			Db::getInstance()->execute($sql);
			$sql = 'DELETE FROM `' . _DB_PREFIX_ . 'plugin_access` WHERE id_plugin = ' . $plugin['id_plugin'];
			Db::getInstance()->execute($sql);
			$sql = 'DELETE FROM `' . _DB_PREFIX_ . 'plugin_carrier` WHERE id_plugin = ' . $plugin['id_plugin'];
			Db::getInstance()->execute($sql);
			$sql = 'DELETE FROM `' . _DB_PREFIX_ . 'plugin_country` WHERE id_plugin = ' . $plugin['id_plugin'];
			Db::getInstance()->execute($sql);
			$sql = 'DELETE FROM `' . _DB_PREFIX_ . 'plugin_currency` WHERE id_plugin = ' . $plugin['id_plugin'];
			Db::getInstance()->execute($sql);
			$sql = 'DELETE FROM `' . _DB_PREFIX_ . 'plugin_group` WHERE id_plugin = ' . $plugin['id_plugin'];
			Db::getInstance()->execute($sql);
			$sql = 'DELETE FROM `' . _DB_PREFIX_ . 'hook_plugin` WHERE id_plugin = ' . $plugin['id_plugin'];
			Db::getInstance()->execute($sql);
			$sql = 'DELETE FROM `' . _DB_PREFIX_ . 'hook_plugin_exceptions` WHERE id_plugin = ' . $plugin['id_plugin'];
			Db::getInstance()->execute($sql);
			$sql = 'DELETE FROM `' . _DB_PREFIX_ . 'payment_mode` WHERE id_plugin = ' . $plugin['id_plugin'];
			Db::getInstance()->execute($sql);

		}

		$query = 'SELECT id_plugin  FROM `' . _DB_PREFIX_ . 'plugin` ORDER BY id_plugin ASC';
		$plugins = Db::getInstance()->executeS($query);
		$i = 1;

		foreach ($plugins as $plugin) {
			$sql = 'UPDATE `' . _DB_PREFIX_ . 'plugin` SET id_plugin = ' . $i . ' WHERE id_plugin = ' . $plugin['id_plugin'];
			Db::getInstance()->execute($sql);

			$sql = 'UPDATE `' . _DB_PREFIX_ . 'plugin_access` SET id_plugin = ' . $i . ' WHERE id_plugin = ' . $plugin['id_plugin'];
			Db::getInstance()->execute($sql);
			$sql = 'UPDATE `' . _DB_PREFIX_ . 'plugin_carrier` SET id_plugin = ' . $i . ' WHERE id_plugin = ' . $plugin['id_plugin'];
			Db::getInstance()->execute($sql);
			$sql = 'UPDATE `' . _DB_PREFIX_ . 'plugin_country` SET id_plugin = ' . $i . ' WHERE id_plugin = ' . $plugin['id_plugin'];
			Db::getInstance()->execute($sql);
			$sql = 'UPDATE `' . _DB_PREFIX_ . 'plugin_currency` SET id_plugin = ' . $i . '  WHERE id_plugin = ' . $plugin['id_plugin'];
			Db::getInstance()->execute($sql);
			$sql = 'UPDATE `' . _DB_PREFIX_ . 'plugin_group` SET id_plugin = ' . $i . '  WHERE id_plugin = ' . $plugin['id_plugin'];
			Db::getInstance()->execute($sql);
			$sql = 'UPDATE `' . _DB_PREFIX_ . 'hook_plugin` SET id_plugin = ' . $i . '  WHERE id_plugin = ' . $plugin['id_plugin'];
			Db::getInstance()->execute($sql);
			$sql = 'UPDATE `' . _DB_PREFIX_ . 'hook_plugin_exceptions` SET id_plugin = ' . $i . '  WHERE id_plugin = ' . $plugin['id_plugin'];
			Db::getInstance()->execute($sql);
			$sql = 'UPDATE `' . _DB_PREFIX_ . 'payment_mode` SET id_plugin = ' . $i . '  WHERE id_plugin = ' . $plugin['id_plugin'];
			Db::getInstance()->execute($sql);
			$i++;

		}

	}

	public static function cleanHook() {

		$query = 'SELECT DISTINCT(id_hook)  FROM `' . _DB_PREFIX_ . 'hook_plugin_exceptions`  ORDER BY id_hook ASC';
		$hooks = Db::getInstance()->executeS($query);

		foreach ($hooks as $hook) {
			$parent = Db::getInstance()->getValue(
				(new DbQuery())
					->select('`id_hook`')
					->from('hook')
					->where('`id_hook` = ' . (int) $hook['id_hook'])
			);

			if (!$parent) {
				$sql = 'DELETE FROM `' . _DB_PREFIX_ . 'hook_plugin_exceptions` WHERE id_hook = ' . $hook['id_hook'];
				Db::getInstance()->execute($sql);
			}

		}

		$query = 'SELECT DISTINCT(id_hook)  FROM `' . _DB_PREFIX_ . 'hook_lang`  ORDER BY id_hook ASC';
		$hooks = Db::getInstance()->executeS($query);

		foreach ($hooks as $hook) {
			$parent = Db::getInstance()->getValue(
				(new DbQuery())
					->select('`id_hook`')
					->from('hook')
					->where('`id_hook` = ' . (int) $hook['id_hook'])
			);

			if (!$parent) {
				$sql = 'DELETE FROM `' . _DB_PREFIX_ . 'hook_lang` WHERE id_hook = ' . $hook['id_hook'];
				Db::getInstance()->execute($sql);
			}

		}

		$query = 'SELECT DISTINCT(id_hook)  FROM `' . _DB_PREFIX_ . 'hook_plugin`  ORDER BY id_hook ASC';
		$hooks = Db::getInstance()->executeS($query);

		foreach ($hooks as $hook) {
			$parent = Db::getInstance()->getValue(
				(new DbQuery())
					->select('`id_hook`')
					->from('hook')
					->where('`id_hook` = ' . (int) $hook['id_hook'])
			);

			if (!$parent) {
				$sql = 'DELETE FROM `' . _DB_PREFIX_ . 'hook_plugin` WHERE id_hook = ' . $hook['id_hook'];
				Db::getInstance()->execute($sql);
			}

		}

		$query = 'SELECT DISTINCT(id_hook)  FROM `' . _DB_PREFIX_ . 'hook_plugin_exceptions`  ORDER BY id_hook ASC';
		$hooks = Db::getInstance()->executeS($query);

		foreach ($hooks as $hook) {
			$parent = Db::getInstance()->getValue(
				(new DbQuery())
					->select('`id_hook`')
					->from('hook')
					->where('`id_hook` = ' . (int) $hook['id_hook'])
			);

			if (!$parent) {
				$sql = 'DELETE FROM `' . _DB_PREFIX_ . 'hook_plugin_exceptions` WHERE id_hook = ' . $hook['id_hook'];
				Db::getInstance()->execute($sql);
			}

		}

		$query = 'SELECT *  FROM `' . _DB_PREFIX_ . 'hook` ORDER BY id_hook ASC';
		$hooks = Db::getInstance()->executeS($query);

		$i = 1;

		foreach ($hooks as $hook) {
			$sql = 'UPDATE `' . _DB_PREFIX_ . 'hook` SET id_hook = ' . $i . ' WHERE id_hook = ' . $hook['id_hook'];
			Db::getInstance()->execute($sql);

			$sql = 'UPDATE `' . _DB_PREFIX_ . 'hook_plugin_exceptions` SET id_hook = ' . $i . ' WHERE id_hook = ' . $hook['id_hook'];
			Db::getInstance()->execute($sql);
			$sql = 'UPDATE `' . _DB_PREFIX_ . 'hook_lang` SET id_hook = ' . $i . ' WHERE id_hook = ' . $hook['id_hook'];
			Db::getInstance()->execute($sql);
			$sql = 'UPDATE `' . _DB_PREFIX_ . 'hook_plugin` SET id_hook = ' . $i . ' WHERE id_hook = ' . $hook['id_hook'];
			Db::getInstance()->execute($sql);
			$i++;

		}

		Hook::getInstance()->getArgs(true);
		self::resetPlugin();

	}

	public static function resetPlugin() {

		$query = 'SELECT *  FROM `' . _DB_PREFIX_ . 'plugin` ORDER BY id_plugin ASC';
		$plugins = Db::getInstance()->executeS($query);

		foreach ($plugins as $plugin) {

			if (file_exists(_EPH_PLUGIN_DIR_ . $plugin['name'] . '/' . $plugin['name'] . '.php')) {
				require_once _EPH_PLUGIN_DIR_ . $plugin['name'] . '/' . $plugin['name'] . '.php';
			} else

			if (file_exists(_EPH_SPECIFIC_PLUGIN_DIR_ . $plugin['name'] . '/' . $plugin['name'] . '.php')) {
				require_once _EPH_SPECIFIC_PLUGIN_DIR_ . $plugin['name'] . '/' . $plugin['name'] . '.php';
			}

			if (class_exists($plugin['name'], false)) {

				$tmpPlugin = Adapter_ServiceLocator::get($plugin['name']);

				if (method_exists($tmpPlugin, 'reset')) {
					$plugin = Plugin::getInstanceByName($plugin['name']);
					$plugin->reset();

				}

			}

		}

	}

	public function exportLang($iso, $theme, $plugins) {

		$file = fopen("testProcessSubmitExportLan.txt", "w");

		if ($iso && $theme) {

			$items = array_flip(Language::getFilesList($iso, $theme, false, false, false, false, false));
			$plugins = array_flip($this->getPluginFilesList($iso, $theme, $plugins));
			$fileName = _EPH_TRANSLATIONS_DIR_ . '/export/' . $iso . '.gzip';
			$gz = new Archive_Tar($fileName, true);
			$gz->createModify($items, null, _SHOP_ROOT_DIR_);
			$gz->addModify($plugins, null, _EPH_ROOT_DIR_ . '/includes');

			$pathFile = _EPH_ROOT_DIR_ . '/packs/' . _EPH_VERSION_ . '/' . $iso . '/' . $iso . '.gzip';
			copy($fileName, $pathFile);

		} else {

			$this->errors[] = $this->la('Please select a language and a theme.');
		}

		if (count($this->errors)) {
			$result = [
				'success' => false,
				'message' => implode(PHP_EOL, $this->errors),
			];
		} else {

			$result = [
				'success' => true,
				'message' => $this->la('Language has been exported successfully'),
			];
		}

		die(Tools::jsonEncode($result));
	}

	public function getPluginFilesList($isoFrom, $themeFrom, $plugins) {

		$filesPlugins = [];

		foreach ($plugins as $mod) {

			if (is_dir(_EPH_PLUGIN_DIR_ . $mod)) {
				$modDir = _EPH_PLUGIN_DIR_ . $mod;
			} else

			if (is_dir(_EPH_SPECIFIC_PLUGIN_DIR_ . $mod)) {
				$modDir = _EPH_SPECIFIC_PLUGIN_DIR_ . $mod;
			}

			// Lang file

			if (file_exists($modDir . '/translations/' . (string) $isoFrom . '.php')) {
				$filesPlugins[$modDir . '/translations/' . (string) $isoFrom . '.php'] = ++$number;
			} else

			if (file_exists($modDir . '/translations/' . (string) $isoFrom . '/admin.php')) {
				$filesPlugins[$modDir . '/translations/' . (string) $isoFrom . '/admin.php'] = ++$number;
			} else

			if (file_exists($modDir . '/translations/' . (string) $isoFrom . '/class.php')) {
				$filesPlugins[$modDir . '/translations/' . (string) $isoFrom . '/class.php'] = ++$number;
			} else

			if (file_exists($modDir . '/translations/' . (string) $isoFrom . '/front.php')) {
				$filesPlugins[$modDir . '/translations/' . (string) $isoFrom . '/front.php'] = ++$number;
			}

			// Mails files
			$modMailDirFrom = $modDir . '/mails/' . (string) $isoFrom;

			if (file_exists($modMailDirFrom)) {
				$dirFiles = scandir($modMailDirFrom);

				foreach ($dirFiles as $file) {

					if (file_exists($modMailDirFrom . '/' . $file) && $file != '.' && $file != '..' && $file != '.svn') {
						$filesPlugins[$modMailDirFrom . '/' . $file] = ++$number;
					}

				}

			}

			$modPdfDirFrom = $modDir . '/pdf/' . (string) $isoFrom;

			if (file_exists($modPdfDirFrom)) {
				$dirFiles = scandir($modPdfDirFrom);

				foreach ($dirFiles as $file) {

					if (file_exists($modPdfDirFrom . '/' . $file) && $file != '.' && $file != '..' && $file != '.svn') {
						$filesPlugins[$modPdfDirFrom . '/' . $file] = ++$number;
					}

				}

			}

		}

		return $filesPlugins;
	}

	public function mergeLanguages() {

		$iso = $this->context->language->iso_code;
		$_plugins = $this->getPlugins();

		$_LANGAD = [];

		if (file_exists(_EPH_TRANSLATIONS_DIR_ . $iso . '/admin.php')) {
			@include _EPH_TRANSLATIONS_DIR_ . $iso . '/admin.php';
			$_LANGAD = $_LANGADM;
		}

		$toInsert = [];

		if (file_exists(_EPH_OVERRIDE_TRANSLATIONS_DIR_ . $iso . '/admin.php')) {

			@include _EPH_OVERRIDE_TRANSLATIONS_DIR_ . $iso . '/admin.php';

			if (isset($_LANGOVADM) && is_array($_LANGOVADM)) {
				$_LANGAD = array_merge(
					$_LANGAD,
					$_LANGOVADM
				);
			}

		}

		foreach ($_plugins as $plugin) {

			if (file_exists(_EPH_PLUGIN_DIR_ . $plugin . DIRECTORY_SEPARATOR . 'translations/' . $iso . '/admin.php')) {

				@include _EPH_PLUGIN_DIR_ . $plugin . DIRECTORY_SEPARATOR . 'translations/' . $iso . '/admin.php';

				if (is_array($_LANGADM)) {
					$_LANGAD = array_merge(
						$_LANGAD,
						$_LANGADM
					);
				}

			}

		}

		foreach ($_plugins as $plugin) {

			if (file_exists(_EPH_SPECIFIC_PLUGIN_DIR_ . $plugin . DIRECTORY_SEPARATOR . 'translations/' . $iso . '/admin.php')) {

				@include _EPH_SPECIFIC_PLUGIN_DIR_ . $plugin . DIRECTORY_SEPARATOR . 'translations/' . $iso . '/admin.php';

				if (is_array($_LANGADM)) {
					$_LANGAD = array_merge(
						$_LANGAD,
						$_LANGADM
					);
				}

			}

		}

		$toInsert = $_LANGAD;
		ksort($toInsert);
		$file = fopen(_EPH_TRANSLATIONS_DIR_ . $iso . '/admin.php', "w");
		fwrite($file, "<?php\n\nglobal \$_LANGADM;\n\n");
		fwrite($file, "\$_LANGADM = [];\n");

		foreach ($toInsert as $key => $value) {
			$value = htmlspecialchars_decode($value, ENT_QUOTES);
			fwrite($file, '$_LANGADM[\'' . translateSQL($key, true) . '\'] = \'' . translateSQL($value, true) . '\';' . "\n");
		}

		fwrite($file, "\n" . 'return $_LANGADM;' . "\n");
		fclose($file);
		$_LANGCLAS = [];

		if (file_exists(_EPH_TRANSLATIONS_DIR_ . $iso . '/class.php')) {
			@include _EPH_TRANSLATIONS_DIR_ . $iso . '/class.php';
			$_LANGCLAS = $_LANGCLASS;
		}

		$toInsert = [];

		if (file_exists(_EPH_OVERRIDE_TRANSLATIONS_DIR_ . $iso . '/class.php')) {

			@include _EPH_OVERRIDE_TRANSLATIONS_DIR_ . $iso . '/class.php';

			if (isset($_LANGOVCLASS) && is_array($_LANGOVCLASS)) {
				$_LANGCLAS = array_merge(
					$_LANGCLAS,
					$_LANGOVCLASS
				);
			}

		}

		foreach ($_plugins as $plugin) {

			if (file_exists(_EPH_PLUGIN_DIR_ . $plugin . DIRECTORY_SEPARATOR . 'translations/' . $iso . '/class.php')) {
				require_once _EPH_PLUGIN_DIR_ . $plugin . DIRECTORY_SEPARATOR . 'translations/' . $iso . '/class.php';

				if (is_array($_LANGCLASS)) {
					$_LANGCLAS = array_merge(
						$_LANGCLAS,
						$_LANGCLASS
					);
				}

			}

		}

		foreach ($_plugins as $plugin) {

			if (file_exists(_EPH_SPECIFIC_PLUGIN_DIR_ . $plugin . DIRECTORY_SEPARATOR . 'translations/' . $iso . '/class.php')) {
				require_once _EPH_SPECIFIC_PLUGIN_DIR_ . $plugin . DIRECTORY_SEPARATOR . 'translations/' . $iso . '/class.php';

				if (is_array($_LANGCLASS)) {
					$_LANGCLAS = array_merge(
						$_LANGCLAS,
						$_LANGCLASS
					);
				}

			}

		}

		$toInsert = $_LANGCLAS;
		ksort($toInsert);
		$file = fopen(_EPH_TRANSLATIONS_DIR_ . $iso . '/class.php', "w");
		fwrite($file, "<?php\n\nglobal \$_LANGCLASS;\n\n");
		fwrite($file, "\$_LANGCLASS = [];\n");

		foreach ($toInsert as $key => $value) {
			$value = htmlspecialchars_decode($value, ENT_QUOTES);
			fwrite($file, '$_LANGCLASS[\'' . translateSQL($key, true) . '\'] = \'' . translateSQL($value, true) . '\';' . "\n");
		}

		fwrite($file, "\n" . 'return $_LANGCLASS;' . "\n");
		fclose($file);

		$_LANGFRON = [];

		if (file_exists(_EPH_TRANSLATIONS_DIR_ . $iso . '/front.php')) {
			@include _EPH_TRANSLATIONS_DIR_ . $iso . '/front.php';
			$_LANGFRON = $_LANGFRONT;
		}

		$toInsert = [];

		if (file_exists(_EPH_OVERRIDE_TRANSLATIONS_DIR_ . $iso . '/front.php')) {

			@include _EPH_OVERRIDE_TRANSLATIONS_DIR_ . $iso . '/front.php';

			if (isset($_LANGOVFRONT) && is_array($_LANGOVFRONT)) {
				$_LANGFRON = array_merge(
					$_LANGFRON,
					$_LANGOVFRONT
				);
			}

		}

		foreach ($_plugins as $plugin) {

			if (file_exists(_EPH_PLUGIN_DIR_ . $plugin . DIRECTORY_SEPARATOR . 'translations/' . $iso . '/front.php')) {

				require_once _EPH_PLUGIN_DIR_ . $plugin . DIRECTORY_SEPARATOR . 'translations/' . $iso . '/front.php';

				if (is_array($complementary_language)) {
					$_LANGFRON = array_merge(
						$_LANGFRON,
						$_LANGFRONT
					);
				}

			}

		}

		foreach ($_plugins as $plugin) {

			if (file_exists(_EPH_SPECIFIC_PLUGIN_DIR_ . $plugin . DIRECTORY_SEPARATOR . 'translations/' . $iso . '/front.php')) {

				require_once _EPH_SPECIFIC_PLUGIN_DIR_ . $plugin . DIRECTORY_SEPARATOR . 'translations/' . $iso . '/front.php';

				if (is_array($complementary_language)) {
					$_LANGFRON = array_merge(
						$_LANGFRON,
						$_LANGFRONT
					);
				}

			}

		}

		$toInsert = $_LANGFRON;
		ksort($toInsert);
		$file = fopen(_EPH_TRANSLATIONS_DIR_ . $iso . '/front.php', "w");
		fwrite($file, "<?php\n\nglobal \$_LANGFRONT;\n\n");
		fwrite($file, "\$_LANGFRONT = [];\n");

		foreach ($toInsert as $key => $value) {
			$value = htmlspecialchars_decode($value, ENT_QUOTES);
			fwrite($file, '$_LANGFRONT[\'' . translateSQL($key, true) . '\'] = \'' . translateSQL($value, true) . '\';' . "\n");
		}

		fwrite($file, "\n" . 'return $_LANGFRONT;' . "\n");
		fclose($file);

		$_LANGMAI = [];

		if (file_exists(_EPH_TRANSLATIONS_DIR_ . $iso . '/mail.php')) {
			@include _EPH_TRANSLATIONS_DIR_ . $iso . '/mail.php';
			$_LANGMAI = $_LANGMAIL;
		}

		$toInsert = [];

		foreach ($_plugins as $plugin) {

			if (file_exists(_EPH_PLUGIN_DIR_ . $plugin . DIRECTORY_SEPARATOR . 'translations/' . $iso . '/mail.php')) {

				@include _EPH_PLUGIN_DIR_ . $plugin . DIRECTORY_SEPARATOR . 'translations/' . $iso . '/mail.php';

				if (is_array($_LANGMAIL)) {
					$_LANGMAI = array_merge(
						$_LANGMAI,
						$_LANGMAIL
					);
				}

			}

		}

		$toInsert = $_LANGMAI;
		ksort($toInsert);
		$file = fopen(_EPH_TRANSLATIONS_DIR_ . $iso . '/mail.php', "w");
		fwrite($file, "<?php\n\nglobal \$_LANGMAIL;\n\n");
		fwrite($file, "\$_LANGMAIL = [];\n");

		foreach ($toInsert as $key => $value) {
			$value = htmlspecialchars_decode($value, ENT_QUOTES);
			fwrite($file, '$_LANGMAIL[\'' . translateSQL($key, true) . '\'] = \'' . translateSQL($value, true) . '\';' . "\n");
		}

		fwrite($file, "\n" . 'return $_LANGMAIL;' . "\n");
		fclose($file);

		$_LANGPD = [];

		if (file_exists(_EPH_TRANSLATIONS_DIR_ . $iso . '/pdf.php')) {
			@include _EPH_TRANSLATIONS_DIR_ . $iso . '/pdf.php';
			$_LANGPD = $_LANGPDF;
		}

		$toInsert = [];

		foreach ($_plugins as $plugin) {

			if (file_exists(_EPH_PLUGIN_DIR_ . $plugin . DIRECTORY_SEPARATOR . 'translations/' . $iso . '/pdf.php')) {

				@include _EPH_PLUGIN_DIR_ . $plugin . DIRECTORY_SEPARATOR . 'translations/' . $iso . '/pdf.php';
				$complementary_language = $_LANGPDF;

				if (is_array($_LANGPDF)) {
					$_LANGPD = array_merge(
						$_LANGPD,
						$_LANGPDF
					);
				}

			}

		}

		$toInsert = $_LANGPD;
		ksort($toInsert);
		$file = fopen(_EPH_TRANSLATIONS_DIR_ . $iso . '/pdf.php', "w");
		fwrite($file, "<?php\n\nglobal \$_LANGPDF;\n\n");
		fwrite($file, "\$_LANGPDF = [];\n");

		foreach ($toInsert as $key => $value) {
			$value = htmlspecialchars_decode($value, ENT_QUOTES);
			fwrite($file, '$_LANGPDF[\'' . translateSQL($key, true) . '\'] = \'' . translateSQL($value, true) . '\';' . "\n");
		}

		fwrite($file, "\n" . 'return $_LANGPDF;' . "\n");
		fclose($file);

		$this->context->translations = new Translate($iso, $this->context->company);
		$this->context->phenyxConfig->updateValue('CURENT_MERGE_LANG_' . $this->context->language->iso_code, 1);

		return true;

	}

	public function getPlugins() {

		$plugs = [];
		$plugins = Plugin::getPluginsDirOnDisk();

		foreach ($plugins as $plugin) {

			if (Plugin::isInstalled($plugin)) {

				if (is_dir(_EPH_PLUGIN_DIR_ . $plugin . '/translations/' . $this->context->language->iso_code)) {
					$plugs[] = $plugin;
				}

			}

		}

		return $plugs;
	}

}
