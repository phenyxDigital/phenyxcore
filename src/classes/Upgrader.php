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
            $result =  $tab->add(true, false, true, $position);
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
        if(!$idMeta) {
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
    
}
