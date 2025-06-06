<?php

use JSMin\JSMin;
use tubalmartin\CssMin\Minifier;

/**
 * Class Media
 *
 * @since 1.9.1.0
 */
class Media {

    protected static $instance;
    const FAVICON = 1;
    const FAVICON_57 = 2;
    const FAVICON_72 = 3;
    const FAVICON_114 = 4;
    const FAVICON_144 = 5;
    /** @since 1.0.2 Browsers update so fast, we have added this one for Android Chrome during a PATCH version */
    const FAVICON_192 = 7;
    const FAVICON_STORE_ICON = 6;

    public $context;

    // @codingStandardsIgnoreStart
    public static $jquery_ui_dependencies = [
        'ui.core'           => ['fileName' => 'jquery.ui.core.min.js', 'dependencies' => [], 'theme' => true],
        'ui.widget'         => ['fileName' => 'jquery.ui.widget.min.js', 'dependencies' => [], 'theme' => false],
        'ui.mouse'          => ['fileName' => 'jquery.ui.mouse.min.js', 'dependencies' => ['ui.core'], 'theme' => false],
        'ui.position'       => ['fileName' => 'jquery.ui.position.min.js', 'dependencies' => [], 'theme' => false],
        'ui.draggable'      => ['fileName' => 'jquery.ui.draggable.min.js', 'dependencies' => ['ui.core', 'ui.mouse'], 'theme' => false],
        'ui.droppable'      => ['fileName' => 'jquery.ui.droppable.min.js', 'dependencies' => ['ui.core', 'ui.mouse', 'ui.draggable'], 'theme' => false],
        'ui.resizable'      => ['fileName' => 'jquery.ui.resizable.min.js', 'dependencies' => ['ui.core', 'ui.mouse'], 'theme' => true],
        'ui.selectable'     => ['fileName' => 'jquery.ui.selectable.min.js', 'dependencies' => ['ui.core', 'ui.mouse'], 'theme' => true],
        'ui.sortable'       => ['fileName' => 'jquery.ui.sortable.min.js', 'dependencies' => ['ui.core', 'ui.mouse'], 'theme' => true],
        'ui.autocomplete'   => ['fileName' => 'jquery.ui.autocomplete.min.js', 'dependencies' => ['ui.core', 'ui.position', 'ui.menu'], 'theme' => true],
        'ui.button'         => ['fileName' => 'jquery.ui.button.min.js', 'dependencies' => ['ui.core'], 'theme' => true],
        'ui.dialog'         => ['fileName' => 'jquery.ui.dialog.min.js', 'dependencies' => ['ui.core', 'ui.position', 'ui.button'], 'theme' => true],
        'ui.menu'           => ['fileName' => 'jquery.ui.menu.min.js', 'dependencies' => ['ui.core', 'ui.position'], 'theme' => true],
        'ui.slider'         => ['fileName' => 'jquery.ui.slider.min.js', 'dependencies' => ['ui.core', 'ui.mouse'], 'theme' => true],
        'ui.spinner'        => ['fileName' => 'jquery.ui.spinner.min.js', 'dependencies' => ['ui.core', 'ui.button'], 'theme' => true],
        'ui.tabs'           => ['fileName' => 'jquery.ui.tabs.min.js', 'dependencies' => ['ui.core'], 'theme' => true],
        'ui.datepicker'     => ['fileName' => 'jquery.ui.datepicker.min.js', 'dependencies' => ['ui.core'], 'theme' => true],
        'ui.progressbar'    => ['fileName' => 'jquery.ui.progressbar.min.js', 'dependencies' => ['ui.core'], 'theme' => true],
        'ui.tooltip'        => ['fileName' => 'jquery.ui.tooltip.min.js', 'dependencies' => ['ui.core', 'ui.position', 'effects.core'], 'theme' => true],
        'ui.accordion'      => ['fileName' => 'jquery.ui.accordion.min.js', 'dependencies' => ['ui.core', 'effects.core'], 'theme' => true],
        'effects.core'      => ['fileName' => 'jquery.effects.core.min.js', 'dependencies' => [], 'theme' => false],
        'effects.blind'     => ['fileName' => 'jquery.effects.blind.min.js', 'dependencies' => ['effects.core'], 'theme' => false],
        'effects.bounce'    => ['fileName' => 'jquery.effects.bounce.min.js', 'dependencies' => ['effects.core'], 'theme' => false],
        'effects.clip'      => ['fileName' => 'jquery.effects.clip.min.js', 'dependencies' => ['effects.core'], 'theme' => false],
        'effects.drop'      => ['fileName' => 'jquery.effects.drop.min.js', 'dependencies' => ['effects.core'], 'theme' => false],
        'effects.explode'   => ['fileName' => 'jquery.effects.explode.min.js', 'dependencies' => ['effects.core'], 'theme' => false],
        'effects.fade'      => ['fileName' => 'jquery.effects.fade.min.js', 'dependencies' => ['effects.core'], 'theme' => false],
        'effects.fold'      => ['fileName' => 'jquery.effects.fold.min.js', 'dependencies' => ['effects.core'], 'theme' => false],
        'effects.highlight' => ['fileName' => 'jquery.effects.highlight.min.js', 'dependencies' => ['effects.core'], 'theme' => false],
        'effects.pulsate'   => ['fileName' => 'jquery.effects.pulsate.min.js', 'dependencies' => ['effects.core'], 'theme' => false],
        'effects.scale'     => ['fileName' => 'jquery.effects.scale.min.js', 'dependencies' => ['effects.core'], 'theme' => false],
        'effects.shake'     => ['fileName' => 'jquery.effects.shake.min.js', 'dependencies' => ['effects.core'], 'theme' => false],
        'effects.slide'     => ['fileName' => 'jquery.effects.slide.min.js', 'dependencies' => ['effects.core'], 'theme' => false],
        'effects.transfer'  => ['fileName' => 'jquery.effects.transfer.min.js', 'dependencies' => ['effects.core'], 'theme' => false],
    ];
    /**
     * @var string pattern used in replaceByAbsoluteURL
     */
    public static $pattern_callback = '#(url\((?![\'"]?(?:data:|//|https?:))(?:\'|")?)([^\)\'"]*)(?=[\'"]?\))#s';
    /**
     * @var string pattern used in packJSinHTML
     */
    public static $pattern_js = '/(<\s*script(?:\s+[^>]*(?:javascript|src)[^>]*)?\s*>)(.*)(<\s*\/script\s*[^>]*>)/Uims';
    /**
     * @var array list of javascript definitions
     */
    protected static $js_def = [];
    /**
     * @var array list of javascript inline scripts
     */
    protected static $inline_script = [];
    /**
     * @var array list of javascript external scripts
     */
    protected static $inline_script_src = [];
    /**
     * @var string used for preg_replace_callback parameter (avoid global)
     */
    protected static $current_css_file;
    protected static $pattern_keepinline = 'data-keepinline';
    // @codingStandardsIgnoreEnd

