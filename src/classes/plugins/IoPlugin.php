<?php

/**
 * Class IoPlugin
 *
 * @since 2.1.0.0
 *
 * @property $confirmUninstall
 */
class IoPlugin extends Plugin {
    
    public $warn;

    protected function getCacheId($name = null) {

        $cache_array = [];
        $cache_array[] = $name !== null ? $name : $this->name;

        if (Configuration::get('EPH_SSL_ENABLED')) {
            $cache_array[] = (int) Tools::usingSecureMode();
        }

        if (Group::isFeatureActive() && isset($this->context->user)) {
            $cache_array[] = (int) Group::getCurrent()->id;
            $cache_array[] = implode('_', User::getGroupsStatic($this->context->user->id));
        }

        if (Language::isMultiLanguageActivated()) {
            $cache_array[] = (int) $this->context->language->id;
        }

        if (Currency::isMultiCurrencyActivated()) {
            $cache_array[] = (int) $this->context->currency->id;
        }

        $cache_array[] = (int) $this->context->country->id;

        return implode('|', $cache_array);
    }

    public static function getPluginsOnDisk($useConfig = false, $loggedOnAddons = false, $idEmployee = false, $full = false) {
        
        global $_PLUGINS;

        $context = Context::getContext();
        $phenyxPlugins = [];
        $phenyxDepends = [];
        $customer_plugins = $context->license->plugins;
        
        if(is_array($customer_plugins) && count($customer_plugins)) {
            foreach($customer_plugins as $plugin => $value) {
                $plug = PhenyxPlugins::getInstanceByName($plugin); 
                if(Validate::isLoadedObject($plug)) {
                    $phenyxPlugins[$plug->plugin] = $plug; 
                    $phenyxDepends[$plug->plugin] = $plug->depends;
                }
            }
        }

        $pluginList = [];
        $pluginNameList = [];
        $pluginsNameToCursor = [];
        $errors = [];

        $pluginsDir = Plugin::getPluginsDirOnDisk();

        $pluginsInstalled = [];
        $result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('m.id_plugin, m.`name`, m.`version`, m.active, m.enable_device, mp.`interest`')
                ->from('plugin', 'm')
                ->leftJoin('plugin_preference', 'mp', 'mp.`plugin` = m.`name` AND mp.`id_employee` = ' . (int) $idEmployee)
        );

        foreach ($result as $row) {
            $pluginsInstalled[$row['name']] = $row;
        }

