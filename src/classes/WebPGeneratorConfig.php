<?php

/**
 * Class WebPGeneratorConfig
 */
class WebPGeneratorConfig {

    /** Required for PHP < 5.6 compatibility */
    protected static $instance;
    
    public static $className = 'WebPGeneratorConfig';

    public static $multiLang = [];
    
    public $context;

    const CONVERTER_CWEBP = 'cwebp';
    const CONVERTER_IMAGICK = 'imagick';
    const CONVERTER_GMAGICK = 'gmagick';
    const CONVERTER_GD = 'gd';
    const CONVERTER_EWWW = 'ewww';

    const CONVERTER_CWEBP_USE_NICE = 'WEBP_CONVERTER_CWEBP_USE_NICE';
    const CONVERTER_CWEBP_TRY_COMMON_SYSTEM_PATHS = 'WEBP_CONVERTER_CWEBP_TRY_COMMON_SYSTEM_PATHS';
    const CONVERTER_CWEBP_TRY_SUPPLIED_BINARY = 'WEBP_CONVERTER_CWEBP_TRY_SUPPLIED_BINARY';
    const CONVERTER_CWEBP_AUTO_FILTER = 'WEBP_CONVERTER_CWEBP_AUTO_FILTER';
    const CONVERTER_CWEBP_CMD_OPTIONS = 'WEBP_CONVERTER_CWEBP_CMD_OPTIONS';

    const CONVERTER_EWWW_API_KEY = 'WEBP_CONVERTER_EWWW_API_KEY';

    const CONFIG_COMMON_QUALITY = 'WEBP_COMMON_QUALITY';
    const CONFIG_COMMON_META_DATA = 'WEBP_CONFIG_COMMON_META_DATA';
    const CONFIG_COMMON_METHOD = 'WEBP_CONFIG_COMMON_METHOD';
    const CONFIG_COMMON_LOW_MEMORY = 'WEBP_CONFIG_LOW_MEMORY';
    const CONFIG_COMMON_LOSSLESS = 'WEBP_CONFIG_COMMON_LOSSLESS';

    const CONFIG_CONVERTER_TO_USE = 'WEBP_CONVERTOR_TO_USE';

    const DEMO_MODE = 'WEBCONVERTOR_DEMO_MODE';
    
    public function __construct() {

        $this->context = Context::getContext();
        if(!isset($this->context->phenyxConfig)) {
            $this->context->phenyxConfig = Configuration::getInstance();
        }
        $this->context->webp = $this;
        

    }
    
    public static function getInstance() {
       
		if (!isset(static::$instance)) {
			static::$instance = new WebPGeneratorConfig();
		}
        
		return static::$instance;
	}
    
    /**
     * Save a config value
     *
     * @param $key
     * @param $value
     *
     * @return bool
     */
    public function saveValue($key, $value) {

        return $this->context->phenyxConfig->updateValue($key, $value);
    }

    /**
     * Get configuration keys and values
     *
     * @return array
     */
    public function getConfigurationValues() {

        try {
            $class = new ReflectionClass(static::$className);
            $values = [];

            foreach ($class->getConstants() as $constant) {

                if (is_string($constant)) {

                    if (in_array($constant, static::$multiLang, true)) {
                        static::getMultilangConfigValues($constant, $values);
                    } else {
                        $values[$constant] = $this->context->phenyxConfig->get($constant);
                    }

                }

            }

            return $values;
        } catch (Exception $exception) {
            return [];
        }

    }

    /**
     * Get a multilang config key (mainly used with the HelperForm class)
     *
     * @param $key
     * @param $values
     */
    private function getMultilangConfigValues($key, &$values) {

        $languages = Language::getLanguages(false, false, false);
        $values[$key] = [];

        foreach ($languages as $language) {
            $values[$key][$language['id_lang']] = $this->context->phenyxConfig->get($key, $language['id_lang']);
        }

    }

    /**
     * Decide if a config key exists in the DB or not, doesn't really care about multilang
     *
     * @param null $configKey
     *
     * @return bool
     * @throws PhenyxDatabaseExceptionException
     */
    public function configExists($configKey = null) {

        $query = new \DbQuery();
        $query->select('count(*)');
        $query->from('configuration');
        $query->where("name = '" . pSQL($configKey) . "'");

        return (int) Db::getInstance()->executeS($query) > 0;
    }

    /**
     * @return array
     * @throws PhenyxException
     */
    public function getConverterSettings() {

        $config = $this->context->phenyxConfig->getMultiple([
            static::CONFIG_COMMON_QUALITY,
            static::CONFIG_COMMON_META_DATA,
            static::CONFIG_COMMON_METHOD,
            static::CONFIG_COMMON_LOW_MEMORY,
            static::CONFIG_COMMON_LOSSLESS,
            static::CONFIG_CONVERTER_TO_USE,
        ]);

        return [
            'quality'           => (int) $config[static::CONFIG_COMMON_QUALITY],
            'metadata'          => (string) $config[static::CONFIG_COMMON_META_DATA],
            'method'            => (int) $config[static::CONFIG_COMMON_METHOD],
            'low-memory'        => (bool) $config[static::CONFIG_COMMON_LOW_MEMORY],
            'lossless'          => (bool) $config[static::CONFIG_COMMON_LOSSLESS],
            'converters'        => explode(',', $config[static::CONFIG_CONVERTER_TO_USE]),
            'converter-options' => [
                'ewww'  => $this->getEwwwSettings(),
                'cwebp' => $this->getCWebpSettings(),
                'gd'    => ['skip-pngs' => false],
            ],
        ];
    }

    public function getEwwwSettings() {

        return [
            'key' => $this->context->phenyxConfig->get('WEBP_CONVERTER_EWWW_API_KEY'),
        ];
    }

    public function getCWebpSettings() {

        return [
            'use-nice'                   => (bool) $this->context->phenyxConfig->get(static::CONVERTER_CWEBP_USE_NICE),
            'try-common-system-paths'    => (bool) $this->context->phenyxConfig->get(static::CONVERTER_CWEBP_TRY_COMMON_SYSTEM_PATHS),
            'try-supplied-binary-for-os' => (bool) $this->context->phenyxConfig->get(static::CONVERTER_CWEBP_TRY_SUPPLIED_BINARY),
            'autofilter'                 => (bool) $this->context->phenyxConfig->get(static::CONVERTER_CWEBP_AUTO_FILTER),
            'command-line-options'       => $this->context->phenyxConfig->get(static::CONVERTER_CWEBP_CMD_OPTIONS),
        ];
    }

    public function updateRegenerationProgress($entityType, $index) {

        $this->context->phenyxConfig->updateValue("PC_WEBP_REGENERATE_$entityType", (int) $index);
    }

    public function getRegenerationProgress($entityType) {

        return (int) $this->context->phenyxConfig->get("PC_WEBP_REGENERATE_$entityType", null, null, null, 0);
    }

}