    public function __construct() {

        $this->context = Context::getContext();

        if (!isset($this->context->phenyxConfig)) {
            $this->context->phenyxConfig = Configuration::getInstance();
        }

        $this->context->media = $this;

    }

    public static function getInstance() {

        if (!isset(static::$instance)) {
            static::$instance = new Media();
        }

        return static::$instance;
    }

    public function minifyHTML($htmlContent) {

        if (strlen($htmlContent) > 0) {

            $htmlContent = str_replace(chr(194) . chr(160), '&nbsp;', $htmlContent);

            if (trim($minifiedContent = Minify_HTML::minify($htmlContent, ['cssMinifier', 'jsMinifier'])) != '') {
                $htmlContent = $minifiedContent;
            }

            return $htmlContent;
        }

        return false;
    }

    public function minifyHTMLpregCallback($pregMatches) {

        $args = [];
        preg_match_all('/[a-zA-Z0-9]+=[\"\\\'][^\"\\\']*[\"\\\']/is', $pregMatches[2], $args);
        $args = $args[0];
        sort($args);
        // if there is no args in the balise, we don't write a space (avoid previous : <title >, now : <title>)

        if (empty($args)) {
            $output = $pregMatches[1] . '>';
        } else {
            $output = $pregMatches[1] . ' ' . implode(' ', $args) . '>';
        }

        return $output;
    }

    public function packJSinHTML($htmlContent) {

        if (strlen($htmlContent) > 0) {
            ini_set('pcre.backtrack_limit', 10000000);
            $htmlContentCopy = $htmlContent;
            // @codingStandardsIgnoreStart

            if (!preg_match('/' . Media::$pattern_keepinline . '/', $htmlContent)) {
                $htmlContent = preg_replace_callback(
                    Media::$pattern_js,
                    [$this, 'packJSinHTMLpregCallback'],
                    $htmlContent,
                    $this->getBackTrackLimit()
                );
                // @codingStandardsIgnoreEnd

                // If the string is too big preg_replace return an error
                // In this case, we don't compress the content

                if (function_exists('preg_last_error') && preg_last_error() == PREG_BACKTRACK_LIMIT_ERROR) {

                    if (_EPH_MODE_DEV_) {
                        Tools::error_log('ERROR: PREG_BACKTRACK_LIMIT_ERROR in function packJSinHTML');
                    }

                    return $htmlContentCopy;
                }

            }

            return $htmlContent;
        }

        return false;
    }

    public function getBackTrackLimit() {

        static $limit = null;

        if ($limit === null) {
            $limit = @ini_get('pcre.backtrack_limit');

            if (!$limit) {
                $limit = -1;
            }

        }

        return $limit;
    }

    public function packJSinHTMLpregCallback($pregMatches) {

        if (!(trim($pregMatches[2]))) {
            return $pregMatches[0];
        }

        $pregMatches[1] = $pregMatches[1] . '/* <![CDATA[ */';
        $pregMatches[2] = $this->packJS($pregMatches[2]);
        $pregMatches[count($pregMatches) - 1] = '/* ]]> */' . $pregMatches[count($pregMatches) - 1];
        unset($pregMatches[0]);
        $output = implode('', $pregMatches);

        return $output;
    }

    public function packJS($jsContent) {

        if (!empty($jsContent)) {
            try {
                $jsContent = JSMin::minify($jsContent);
            } catch (PhenyxException $e) {

                if (_EPH_MODE_DEV_) {
                    echo $e->getMessage();
                    $file = fopen("testpackJS.txt", "a");
                    fwrite($file, $e->getMessage() . PHP_EOL);
                    fwrite($file, $jsContent . PHP_EOL);
                }

                return ';' . trim($jsContent, ';') . ';';
            }

        }

        return ';' . trim($jsContent, ';') . ';';
    }

    public function replaceByAbsoluteURL($matches) {

        if (array_key_exists(1, $matches) && array_key_exists(2, $matches)) {

            if (!preg_match('/^(?:https?:)?\/\//iUs', $matches[2])) {
                $protocolLink = Tools::getCurrentUrlProtocolPrefix();
                $sep = '/';
                // @codingStandardsIgnoreStart
                $tmp = $matches[2] == $sep ? $matches[2] : dirname(Media::$current_css_file) . $sep . ltrim($matches[2], $sep);
                // @codingStandardsIgnoreEnd
                $server = Tools::getMediaServer($tmp);

                return $matches[1] . $protocolLink . $server . $tmp;
            } else {
                return $matches[0];
            }

        }

        return false;
    }