        foreach ($pluginsDir as $plugin) {

            if (Plugin::useTooMuchMemory()) {
                $errors[] = Tools::displayError('All plugins cannot be loaded due to memory limit restrictions, please increase your memory_limit value on your server configuration');
                break;
            }

            if (!class_exists($plugin, false)) {

                if (file_exists(_EPH_PLUGIN_DIR_ . $plugin . '/' . $plugin . '.php')) {
                    $filePath = _EPH_PLUGIN_DIR_ . $plugin . '/' . $plugin . '.php';
                    $file = trim(file_get_contents(_EPH_PLUGIN_DIR_ . $plugin . '/' . $plugin . '.php'));
                } else
                if (file_exists(_EPH_SPECIFIC_PLUGIN_DIR_ . $plugin . '/' . $plugin . '.php')) {
                    $filePath = _EPH_SPECIFIC_PLUGIN_DIR_ . $plugin . '/' . $plugin . '.php';
                    $file = trim(file_get_contents(_EPH_SPECIFIC_PLUGIN_DIR_ . $plugin . '/' . $plugin . '.php'));
                }

                if (substr($file, 0, 5) == '<?php') {
                    $file = substr($file, 5);
                }

                if (substr($file, -2) == '?>') {
                    $file = substr($file, 0, -2);
                }

                $file = preg_replace('/\n[\s\t]*?use\s.*?;/', '', $file);

                if (eval('if (false){   ' . $file . "\n" . ' }') !== false) {

                    if (file_exists(_EPH_PLUGIN_DIR_ . $plugin . '/' . $plugin . '.php')) {
                        require_once _EPH_PLUGIN_DIR_ . $plugin . '/' . $plugin . '.php';
                    } else
                    if (file_exists(_EPH_SPECIFIC_PLUGIN_DIR_ . $plugin . '/' . $plugin . '.php')) {
                        require_once _EPH_SPECIFIC_PLUGIN_DIR_ . $plugin . '/' . $plugin . '.php';
                    }

                } else {
                    $errors[] = sprintf(Tools::displayError('%1$s (parse error in %2$s)'), $plugin, substr($filePath, strlen(_EPH_ROOT_DIR_)));
                }

            }

            if (class_exists($plugin, false)) {

                $tmpPlugin = Adapter_ServiceLocator::get($plugin);

                $item = [
                    'id'                     => is_null($tmpPlugin->id) ? 0 : $tmpPlugin->id,
                    'warning'                => $tmpPlugin->warning,
                    'name'                   => $tmpPlugin->name,
                    'version'                => $tmpPlugin->version,
                    'tab'                    => $tmpPlugin->tab,
                    'displayName'            => $tmpPlugin->displayName,
                    'description'            => !is_null($tmpPlugin->description) ? stripslashes($tmpPlugin->description) : '',
                    'author'                 => $tmpPlugin->author,
                    'author_uri'             => (isset($tmpPlugin->author_uri) && $tmpPlugin->author_uri) ? $tmpPlugin->author_uri : false,
                    'limited_countries'      => $tmpPlugin->limited_countries,
                    'parent_class'           => get_parent_class($plugin),
                    'is_configurable'        => $tmpPlugin->is_configurable = method_exists($tmpPlugin, 'getContent') ? 1 : 0,
                    'config_controller'      => $tmpPlugin->config_controller,
                    'active'                 => $tmpPlugin->active,
                    'trusted'                => true,
                    'currencies'             => isset($tmpPlugin->currencies) ? $tmpPlugin->currencies : null,
                    'currencies_mode'        => isset($tmpPlugin->currencies_mode) ? $tmpPlugin->currencies_mode : null,
                    'confirmUninstall'       => isset($tmpPlugin->confirmUninstall) ? html_entity_decode($tmpPlugin->confirmUninstall) : null,
                    'description_full'       => isset($tmpPlugin->description_full) ? stripslashes($tmpPlugin->description_full) : null,
                    'additional_description' => isset($tmpPlugin->additional_description) ? stripslashes($tmpPlugin->additional_description) : null,
                    'compatibility'          => isset($tmpPlugin->compatibility) ? (array) $tmpPlugin->compatibility : null,
                    'nb_rates'               => isset($tmpPlugin->nb_rates) ? (array) $tmpPlugin->nb_rates : null,
                    'avg_rate'               => isset($tmpPlugin->avg_rate) ? (array) $tmpPlugin->avg_rate : null,
                    'badges'                 => isset($tmpPlugin->badges) ? (array) $tmpPlugin->badges : null,
                    'url'                    => isset($tmpPlugin->url) ? $tmpPlugin->url : null,
                    'onclick_option'         => method_exists($plugin, 'onclickOption') ? true : false,
                ];

                if ($item['onclick_option']) {
                    $href = Context::getContext()->link->getAdminLink('Plugin', true) . '&plugin_name=' . $tmpPlugin->name . '&tab_plugin=' . $tmpPlugin->tab;
                    $item['onclick_option_content'] = [];
                    $optionTab = ['desactive', 'reset', 'configure', 'delete'];

                    foreach ($optionTab as $opt) {
                        $item['onclick_option_content'][$opt] = $tmpPlugin->onclickOption($opt, $href);
                    }

                }

                $item = (object) $item;
                $pluginList[] = $item;
                $pluginsNameToCursor[mb_strtolower($item->name)] = $item;

                unset($tmpPlugin);
            } else {
                $errors[] = sprintf(Tools::displayError('%1$s (class missing in %2$s)'), $plugin, substr($filePath, strlen(_EPH_ROOT_DIR_)));
            }

        }

        if (!empty($pluginNameList)) {
            $list = [Context::getContext()->company->id];
            $results = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
                (new DbQuery())
                    ->select('m.`id_plugin`, m.`name`, m.active, COUNT(m.`id_plugin`) AS `total`')
                    ->from('plugin', 'm')
                    ->where('LOWER(m.`name`) IN (' . mb_strtolower(implode(',', $pluginNameList)) . ')')
            );

