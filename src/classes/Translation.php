<?php
use \Curl\Curl;

/**
 * Class Translation
 *
 * @since 1.9.1.0
 */
class Translation extends PhenyxObjectModel {

    protected static $instance;

    /**
     * @see PhenyxObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'translation',
        'primary' => 'id_translation',
        'fields'  => [
            'iso_code'    => ['type' => self::TYPE_STRING, 'validate' => 'isLanguageIsoCode', 'required' => true, 'size' => 2],
            'origin'      => ['type' => self::TYPE_HTML, 'required' => true],
            'translation' => ['type' => self::TYPE_HTML, 'required' => true],
            'date_upd'    => ['type' => self::TYPE_HTML, 'required' => true],
        ],
    ];
    /** @var string Name */
    public $iso_code;

    public $origin;
    public $translation;
    public $date_upd;
    
    public $translations = [];

    public function __construct($id = null, $isos = null)  {
        
        $this->className = get_class($this);
        $this->context = Context::getContext();
        if (!PhenyxObjectModel::$hook_instance) {
            PhenyxObjectModel::$hook_instance = new Hook();
            $this->context->_hook = PhenyxObjectModel::$hook_instance;
        }
        if (!isset(PhenyxObjectModel::$loaded_classes[$this->className])) {
            $this->def = PhenyxObjectModel::getDefinition($this->className);            
            PhenyxObjectModel::$loaded_classes[$this->className] = get_object_vars($this);
            
        } else {
            foreach (PhenyxObjectModel::$loaded_classes[$this->className] as $key => $value) {
                $this->{$key}  = $value;
            }

        }
        
        $this->translations = $this->getGlobalTranslations($isos);   

        if ($id) {
            $this->id = $id;
            $entityMapper = Adapter_ServiceLocator::get("Adapter_EntityMapper");
            $entityMapper->load($this->id, null, $this, $this->def, false);           
		}

    }
    
    public function getGlobalTranslations($isos = null) {
        
        $translations = [];
        if(is_null($isos)) {
            $isos = Language::getLanguages(true);
        } else {
            $isos = Language::getLanguagesByIsos($isos);
        }
        
        foreach ($isos as $lang) {   
            
            $translations[$lang['iso_code']] = Db::getInstance()->executeS(
                (new DbQuery())
                ->select('*')
                ->from('translation')
                ->where('`iso_code` = \'' . trim($lang['iso_code']) . '\'')
            );
            
        }
        
        return $translations;
    }
    
    public function updateGlobalTranslations($translations) {
        if(is_array($translations)) {
            foreach($translations as $translation) {
                if(!empty($translation['translation'])) {
                    $id_translation = $this->getExistingObjectTranslation($translation['iso_code'], $translation['origin']);
            
                    if(!is_null($id_translation)) {
                        $translation = new Translation($id_translation);
                        $translation->translation = $translation['translation'];
                        $translation->update();
                    } else {
                        $translation = new Translation();
                        $translation->iso_code = $translation['iso_code'];
                        $translation->origin = $translation['origin'];
                        $translation->translation = $translation['translation'];
                        $translation->add();
                    }
                }
            
            }
        }
            
        
    }

    public static function getInstance() {

        if (!Translation::$instance) {
            Translation::$instance = new Translation();
        }

        return Translation::$instance;
    }

    public function add($autoDate = false, $nullValues = false) {
        
        $result = parent::add($autoDate, $nullValues);
        
        $this->context->translations = $this->getGlobalTranslations();
        
        return $result;

    }
    
    public function update($nullValues = false) {
        
        $result = parent::update($nullValues);

		$this->context->translations = $this->getGlobalTranslations();
        
        return $result;
	}

    public function getExistingTranslation($iso_code, $origin) {

       return Db::getInstance()->getValue(
            (new DbQuery())
            ->select('`translation`')
            ->from('translation')
            ->where('`iso_code` = \'' . trim($iso_code) . '\'')
            ->where('`origin` = \'' . bqSQL(trim($origin)) . '\'')
        );
    }
    
    public function getExistingObjectTranslation($iso_code, $origin) {

       $id_translation = Db::getInstance()->getValue(
            (new DbQuery())
            ->select('`id_translation`')
            ->from('translation')
            ->where('`iso_code` = \'' . trim($iso_code) . '\'')
            ->where('`origin` = \'' . bqSQL(trim($origin)) . '\'')
        );
        
        if(Validate::isUnsignedId($id_translation)) {
            return $id_translation;
        }
        
        return null;
    }

    public function getExistingTranslationByIso($iso_code) {

        $javareturn = [];

        $results = Db::getInstance()->executeS(
            (new DbQuery())
            ->select('*')
            ->from('translation')
            ->where('`iso_code` = \'' . trim($iso_code) . '\'')
        );

        foreach ($results as $result) {
            $javareturn[$result['origin']] = $result['translation'];
        }

        return $javareturn;
    }

    public static function addTranslation($object) {

        $object = Tools::jsonDecode(Tools::jsonEncode($object), true);

        $translation = new Translation();

        foreach ($object as $key => $value) {

            if (property_exists($translation, $key)) {
                $translation->{$key}
                = $value;
            }

        }

        $result = $translation->add();

        return $result;
    }

}