    public function getJqueryPath($version = null, $folder = null, $minifier = true) {

        $addNoConflict = false;

        if ($version === null) {
            $version = _EPH_JQUERY_VERSION_;
        }

        //set default version
        else

        if (preg_match('/^([0-9\.]+)$/Ui', $version)) {
            $addNoConflict = true;
        } else {
            return false;
        }

        if ($folder === null) {
            $folder = _EPH_JS_DIR_ . 'jquery/';
        }

        //set default folder
        //check if file exists
        $file = $folder . 'jquery-' . $version . ($minifier ? '.min.js' : '.js');

        // remove EPH_BASE_URI on _EPH_ROOT_DIR_ for the following
        $urlData = parse_url($file);
        $fileUri = _EPH_ROOT_DIR_ . Tools::str_replace_once(__EPH_BASE_URI__, DIRECTORY_SEPARATOR, $urlData['path']);
        // check if js files exists, if not try to load query from ajax.googleapis.com

        $return = [];

        if (@filemtime($fileUri)) {
            $return[] = $this->getJSPath($file);
        } else {
            $return[] = $this->getJSPath(
                Tools::getCurrentUrlProtocolPrefix() . 'ajax.googleapis.com/ajax/libs/jquery/' . $version . '/jquery' . ($minifier ? '.min.js' : '.js')
            );
        }

        if ($addNoConflict) {
            $return[] = $this->getJSPath(
                Context::getContext()->company->getBaseURL(true, false) . _EPH_JS_DIR_ . 'jquery/jquery.noConflict.php?version=' . $version
            );
        }

        //$return[] = Media::getJSPath(_EPH_JS_DIR_ . 'jquery/jquery-migrate-3.3.2.min.js');

        return $return;
    }

    public function getJSPath($jsUri) {

        return $this->getMediaPath($jsUri);
    }

    public function getFaviconPath($type = self::FAVICON, $idCompany = null) {

        if (!$idCompany) {
            $idCompany = (int) $this->context->company->id;
        }

        switch ($type) {
        case static::FAVICON_57:
            $path = "favicon_57";
            $ext = "png";
            break;
        case static::FAVICON_72:
            $path = "favicon_72";
            $ext = "png";
            break;
        case static::FAVICON_114:
            $path = "favicon_114";
            $ext = "png";
            break;
        case static::FAVICON_144:
            $path = "favicon_144";
            $ext = "png";
            break;
        case static::FAVICON_192:
            $path = "favicon_192";
            $ext = "png";
            break;
        default:
            // Default favicon
            $path = "favicon";
            $ext = "ico";
            break;
        }

        return (string) $this->getMediaPath(_EPH_IMG_DIR_ . "{$path}.{$ext}");
    }

    public function getMediaPath($mediaUri, $cssMediaType = null) {

        if (is_array($mediaUri) || $mediaUri === null || empty($mediaUri)) {
            return false;
        }

        $urlData = parse_url($mediaUri);

        if (!is_array($urlData)) {
            return false;
        }

        if (!array_key_exists('host', $urlData)) {
            $mediaUri = '/' . ltrim(str_replace(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, _EPH_ROOT_DIR_), __EPH_BASE_URI__, $mediaUri), '/\\');
            // remove EPH_BASE_URI on _EPH_ROOT_DIR_ for the following
            $fileUri = _EPH_ROOT_DIR_ . Tools::str_replace_once(__EPH_BASE_URI__, DIRECTORY_SEPARATOR, $mediaUri);

            if (!@filemtime($fileUri) || @filesize($fileUri) === 0) {
                return false;
            }

            $mediaUri = str_replace('//', '/', $mediaUri);
        }

        if ($cssMediaType) {
            return [$mediaUri => $cssMediaType];
        }