            foreach ($results as $result) {

                if (isset($pluginsNameToCursor[mb_strtolower($result['name'])])) {
                    $pluginCursor = $pluginsNameToCursor[mb_strtolower($result['name'])];
                    $pluginCursor->id = (int) $result['id_plugin'];
                    $pluginCursor->active = $result['active'];
                }

            }

        }

        $languageCode = str_replace('_', '-', mb_strtolower(Context::getContext()->language->language_code));
        
        
        foreach ($pluginList as $key => &$plugin) {

            if (file_exists(_EPH_PLUGIN_DIR_ . $plugin->name . '/' . $plugin->name . '.php')) {
                require_once _EPH_PLUGIN_DIR_ . $plugin->name . '/' . $plugin->name . '.php';
                $image = 'includes/plugins/' . $plugin->name . '/logo.png';
            } else
            if (file_exists(_EPH_SPECIFIC_PLUGIN_DIR_ . $plugin->name . '/' . $plugin->name . '.php')) {
                require_once _EPH_SPECIFIC_PLUGIN_DIR_ . $plugin->name . '/' . $plugin->name . '.php';
                $image = 'includes/specific_plugins/' . $plugin->name . '/logo.png';
            }
            
            if(array_key_exists($plugin->name, $phenyxPlugins)) {
                unset($pluginList[$key]);
                continue;
            }
            


            $tmpPlugin = Adapter_ServiceLocator::get($plugin->name);

            if (isset($pluginsInstalled[$plugin->name])) {

                if (method_exists($tmpPlugin, 'reset')) {
                    $plugin->has_reset = true;
                } else {
                    $plugin->has_reset = false;
                }

                $plugin->removable = $tmpPlugin->removable;
                $plugin->config_controller = $tmpPlugin->config_controller;
                $plugin->id = $pluginsInstalled[$plugin->name]['id_plugin'];
                $plugin->installed = true;
                $plugin->database_version = $pluginsInstalled[$plugin->name]['version'];
                $plugin->interest = $pluginsInstalled[$plugin->name]['interest'];
                $plugin->enable_device = $pluginsInstalled[$plugin->name]['enable_device'];
                $plugin->active = $pluginsInstalled[$plugin->name]['active'];
                $plugin->dependencies = $tmpPlugin->dependencies;
                $plugin->image_link = $context->link->getBaseFrontLink() . $image;
                $plugin->is_ondisk = false;
                
                foreach($phenyxPlugins as $plug => $value) {
                    if($plug == $plugin->name) {
                        $plugin->is_ondisk = true;
                    }                    
                }
                
            } else {
                
                $plugin->removable = true;
                $plugin->installed = false;
                $plugin->database_version = 0;
                $plugin->interest = 0;
                $plugin->image_link = $context->link->getBaseFrontLink() . $image;
                $plugin->dependencies = $tmpPlugin->dependencies;
                $plugin->is_ondisk = false;
                
                foreach($phenyxPlugins as $plug => $value) {
                    if($plug == $plugin->name) {
                        $plugin->is_ondisk = true;
                    }                    
                }
            }
            
            foreach($phenyxDepends as $plug => $dependencies) {
                if($plug == $plugin->name && is_array($dependencies) && count($dependencies)) {
                    $plugin->warn = implode(', ', $dependencies);
                }
            }

        }

        if ($errors) {

            if (!isset(Context::getContext()->controller) && !Context::getContext()->controller->controller_name) {
                echo '<div class="alert error"><h3>' . Tools::displayError('The following plugin(s) could not be loaded') . ':</h3><ol>';

                foreach ($errors as $error) {
                    echo '<li>' . $error . '</li>';
                }

                echo '</ol></div>';
            } else {

                foreach ($errors as $error) {
                    Context::getContext()->controller->errors[] = $error;
                }

            }

        }

        return $pluginList;
    }
    
    public static function generatePluginZip($plugin) {
        
        $file = fopen("testgeneratePluginZip.txt","w");
        fwrite($file,$plugin.PHP_EOL);
        if(file_exists(_EPH_ROOT_DIR_.'/plugins/'.$plugin.'.zip')) {
            unlink(_EPH_ROOT_DIR_.'/plugins/'.$plugin.'.zip');
        }
        
        $rootPath = _EPH_PLUGIN_DIR_ . $plugin;
        $zip = new ZipArchive();
        $zip->open(_EPH_ROOT_DIR_.'/plugins/'.$plugin.'.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($rootPath),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $name => $file) {    
            if (!$file->isDir())  {        
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($rootPath) + 1);        
                $zip->addFile($filePath, $relativePath);
            }
        }

        $zip->close();
        
        return '/plugins/'.$plugin.'.zip';
    }
    

}