        return $mediaUri;
    }

    public function getJqueryUIPath($component, $theme, $checkDependencies) {

        $uiPath = ['js' => [], 'css' => []];
        $folder = _EPH_JS_DIR_ . 'jquery/ui';
        $file = '/jquery.' . $component . '.min.js';
        $urlData = parse_url($folder . $file);
        $fileUri = _EPH_ROOT_DIR_ . Tools::str_replace_once(__EPH_BASE_URI__, DIRECTORY_SEPARATOR, $urlData['path']);
        $uiTmp = [];
        // @codingStandardsIgnoreStart

        if (isset(Media::$jquery_ui_dependencies[$component]) && Media::$jquery_ui_dependencies[$component]['theme'] && $checkDependencies) {
            // @codingStandardsIgnoreEnd

            $compCss = $this->getCSSPath($folder . _EPH_THEMES_DIR_ . $theme . '/jquery.' . $component . '.css');

            if (!empty($compCss) || $compCss) {
                $uiPath['css'] = array_merge($uiPath['css'], $compCss);
            }

        }

        // @codingStandardsIgnoreStart

        if ($checkDependencies && array_key_exists($component, static::$jquery_ui_dependencies)) {

            foreach (static::$jquery_ui_dependencies[$component]['dependencies'] as $dependency) {
                $uiTmp[] = $this->getJqueryUIPath($dependency, $theme, false);

                if (static::$jquery_ui_dependencies[$dependency]['theme']) {
                    $depCss = $this->getCSSPath($folder . _EPH_THEMES_DIR_ . $theme . '/jquery.' . $dependency . '.css');
                }

                // @codingStandardsIgnoreEnd

                if (isset($depCss) && (!empty($depCss) || $depCss)) {
                    $uiPath['css'] = array_merge($uiPath['css'], $depCss);
                }

            }

        }

        if (@filemtime($fileUri)) {

            if (!empty($uiTmp)) {

                foreach ($uiTmp as $ui) {

                    if (!empty($ui['js'])) {
                        $uiPath['js'][] = $ui['js'];
                    }

                    if (!empty($ui['css'])) {
                        $uiPath['css'][] = $ui['css'];
                    }

                }

                $uiPath['js'][] = $this->getJSPath($folder . $file);
            } else {
                $uiPath['js'] = $this->getJSPath($folder . $file);
            }

        }

        //add i18n file for datepicker

        if ($component == 'ui.datepicker') {

            if (!is_array($uiPath['js'])) {
                $uiPath['js'] = [$uiPath['js']];
            }

            $uiPath['js'][] = $this->getJSPath($folder . '/i18n/jquery.ui.datepicker-' . $this->context->language->iso_code . '.js');
        }

        return $uiPath;
    }

    public function getCSSPath($cssUri, $cssMediaType = 'all', $needRtl = true) {

        // RTL Ready: search and load rtl css file if it's not originally rtl

        if ($needRtl && $this->context->language->is_rtl) {
            $cssUriRtl = preg_replace('/(^[^.].*)(\.css)$/', '$1_rtl.css', $cssUri);
            $rtlMedia = $this->getMediaPath($cssUriRtl, $cssMediaType);

            if ($rtlMedia != false) {
                return $rtlMedia;
            }

        }

        // End RTL
        return $this->getMediaPath($cssUri, $cssMediaType);
    }

    public function getJqueryPluginPath($name, $folder = null) {

        $pluginPath = ['js' => [], 'css' => []];

        if ($folder === null) {
            $folder = _EPH_JS_DIR_ . 'jquery/plugins/';
        }

        //set default folder

        $file = 'jquery.' . $name . '.js';
        $urlData = parse_url($folder);
        $fileUri = _EPH_ROOT_DIR_ . Tools::str_replace_once(__EPH_BASE_URI__, DIRECTORY_SEPARATOR, $urlData['path']);

        if (@file_exists($fileUri . $file)) {
            $pluginPath['js'] = $this->getJSPath($folder . $file);
        } else

        if (@file_exists($fileUri . $name . '/' . $file)) {
            $pluginPath['js'] = $this->getJSPath($folder . $name . '/' . $file);
        } else {
            return false;
        }

        $pluginPath['css'] = $this->getJqueryPluginCSSPath($name, $folder);

        return $pluginPath;
    }

    public function getJqueryPluginCSSPath($name, $folder = null) {

        if ($folder === null) {
            $folder = _EPH_JS_DIR_ . 'jquery/plugins/';
        }

        //set default folder
        $file = 'jquery.' . $name . '.css';
        $urlData = parse_url($folder);
        $fileUri = _EPH_ROOT_DIR_ . Tools::str_replace_once(__EPH_BASE_URI__, DIRECTORY_SEPARATOR, $urlData['path']);

        if (@file_exists($fileUri . $file)) {
            return $this->getCSSPath($folder . $file);
        } else

        if (@file_exists($fileUri . $name . '/' . $file)) {
            return $this->getCSSPath($folder . $name . '/' . $file);
        } else {
            return false;
        }

    }

    public function cccCss($cssFiles, $cachePath = null) {

        //inits
        $cssFilesByMedia = [];
        $externalCssFiles = [];
        $compressedCssFiles = [];
        $compressedCssFilesNotFound = [];
        $compressedCssFilesInfos = [];
        $protocolLink = Tools::getCurrentUrlProtocolPrefix();
        //if cache_path not specified, set curent theme cache folder
        $cachePath = $cachePath ? $cachePath : $this->context->theme->path . 'cache/';
        $cssSplitNeedRefresh = false;

        // group css files by media

        foreach ($cssFiles as $filename => $media) {

            if (!array_key_exists($media, $cssFilesByMedia)) {
                $cssFilesByMedia[$media] = [];
            }

            $infos = [];
            $infos['uri'] = $filename;
            $urlData = parse_url($filename);

            if (array_key_exists('host', $urlData)) {
                $externalCssFiles[$filename] = $media;
                continue;
            }

            $infos['path'] = _EPH_ROOT_DIR_ . Tools::str_replace_once(__EPH_BASE_URI__, '/', $urlData['path']);

            if (!@filemtime($infos['path'])) {
                $infos['path'] = _EPH_CORE_DIR_ . Tools::str_replace_once(__EPH_BASE_URI__, '/', $urlData['path']);
            }

            $cssFilesByMedia[$media]['files'][] = $infos;

            if (!array_key_exists('date', $cssFilesByMedia[$media])) {
                $cssFilesByMedia[$media]['date'] = 0;
            }

            $cssFilesByMedia[$media]['date'] = max(
                (int) @filemtime($infos['path']),
                $cssFilesByMedia[$media]['date']
            );

            if (!array_key_exists($media, $compressedCssFilesInfos)) {
                $compressedCssFilesInfos[$media] = ['key' => ''];
            }

            $compressedCssFilesInfos[$media]['key'] .= $filename;
        }

        // get compressed css file infos
        $version = (int) $this->context->phenyxConfig->get('EPH_CCCCSS_VERSION');

        foreach ($compressedCssFilesInfos as $media => &$info) {
            $key = md5($info['key'] . $protocolLink);
            $filename = $cachePath . 'v_' . $version . '_' . $key . '_' . $media . '.css';

            $info = [
                'key'  => $key,
                'date' => (int) @filemtime($filename),
            ];
        }

        foreach ($cssFilesByMedia as $media => $mediaInfos) {

            if ($mediaInfos['date'] > $compressedCssFilesInfos[$media]['date']) {

                if ($compressedCssFilesInfos[$media]['date']) {
                    $this->context->phenyxConfig->updateValue('EPH_CCCCSS_VERSION', ++$version);
                    break;
                }

            }

        }

        // aggregate and compress css files content, write new caches files
        $importUrl = [];

        foreach ($cssFilesByMedia as $media => $mediaInfos) {
            $cacheFilename = $cachePath . 'v_' . $version . '_' . $compressedCssFilesInfos[$media]['key'] . '_' . $media . '.css';

            if ($mediaInfos['date'] > $compressedCssFilesInfos[$media]['date']) {
                $cssSplitNeedRefresh = true;
                $cacheFilename = $cachePath . 'v_' . $version . '_' . $compressedCssFilesInfos[$media]['key'] . '_' . $media . '.css';
                $compressedCssFiles[$media] = '';

                foreach ($mediaInfos['files'] as $fileInfos) {

                    if (file_exists($fileInfos['path'])) {
                        $compressedCssFiles[$media] .= $this->minifyCSS(file_get_contents($fileInfos['path']), $fileInfos['uri'], $importUrl);
                    } else {
                        $compressedCssFilesNotFound[] = $fileInfos['path'];
                    }

                }

                if (!empty($compressedCssFilesNotFound)) {
                    $content = '/* WARNING ! file(s) not found : "' . implode(',', $compressedCssFilesNotFound) . '" */' . "\n" . $compressedCssFiles[$media];
                } else {
                    $content = $compressedCssFiles[$media];
                }

                $content = '@charset "UTF-8";' . "\n" . $content;
                $content = implode('', $importUrl) . $content;
                file_put_contents($cacheFilename, $content);
                chmod($cacheFilename, 0777);
            }

            $compressedCssFiles[$media] = $cacheFilename;
        }

        // rebuild the original css_files array
        $cssFiles = [];

        foreach ($compressedCssFiles as $media => $filename) {
            $url = str_replace($this->context->theme->path, $this->context->theme->localpath . '/', $filename);
            $cssFiles[$protocolLink . Tools::getMediaServer($url) . $url] = $media;
        }

        $compiledCss = array_merge($externalCssFiles, $cssFiles);

        return $compiledCss;
    }

    public function admincccCss($cssFiles, $cachePath = null) {

        //inits
        $cssFilesByMedia = [];
        $externalCssFiles = [];
        $compressedCssFiles = [];
        $compressedCssFilesNotFound = [];
        $compressedCssFilesInfos = [];
        $protocolLink = Tools::getCurrentUrlProtocolPrefix();
        //if cache_path not specified, set curent theme cache folder
        $cachePath = $cachePath ? $cachePath : _EPH_BO_ALL_THEMES_DIR_ . 'backend/cache/';
        $cssSplitNeedRefresh = false;

        // group css files by media

        if (is_array($cssFiles)) {

            foreach ($cssFiles as $filename => $media) {

                if (!array_key_exists($media, $cssFilesByMedia)) {
                    $cssFilesByMedia[$media] = [];
                }

                $infos = [];
                $infos['uri'] = $filename;
                $urlData = parse_url($filename);

                if (array_key_exists('host', $urlData)) {
                    $externalCssFiles[$filename] = $media;
                    continue;
                }

                $infos['path'] = _EPH_ROOT_DIR_ . Tools::str_replace_once(__EPH_BASE_URI__, '/', $urlData['path']);

                if (!@filemtime($infos['path'])) {
                    $infos['path'] = _EPH_CORE_DIR_ . Tools::str_replace_once(__EPH_BASE_URI__, '/', $urlData['path']);
                }

                $cssFilesByMedia[$media]['files'][] = $infos;

                if (!array_key_exists('date', $cssFilesByMedia[$media])) {
                    $cssFilesByMedia[$media]['date'] = 0;
                }

                $cssFilesByMedia[$media]['date'] = max(
                    (int) @filemtime($infos['path']),
                    $cssFilesByMedia[$media]['date']
                );

                if (!array_key_exists($media, $compressedCssFilesInfos)) {
                    $compressedCssFilesInfos[$media] = ['key' => ''];
                }

                $compressedCssFilesInfos[$media]['key'] .= $filename;
            }

        }

        // get compressed css file infos
        $version = (int) $this->context->phenyxConfig->get('EPH_ADCCCCSS_VERSION');

        foreach ($compressedCssFilesInfos as $media => &$info) {
            $key = md5($info['key'] . $protocolLink);
            $filename = $cachePath . 'v_' . $version . '_' . $key . '_' . $media . '.css';

            $info = [
                'key'  => $key,
                'date' => (int) @filemtime($filename),
            ];
        }

        foreach ($cssFilesByMedia as $media => $mediaInfos) {

            if ($mediaInfos['date'] > $compressedCssFilesInfos[$media]['date']) {

                if ($compressedCssFilesInfos[$media]['date']) {
                    Context::getContext()->phenyxConfig->updateValue('EPH_ADCCCCSS_VERSION', ++$version);
                    break;
                }

            }

        }

        // aggregate and compress css files content, write new caches files
        $importUrl = [];

        foreach ($cssFilesByMedia as $media => $mediaInfos) {
            $cacheFilename = $cachePath . 'v_' . $version . '_' . $compressedCssFilesInfos[$media]['key'] . '_' . $media . '.css';

            if ($mediaInfos['date'] > $compressedCssFilesInfos[$media]['date']) {
                $cssSplitNeedRefresh = true;
                $cacheFilename = $cachePath . 'v_' . $version . '_' . $compressedCssFilesInfos[$media]['key'] . '_' . $media . '.css';
                $compressedCssFiles[$media] = '';

                foreach ($mediaInfos['files'] as $fileInfos) {

                    if (file_exists($fileInfos['path'])) {
                        $compressedCssFiles[$media] .= $this->minifyCSS(file_get_contents($fileInfos['path']), $fileInfos['uri'], $importUrl);
                    } else {
                        $compressedCssFilesNotFound[] = $fileInfos['path'];
                    }

                }

                if (!empty($compressedCssFilesNotFound)) {
                    $content = '/* WARNING ! file(s) not found : "' . implode(',', $compressedCssFilesNotFound) . '" */' . "\n" . $compressedCssFiles[$media];
                } else {
                    $content = $compressedCssFiles[$media];
                }

                $content = '@charset "UTF-8";' . "\n" . $content;
                $content = implode('', $importUrl) . $content;
                file_put_contents($cacheFilename, $content);
                chmod($cacheFilename, 0777);
            }

            $compressedCssFiles[$media] = $cacheFilename;
        }

        // rebuild the original css_files array
        $cssFiles = [];

        foreach ($compressedCssFiles as $media => $filename) {
            $url = str_replace(_EPH_ROOT_DIR_, '', $filename);
            $cssFiles[$protocolLink . Tools::getMediaServer($url) . $url] = $media;
        }

        $compiledCss = array_merge($externalCssFiles, $cssFiles);

        return $compiledCss;
    }

    public function minifyCSS($cssContent, $fileuri = false, &$importUrl = []) {

        // @codingStandardsIgnoreStart
        Media::$current_css_file = $fileuri;
        $obj = new Minifier;
        //return $obj->run($cssContent);
        // @codingStandardsIgnoreEnd

        if (strlen($cssContent) > 0) {
            $limit = $this->getBackTrackLimit();
            $cssContent = preg_replace('#/\*.*?\*/#s', '', $cssContent, $limit);

            if (!is_null($cssContent)) {
                $cssContent = preg_replace_callback(Media::$pattern_callback, [$this, 'replaceByAbsoluteURL'], $cssContent, $limit);
                $cssContent = preg_replace_callback('#(AlphaImageLoader\(src=\')([^\']*\',)#s', [$this, 'replaceByAbsoluteURL'], $cssContent);
                preg_match_all('#@(import|charset) .*?;#i', $cssContent, $m);

                for ($i = 0, $total = count($m[0]); $i < $total; $i++) {

                    if (isset($m[1][$i]) && $m[1][$i] == 'import') {
                        $importUrl[] = $m[0][$i];
                    }

                    $cssContent = str_replace($m[0][$i], '', $cssContent);
                }

                return trim($obj->run($cssContent));

            }

            return $cssContent;
        }

        return false;
    }

    public function cccJS($jsFiles) {

        //inits
        $compressedJsFilesNotFound = [];
        $jsFilesInfos = [];
        $jsFilesDate = 0;
        $compressedJsFilename = '';
        $jsExternalFiles = [];
        $protocolLink = Tools::getCurrentUrlProtocolPrefix();
        $theme = Context::getContext()->theme;
        $cachePath = $this->context->theme->path . 'cache/';

        // get js files infos

        if (is_array($jsFiles)) {

            foreach ($jsFiles as $filename) {

                if (Validate::isAbsoluteUrl($filename)) {
                    $jsExternalFiles[] = $filename;
                } else {
                    $infos = [];
                    $infos['uri'] = $filename;
                    $urlData = parse_url($filename);
                    $infos['path'] = _EPH_ROOT_DIR_ . Tools::str_replace_once(__EPH_BASE_URI__, '/', $urlData['path']);

                    if (!@filemtime($infos['path'])) {
                        $infos['path'] = _EPH_CORE_DIR_ . Tools::str_replace_once(__EPH_BASE_URI__, '/', $urlData['path']);
                    }

                    $jsFilesInfos[] = $infos;

                    $jsFilesDate = max(
                        (int) @filemtime($infos['path']),
                        $jsFilesDate
                    );
                    $compressedJsFilename .= $filename;
                }

            }

        }

        // get compressed js file infos
        $compressedJsFilename = md5($compressedJsFilename);
        $version = (int) $this->context->phenyxConfig->get('EPH_CCCJS_VERSION');
        $compressedJsPath = $cachePath . 'v_' . $version . '_' . $compressedJsFilename . '.js';
        $compressedJsFileDate = (int) @filemtime($compressedJsPath);

        // aggregate and compress js files content, write new caches files

        if ($jsFilesDate > $compressedJsFileDate) {

            if ($compressedJsFileDate) {
                $this->context->phenyxConfig->updateValue('EPH_CCCJS_VERSION', ++$version);
            }

            $compressedJsPath = $cachePath . 'v_' . $version . '_' . $compressedJsFilename . '.js';
            $content = '';

            foreach ($jsFilesInfos as $fileInfos) {

                if (file_exists($fileInfos['path'])) {
                    $tmpContent = file_get_contents($fileInfos['path']);

                    if (preg_match('@\.(min|pack)\.[^/]+$@', $fileInfos['path'], $matches)) {
                        $content .= preg_replace('/\/\/@\ssourceMappingURL\=[_a-zA-Z0-9-.]+\.' . $matches[1] . '\.map\s+/', '', $tmpContent);
                    } else {
                        $content .= $this->packJS($tmpContent);
                    }

                } else {
                    $compressedJsFilesNotFound[] = $fileInfos['path'];
                }

            }

            if (!empty($compressedJsFilesNotFound)) {
                $content = '/* WARNING ! file(s) not found : "' . implode(',', $compressedJsFilesNotFound) . '" */' . "\n" . $content;
            }

            file_put_contents($compressedJsPath, $content);
            chmod($compressedJsPath, 0777);
        }

        // rebuild the original js_files array
        $url = '';

        if (strpos($compressedJsPath, _EPH_ROOT_DIR_) !== false) {
            $url = str_replace(_EPH_ROOT_DIR_ . '/', __EPH_BASE_URI__, $compressedJsPath);
        }

        if (strpos($compressedJsPath, _EPH_CORE_DIR_) !== false) {
            $url = str_replace(_EPH_CORE_DIR_ . '/', __EPH_BASE_URI__, $compressedJsPath);
        }

        return array_merge($jsExternalFiles, [$protocolLink . Tools::getMediaServer($url) . $url]);
    }

    public function admincccJS($jsFiles) {

        //inits
        $compressedJsFilesNotFound = [];
        $jsFilesInfos = [];
        $jsFilesDate = 0;
        $compressedJsFilename = '';
        $jsExternalFiles = [];
        $protocolLink = Tools::getCurrentUrlProtocolPrefix();
        $cachePath = _EPH_BO_ALL_THEMES_DIR_ . 'backend/cache/';

        // get js files infos

        if (is_array($jsFiles)) {

            foreach ($jsFiles as $filename) {

                if (Validate::isAbsoluteUrl($filename)) {
                    $jsExternalFiles[] = $filename;
                } else {
                    $infos = [];
                    $infos['uri'] = $filename;
                    $urlData = parse_url($filename);
                    $infos['path'] = _EPH_ROOT_DIR_ . Tools::str_replace_once(__EPH_BASE_URI__, '/', $urlData['path']);

                    if (!@filemtime($infos['path'])) {
                        $infos['path'] = _EPH_CORE_DIR_ . Tools::str_replace_once(__EPH_BASE_URI__, '/', $urlData['path']);
                    }

                    $jsFilesInfos[] = $infos;

                    $jsFilesDate = max(
                        (int) @filemtime($infos['path']),
                        $jsFilesDate
                    );
                    $compressedJsFilename .= $filename;
                }

            }

        }

        // get compressed js file infos
        $compressedJsFilename = md5($compressedJsFilename);
        $version = (int) $this->context->phenyxConfig->get('EPH_ADCCCJS_VERSION');
        $compressedJsPath = $cachePath . 'v_' . $version . '_' . $compressedJsFilename . '.js';
        $compressedJsFileDate = (int) @filemtime($compressedJsPath);

        // aggregate and compress js files content, write new caches files

        if ($jsFilesDate > $compressedJsFileDate) {

            if ($compressedJsFileDate) {
                $this->context->phenyxConfig->updateValue('EPH_ADCCCJS_VERSION', ++$version);
            }

            $compressedJsPath = $cachePath . 'v_' . $version . '_' . $compressedJsFilename . '.js';
            $content = '';

            foreach ($jsFilesInfos as $fileInfos) {

                if (file_exists($fileInfos['path'])) {

                    $tmpContent = file_get_contents($fileInfos['path']);

                    if (preg_match('@\.(min|pack)\.[^/]+$@', $fileInfos['path'], $matches)) {
                        $content .= preg_replace('/\/\/@\ssourceMappingURL\=[_a-zA-Z0-9-.]+\.' . $matches[1] . '\.map\s+/', '', $tmpContent);
                    } else {

                        $content .= $this->packJS($tmpContent);
                    }

                } else {
                    $compressedJsFilesNotFound[] = $fileInfos['path'];
                }

            }

            if (!empty($compressedJsFilesNotFound)) {
                $content = '/* WARNING ! file(s) not found : "' . implode(',', $compressedJsFilesNotFound) . '" */' . "\n" . $content;
            }

            file_put_contents($compressedJsPath, $content);
            chmod($compressedJsPath, 0777);
        }

        // rebuild the original js_files array
        $url = '';

        if (strpos($compressedJsPath, _EPH_ROOT_DIR_) !== false) {
            $url = str_replace(_EPH_ROOT_DIR_ . '/', __EPH_BASE_URI__, $compressedJsPath);
        }

        if (strpos($compressedJsPath, _EPH_CORE_DIR_) !== false) {
            $url = str_replace(_EPH_CORE_DIR_ . '/', __EPH_BASE_URI__, $compressedJsPath);
        }

        return array_merge($jsExternalFiles, [$protocolLink . Tools::getMediaServer($url) . $url]);
    }

    public function clearCache() {

        if (!$this->context->phenyxConfig->get('EPH_KEEP_CCC_FILES')) {
            $theme = $this->context->theme;

            if (is_dir($theme->path . 'cache')) {
                $iterator = new AppendIterator();
                $iterator->append(new DirectoryIterator($theme->path . 'cache/'));

                foreach ($iterator as $file) {
                    $filePath = $file->getPathname();

                    if (in_array($file->getFilename(), ['.', '..', 'index.php'])) {
                        continue;
                    }

                    unlink($filePath);
                }

            }

        }

        $version = (int) $this->context->phenyxConfig->get('EPH_CCCJS_VERSION');
        $this->context->phenyxConfig->updateValue('EPH_CCCJS_VERSION', ++$version);
        $version = (int) $this->context->phenyxConfig->get('EPH_CCCCSS_VERSION');
        $this->context->phenyxConfig->updateValue('EPH_CCCCSS_VERSION', ++$version);
    }

    public function clearAdminCache() {

        if (!$this->context->phenyxConfig->get('EPH_KEEP_CCC_FILES')) {

            if (is_dir(_EPH_BO_ALL_THEMES_DIR_ . 'backend/cache')) {
                $iterator = new AppendIterator();
                $iterator->append(new DirectoryIterator(_EPH_BO_ALL_THEMES_DIR_ . 'backend/cache/'));

                foreach ($iterator as $file) {
                    $filePath = $file->getPathname();

                    if (in_array($file->getFilename(), ['.', '..', 'index.php'])) {
                        continue;
                    }

                    unlink($filePath);
                }

            }

        }

        $version = (int) $this->context->phenyxConfig->get('EPH_CCCJS_VERSION');
        $this->context->phenyxConfig->updateValue('EPH_CCCJS_VERSION', ++$version);
        $version = (int) $this->context->phenyxConfig->get('EPH_CCCCSS_VERSION');
        $this->context->phenyxConfig->updateValue('EPH_CCCCSS_VERSION', ++$version);
    }

    public function getJsDef() {

        // @codingStandardsIgnoreStart
        ksort(Media::$js_def);

        return Media::$js_def;
        // @codingStandardsIgnoreEnd
    }

    public function getInlineScript() {

        // @codingStandardsIgnoreStart
        return Media::$inline_script;
        // @codingStandardsIgnoreEnd
    }

    public function addJsDef($jsDef) {

        if (is_array($jsDef)) {

            foreach ($jsDef as $key => $js) {
                // @codingStandardsIgnoreStart
                Media::$js_def[$key] = $js;
                // @codingStandardsIgnoreEnd
            }

        } else

        if ($jsDef) {
            // @codingStandardsIgnoreStart
            Media::$js_def[] = $jsDef;
            // @codingStandardsIgnoreEnd
        }

    }

    public function addJsDefL($params, $content, $smarty = null, &$repeat = false) {

        if (!$repeat && isset($params) && !is_null($content) && mb_strlen($content)) {

            if (!is_array($params)) {
                $params = (array) $params;
            }

            foreach ($params as $param) {
                // @codingStandardsIgnoreStart
                Media::$js_def[$param] = $content;
                // @codingStandardsIgnoreEnd
            }

        }

    }

    public function deferTagOutput($tag, $output) {

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        @$dom->loadHTML(($output));
        libxml_use_internal_errors(false);
        $tag = $dom->getElementsByTagName($tag);

        if ($tag && 0 < $tag->length) {
            $tag = $tag->item(0);
            return $dom->savehtml($tag);
        }

    }

    public function deferIdOutput($tag, $output) {

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        @$dom->loadHTML(($output));
        libxml_use_internal_errors(false);
        $tag = $dom->getElementById($tag);
        $html = '';

        if (isset($tag->childNodes)) {

            foreach ($tag->childNodes as $node) {
                $html .= $dom->saveXML($node, LIBXML_NOEMPTYTAG);
            }

        }

        return $html;
    }

    public function deferInlineScripts($output) {

        /* Try to enqueue in js_files inline scripts with src but without conditionnal comments */
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        @$dom->loadHTML(($output));
        libxml_use_internal_errors(false);
        $scripts = $dom->getElementsByTagName('script');

        if (is_object($scripts) && $scripts->length) {

            foreach ($scripts as $script) {
                /** @var DOMElement $script */

                if ($datadefer = $script->getAttribute('data-keepinline')) {
                    continue;
                }

                if ($src = $script->getAttribute('src')) {

                    if (substr($src, 0, 2) == '//') {
                        $src = Tools::getCurrentUrlProtocolPrefix() . substr($src, 2);
                    }

                    $patterns = [
                        '#code\.jquery\.com/jquery-([0-9\.]+)(\.min)*\.js$#Ui',
                        '#ajax\.googleapis\.com/ajax/libs/jquery/([0-9\.]+)/jquery(\.min)*\.js$#Ui',
                        '#ajax\.aspnetcdn\.com/ajax/jquery/jquery-([0-9\.]+)(\.min)*\.js$#Ui',
                        '#cdnjs\.cloudflare\.com/ajax/libs/jquery/([0-9\.]+)/jquery(\.min)*\.js$#Ui',
                        '#/jquery-([0-9\.]+)(\.min)*\.js$#Ui',
                    ];

                    foreach ($patterns as $pattern) {
                        $matches = [];

                        if (preg_match($pattern, $src, $matches)) {
                            $minifier = $version = false;

                            if (isset($matches[2]) && $matches[2]) {
                                $minifier = (bool) $matches[2];
                            }

                            if (isset($matches[1]) && $matches[1]) {
                                $version = $matches[1];
                            }

                            if ($version) {

                                // @codingStandardsIgnoreStart
                                array_push(Media::$inline_script_src, $src);
                                // @codingStandardsIgnoreEnd
                            }

                        }

                    }

                    // @codingStandardsIgnoreStart

                    if (!in_array($src, Media::$inline_script_src) && !$script->getAttribute(Media::$pattern_keepinline)) {
                        // @codingStandardsIgnoreEnd
                        $this->context->controller->addJS($src);
                    }

                }

            }

        }

        // @codingStandardsIgnoreStart
        $output = preg_replace_callback(Media::$pattern_js, [$this, 'deferScript'], $output);
        // @codingStandardsIgnoreEnd

        return $output;
    }

    public function deferScript($matches) {

        if (!is_array($matches)) {
            return false;
        }

        $inline = '';

        if (isset($matches[0])) {
            $original = trim($matches[0]);
        } else {
            $original = '';
        }

        if (isset($matches[2])) {
            $inline = trim($matches[2]);
        }

        /* This is an inline script, add its content to inline scripts stack then remove it from content */
        // @codingStandardsIgnoreStart

        if (!empty($inline) && preg_match(Media::$pattern_js, $original) !== false && !preg_match('/' . Media::$pattern_keepinline . '/', $original) && Media::$inline_script[] = $inline) {
            // @codingStandardsIgnoreEnd
            return '';
        }

        /* This is an external script, if it already belongs to js_files then remove it from content */
        preg_match('/src\s*=\s*["\']?([^"\']*)[^>]/ims', $original, $results);

        if (array_key_exists(1, $results)) {

            if (substr($results[1], 0, 2) == '//') {
                $protocolLink = Tools::getCurrentUrlProtocolPrefix();
                $results[1] = $protocolLink . ltrim($results[1], '/');
            }

            // @codingStandardsIgnoreStart

            if (in_array($results[1], $this->context->controller->js_files) || in_array($results[1], Media::$inline_script_src)) {
                // @codingStandardsIgnoreEnd
                return '';
            }

        }

        /* return original string because no match was found */

        return "\n" . $original;
    }

}
