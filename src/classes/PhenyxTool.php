<?php

use Google\Cloud\Translate\V2\TranslateClient;
use PHPMailer\PHPMailer\PHPMailer;
use PHPSQLParser\PHPSQLParser;

/**
 * Class ToolsCore
 *
 * @since 1.9.1.0
 */
class PhenyxTool {

    protected static $instance;
    /**
     * Bootstring parameter values
     *
     */
    const PUNYCODE_BASE = 36;
    const PUNYCODE_TMIN = 1;
    const PUNYCODE_TMAX = 26;
    const PUNYCODE_SKEW = 38;
    const PUNYCODE_DAMP = 700;
    const PUNYCODE_INITIAL_BIAS = 72;
    const PUNYCODE_INITIAL_N = 128;
    const PUNYCODE_PREFIX = 'xn--';
    const PUNYCODE_DELIMITER = '-';

    // @codingStandardsIgnoreStart
    public $round_mode = null;
    protected static $file_exists_cache = [];
    protected static $_forceCompile;
    protected static $_caching;
    protected static $_user_plateform;
    protected static $_user_browser;
    protected static $_cache_nb_media_servers = null;
    protected static $is_addons_up = true;

    public $context;

    public function __construct() {

        $this->context = Context::getContext();

        if (!isset($this->context->_tools)) {
            $this->context->_tools = $this;
        }

        if (!isset($this->context->phenyxConfig)) {
            $this->context->phenyxConfig = Configuration::getInstance();
        }

    }

    public static function getInstance() {

        if (!isset(static::$instance)) {
            static::$instance = new PhenyxTool();
        }

        return static::$instance;
    }

    public function checkLicense($purchaseKey, $website) {

        $key = $this->context->phenyxConfig->get('_EPHENYX_LICENSE_KEY_');

        if ($key == $purchaseKey && $this->context->company->domain_ssl == $website) {
            return true;
        }

        return false;

    }

    public function passwdGen($length = 8, $flag = 'ALPHANUMERIC') {

        $length = (int) $length;

        if ($length <= 0) {
            return false;
        }

        switch ($flag) {
        case 'NUMERIC':
            $str = '0123456789';
            break;
        case 'NO_NUMERIC':
            $str = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            break;
        case 'RANDOM':
            $numBytes = (int) ceil($length * 0.75);
            $bytes = $this->getBytes($numBytes);

            return substr(rtrim(base64_encode($bytes), '='), 0, $length);
        case 'ALPHANUMERIC':
        default:
            $str = 'abcdefghijkmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            break;
        }

        $bytes = $this->getBytes($length);
        $position = 0;
        $result = '';

        for ($i = 0; $i < $length; $i++) {
            $position = ($position + ord($bytes[$i])) % strlen($str);
            $result .= $str[$position];
        }

        return $result;
    }
    
    public function getCmsFullPath($cms) {
        
        if (!is_object($cms)) {
            $cms = new CMS($cms, $this->context->language->id);
        }
        $tag = null;
        if($cms->id_parent > 1) {
            $parent = new CMS($cms->id_parent, $this->context->language->id);
        } else {
            return '<span class="navigation_cms">'.$cms->meta_title.'</span>';
        }
        $ajax_mode = $this->context->phenyxConfig->get('EPH_FRONT_AJAX') ? 1 : 0;
        
        $cms_ajax_mode = $this->context->phenyxConfig->get('EPH_CMS_AJAX') ? 1 : 0;
        $pipe = $this->context->phenyxConfig->get('EPH_NAVIGATION_PIPE');
        if (empty($pipe)) {
            $pipe = '>';
        }
        if($ajax_mode && $cms_ajax_mode) {
            return '<span class="navigation-pipe"><a href="javascript:void(0)"  onClick="openAjaxCms('.$parent->id.')" title="'.htmlentities($cms->meta_title, ENT_NOQUOTES, 'UTF-8').'" title="'.htmlentities($cms->meta_title, ENT_NOQUOTES, 'UTF-8').'">'.$parent->meta_title.'</a></span><span class="navigation-pipe">'.$pipe.'</span><span class="navigation_cms">'.$cms->meta_title.'</span>';
        }
        return '<span class="navigation-pipe"><a href="'.$this->context->_link->getCMSLink($parent).'"  title="'.htmlentities($cms->meta_title, ENT_NOQUOTES, 'UTF-8').'">'.$parent->meta_title.'</a></span><span class="navigation-pipe">'.$pipe.'</span><span class="navigation_cms">'.$cms->meta_title.'</span>';

    }

    public function getBytes($length) {

        $length = (int) $length;

        if ($length <= 0) {
            return false;
        }

        if (function_exists('openssl_random_pseudo_bytes')) {
            $bytes = openssl_random_pseudo_bytes($length, $cryptoStrong);

            if ($cryptoStrong === true) {
                return $bytes;
            }

        }

        if (function_exists('mcrypt_create_iv')) {
            $bytes = mcrypt_create_iv($length, MCRYPT_DEV_URANDOM);

            if ($bytes !== false && strlen($bytes) === $length) {
                return $bytes;
            }

        }

        // Else try to get $length bytes of entropy.
        // Thanks to Zend

        $result = '';
        $entropy = '';
        $msecPerRound = 400;
        $bitsPerRound = 2;
        $total = $length;
        $hashLength = 20;

        while (strlen($result) < $length) {
            $bytes = ($total > $hashLength) ? $hashLength : $total;
            $total -= $bytes;

            for ($i = 1; $i < 3; $i++) {
                $t1 = microtime(true);
                $seed = mt_rand();

                for ($j = 1; $j < 50; $j++) {
                    $seed = sha1($seed);
                }

                $t2 = microtime(true);
                $entropy .= $t1 . $t2;
            }

            $div = (int) (($t2 - $t1) * 1000000);

            if ($div <= 0) {
                $div = 400;
            }

            $rounds = (int) ($msecPerRound * 50 / $div);
            $iter = $bytes * (int) (ceil(8 / $bitsPerRound));

            for ($i = 0; $i < $iter; $i++) {
                $t1 = microtime();
                $seed = sha1(mt_rand());

                for ($j = 0; $j < $rounds; $j++) {
                    $seed = sha1($seed);
                }

                $t2 = microtime();
                $entropy .= $t1 . $t2;
            }

            $result .= sha1($entropy, true);
        }

        return substr($result, 0, $length);
    }

    public function redirect($url, $baseUri = __EPH_BASE_URI__, $link = null, $headers = null) {

        if (_EPH_DEBUG_PROFILING_ || _EPH_ADMIN_DEBUG_PROFILING_) {
            return Profiling::redirect($url, $baseUri, $link, $headers);
        }

        if (!$link) {
            $link = $this->context->_link;
        }

        if (strpos($url, 'http://') === false && strpos($url, 'https://') === false && $link) {

            if (strpos($url, $baseUri) === 0) {
                $url = substr($url, strlen($baseUri));
            }

            if (strpos($url, 'index.php?controller=') !== false && strpos($url, 'index.php/') == 0) {
                $url = substr($url, strlen('index.php?controller='));

                if ($this->context->phenyxConfig->get('EPH_REWRITING_SETTINGS')) {
                    $url = $this->strReplaceFirst('&', '?', $url);
                }

            }

            $explode = explode('?', $url);
            // don't use ssl if url is home page
            // used when logout for example
            $useSsl = !empty($url);
            $url = $link->getPageLink($explode[0], $useSsl);

            if (isset($explode[1])) {
                $url .= '?' . $explode[1];
            }

        }

        // Send additional headers

        if ($headers) {

            if (!is_array($headers)) {
                $headers = [$headers];
            }

            foreach ($headers as $header) {
                header($header);
            }

        }

        header('Location: ' . $url);
        exit;
    }

    public function strReplaceFirst($search, $replace, $subject, $cur = 0) {

        if (!is_null($subject)) {
            return (strpos($subject, $search, $cur)) ? substr_replace($subject, $replace, (int) strpos($subject, $search, $cur), strlen($search)) : $subject;
        }

        return $subject;
    }

    public function str_replace_first($search, $replace, $subject) {

        $search = '/' . preg_quote($search, '/') . '/';
        return preg_replace($search, $replace, $subject, 1);
    }

    public function display_gallery_page($files_array, $pageno = 1, $path = '', $resultspp = 4096, $display = true) {

        if ($path == 'undefined') {
            $path = '';
        }

        $composer = false;

        if (str_contains($path, 'composer')) {
            $composer = true;
        }

        $pagination = [];
        $pagination['resultspp'] = $resultspp;
        $pagination['startres'] = 1 + (($pageno - 1) * $pagination['resultspp']);
        $pagination['endres'] = $pagination['startres'] + $pagination['resultspp'] - 1;
        $pagination['counter'] = 1;
        $pagination['totalres'] = count($files_array);
        $pagination['totalpages'] = ceil($pagination['totalres'] / $pagination['resultspp']);

        $imagepath = '/content/img/';
        $array_count = 0;
        $output = '';

        foreach ($files_array as $file_name) {
            $file_path = $imagepath . $path . $file_name;

            if ($composer) {
                $dataComposer = ComposerMedia::getIdMediaByName($file_name);
            }

            if (($pagination['counter'] >= $pagination['startres']) && ($pagination['counter'] <= $pagination['endres'])) {
                $addcbr = '';

                if (($pagination['counter'] > ($pagination['endres'] - 5)) && ($pagination['counter'] > ($pagination['totalres'] - 5))) {
                    $addcbr = ' br';
                }

                $file = []; // ???
                $file['name'] = $file_name;

                $files_array[$array_count] = [$file];
                $array_count++;

                if (preg_match('/\.(jpg|jpe|jpeg|png|gif|bmp)$/', $file_name)) {

                    if ($composer) {
                        $output .= '<a href="' . $file_path . '" title="' . $file_name . '" data-gallery="gallery" data-image="' . $file_name . '" data-id="' . $dataComposer['id_vc_media'] . '" data-field_id="' . $dataComposer['id_vc_media'] . '" data-image-folder="' . $dataComposer['subdir'] . '">';
                    } else {
                        $output .= '<a href="' . $file_path . '" title="' . $file_name . '" data-gallery="gallery" data-id="">';
                    }

                    $output .= "<div class=\"thumb$addcbr\" style=\"background-image:url('$file_path')\"></div>";
                    $output .= '<label class="file-name">' . $file_name . '</label>';
                    $output .= '</a>';
                } else {
                    $output .= '<a href="' . $file_path . '" title="' . $file_name . '" data-folder="folder">';
                    $output .= "<div class=\"thumb folder$addcbr\"></div>";
                    $output .= '<label class="file-name">' . $file_name . '</label>';
                    $output .= '</a>';
                }

            }

            $pagination['counter']++;
        }

        if ($display == true) {
            echo $output;
        } else {
            return $output;
        }

    }

    public function display_gallery_pagination($url = '', $totalresults = 0, $pageno = 1, $resultspp = 4096, $display = true) {

        $configp = [];
        $configp['results_per_page'] = $resultspp;
        $configp['total_no_results'] = $totalresults;
        $configp['page_url'] = $url;
        $configp['current_page_segment'] = 4;
        $configp['url'] = $url;
        $configp['pageno'] = $pageno;

        $output = $this->get_html($configp);

        if ($display == true) {
            echo $output;
        } else {
            return $output;
        }

    }

    public function get_html($pconfig) {

        $links_html = '';

        // $pageAddress = $pconfig['url'];
        $resultspp = $pconfig['results_per_page'];
        $current_page = $pconfig['pageno'];
        $start_res = $current_page * $resultspp;
        // $endRes = $start_res + $resultspp;

        $tot_pages = $pconfig['total_no_results'] / $resultspp;

        $round_pages = ceil($tot_pages);

        $links_html .= '<ul>';

        if ($current_page > 1) {

            if ($tot_pages > 1) {
                $links_html .= '<li id="gliFirst"><a data-target-page="1" href="#">&lt; First</a></li>';
            }

            $links_html .= '<li id="gliPrev"><a data-target-page="prev" href="#">Prev</a></li>';
        } else {

            if ($tot_pages > 1) {
                $links_html .= '<li class="disabled" id="gliFirst"><a data-target-page="1" href="#">&lt; First</a></li>';
            }

            $links_html .= '<li class="disabled" id="gliPrev"><a data-target-page="prev" href="#">Prev</a></li>';
        }

        // $pageLimit = 9;

        if (($current_page - 3) > 0) {
            $start_page = $current_page - 3;
        } else {
            $start_page = 1;
            $end_add = 1 - ($current_page - 3);
        }

        $end_page = $round_pages;
        $start_add = 0;

        if (($start_page + $start_add) > 0) {
            $start_page = $start_page - $start_add;
        } else {
            $start_page = 1;
        }

        if ($start_page <= 0) {
            $start_page = 1;
        }



        for ($i = $start_page; $i <= $end_page; $i++) {

            if ($i == $current_page) {
                $links_html .= '<li class="disabled" id="gli$i"><a href="#" data-target-page="$i">$i</a></span></li>';
            } else {
                $links_html .= '<li id="gli$i"><a href="#" data-target-page="$i">$i</a></li>';
            }

        }

        if ($current_page < $round_pages) {
            // $nextPage = $current_page + 1;
            $links_html .= '<li id="gliNext"><a href="#" data-target-page="next">Next</a></li>';

            if ($tot_pages > 1) {
                $links_html .= '<li id="gliLast"><a href="#" data-target-page="$round_pages">Last &gt;</a></li>';
            }

        } else {
            $links_html .= '<li id="gliNext" class="disabled"><a href="#" data-target-page="next">Next</a></li>';

            if ($tot_pages > 1) {
                $links_html .= '<li id="gliLast" class="disabled"><a href="#" data-target-page="$round_pages">Last &gt;</a><li>';
            }

        }

        //if ($round_pages > 9) {}
        $links_html .= '</ul>';
        return $links_html;
    }

    public function redirectLink($url) {

        $url = str_replace(PHP_EOL, '', $url);

        if (_EPH_DEBUG_PROFILING_ || _EPH_ADMIN_DEBUG_PROFILING_) {
            return Profiling::redirectLink($url);
        }

        if (!preg_match('@^https?://@i', $url)) {

            if (strpos($url, __EPH_BASE_URI__) !== false && strpos($url, __EPH_BASE_URI__) == 0) {
                $url = substr($url, strlen(__EPH_BASE_URI__));
            }

            if (strpos($url, 'index.php?controller=') !== false && strpos($url, 'index.php/') == 0) {
                $url = substr($url, strlen('index.php?controller='));
            }

            $explode = explode('?', $url);
            $url = $this->context->_link->getPageLink($explode[0]);

            if (isset($explode[1])) {
                $url .= '?' . $explode[1];
            }

        }

        header('Location: ' . $url);
        exit;
    }

    public function redirectAdmin($url) {

        if (_EPH_DEBUG_PROFILING_ || _EPH_ADMIN_DEBUG_PROFILING_) {
            return Profiling::redirectAdmin($url);
        }

        header('Location: ' . $url);
        exit;
    }

    public function getProtocol() {

        $protocol = ($this->context->phenyxConfig->get('EPH_SSL_ENABLED') || (!empty($_SERVER['HTTPS'])
            && mb_strtolower($_SERVER['HTTPS']) != 'off')) ? 'https://' : 'http://';

        return $protocol;
    }

    public function strtolower($str) {

        if (is_array($str)) {
            return false;
        }

        return mb_strtolower($str, 'utf-8');
    }

    public function isArray($str) {

        if (is_array($str)) {
            return true;
        }

        return false;
    }

    public function getRemoteAddr() {

        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
        } else {
            $headers = $_SERVER;
        }

        if (array_key_exists('X-Forwarded-For', $headers)) {
            $_SERVER['HTTP_X_FORWARDED_FOR'] = $headers['X-Forwarded-For'];
        }

        if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] && (!isset($_SERVER['REMOTE_ADDR'])
            || preg_match('/^127\..*/i', trim($_SERVER['REMOTE_ADDR'])) || preg_match('/^172\.16.*/i', trim($_SERVER['REMOTE_ADDR']))
            || preg_match('/^192\.168\.*/i', trim($_SERVER['REMOTE_ADDR'])) || preg_match('/^10\..*/i', trim($_SERVER['REMOTE_ADDR'])))
        ) {

            if (strpos($_SERVER['HTTP_X_FORWARDED_FOR'], ',')) {
                $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);

                return $ips[0];
            } else {
                return $_SERVER['HTTP_X_FORWARDED_FOR'];
            }

        } else {
            return $_SERVER['REMOTE_ADDR'];
        }

    }

    public function getCurrentUrlProtocolPrefix() {

        if ($this->usingSecureMode()) {
            return 'https://';
        } else {
            return 'http://';
        }

    }

    public function usingSecureMode() {

        if (isset($_SERVER['HTTPS'])) {
            return in_array(mb_strtolower($_SERVER['HTTPS']), [1, 'on']);
        }

        // $_SERVER['SSL'] exists only in some specific configuration

        if (isset($_SERVER['SSL'])) {
            return in_array(mb_strtolower($_SERVER['SSL']), [1, 'on']);
        }

        // $_SERVER['REDIRECT_HTTPS'] exists only in some specific configuration

        if (isset($_SERVER['REDIRECT_HTTPS'])) {
            return in_array(mb_strtolower($_SERVER['REDIRECT_HTTPS']), [1, 'on']);
        }

        if (isset($_SERVER['HTTP_SSL'])) {
            return in_array(mb_strtolower($_SERVER['HTTP_SSL']), [1, 'on']);
        }

        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            return mb_strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) == 'https';
        }

        return false;
    }

    public function secureReferrer($referrer) {

        if (preg_match('/^http[s]?:\/\/' . $this->getServerName() . '(:' . _EPH_SSL_PORT_ . ')?\/.*$/Ui', $referrer)) {
            return $referrer;
        }

        return __EPH_BASE_URI__;
    }

    public function getServerName() {

        if (isset($_SERVER['HTTP_X_FORWARDED_SERVER']) && $_SERVER['HTTP_X_FORWARDED_SERVER']) {
            return $_SERVER['HTTP_X_FORWARDED_SERVER'];
        }

        return $_SERVER['SERVER_NAME'];
    }

    public function getAllValues() {

        return $_POST + $_GET;
    }

    public function getIsset($key) {

        if (!isset($key) || empty($key) || !is_string($key)) {
            return false;
        }

        return isset($_POST[$key]) ? true : (isset($_GET[$key]) ? true : false);
    }

    public function setCookieLanguage($cookie = null) {

        if (!$cookie) {
            $cookie = $this->context->cookie;
        }

        /* If language does not exist or is disabled, erase it */

        if ($cookie->id_lang) {
            $lang = new Language((int) $cookie->id_lang);

            if (!Validate::isLoadedObject($lang) || !$lang->active) {
                $cookie->id_lang = null;
            }

        }

        if (!$this->context->phenyxConfig->get('EPH_DETECT_LANG')) {
            unset($cookie->detect_language);
        }

        /* Automatically detect language if not already defined, detect_language is set in Cookie::update */

        if (!$this->getValue('isolang') && !$this->getValue('id_lang') && (!$cookie->id_lang || isset($cookie->detect_language))
            && isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])
        ) {
            $array = explode(',', mb_strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE']));
            $string = $array[0];

            if (Validate::isLanguageCode($string)) {
                $lang = Language::getLanguageByIETFCode($string);

                if (Validate::isLoadedObject($lang) && $lang->active) {
                    $this->context->language = $lang;
                    $cookie->id_lang = (int) $lang->id;
                }

            }

        }

        if (isset($cookie->detect_language)) {
            unset($cookie->detect_language);
        }

        /* If language file not present, you must use default language file */

        if (!$cookie->id_lang || !Validate::isUnsignedId($cookie->id_lang)) {
            $cookie->id_lang = (int) $this->context->language->id;
        }

        $iso = Language::getIsoById((int) $cookie->id_lang);
        @include_once _EPH_THEME_DIR_ . 'lang/' . $iso . '.php';

        return $iso;
    }

    public function getValue($key, $defaultValue = false) {

        if (!isset($key) || empty($key) || !is_string($key)) {
            return false;
        }

        $ret = (isset($_POST[$key]) ? $_POST[$key] : (isset($_GET[$key]) ? $_GET[$key] : $defaultValue));

        if (is_string($ret)) {
            return stripslashes(urldecode(preg_replace('/((\%5C0+)|(\%00+))/i', '', urlencode($ret))));
        }

        return $ret;
    }

    public function switchLanguage($context = null) {

        if (!isset($this->context->cookie)) {
            return;
        }

        if (($iso = $this->getValue('isolang')) && Validate::isLanguageIsoCode($iso) && ($idLang = (int) Language::getIdByIso($iso))) {
            $_GET['id_lang'] = $idLang;
        }

        $cookieIdLang = $this->context->cookie->id_lang;
        $configurationIdLang = $this->context->language->id;

        if ((($idLang = (int) $this->getValue('id_lang')) && Validate::isUnsignedId($idLang) && $cookieIdLang != (int) $idLang)
            || (($idLang == $configurationIdLang) && Validate::isUnsignedId($idLang) && $idLang != $cookieIdLang)
        ) {
            $this->context->cookie->id_lang = $idLang;
            $language = new Language($idLang);

            if (Validate::isLoadedObject($language) && $language->active) {
                $this->context->language = $language;
            }

            $params = $_GET;

            if ($this->context->phenyxConfig->get('EPH_REWRITING_SETTINGS') || !Language::isMultiLanguageActivated()) {
                unset($params['id_lang']);
            }

        }

    }

    public function getCountry($address = null) {

        $idCountry = (int) $this->getValue('id_country');

        if ($idCountry && Validate::isInt($idCountry)) {
            return (int) $idCountry;
        } else

        if (!$idCountry && isset($address) && isset($address->id_country) && $address->id_country) {

            $idCountry = (int) $address->id_country;
        } else

        if ($this->context->phenyxConfig->get('EPH_DETECT_COUNTRY') && isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            preg_match('#(?<=-)\w\w|\w\w(?!-)#', $_SERVER['HTTP_ACCEPT_LANGUAGE'], $array);

            if (is_array($array) && isset($array[0]) && Validate::isLanguageIsoCode($array[0])) {
                $idCountry = (int) Country::getByIso($array[0], true);
            }

        }

        if (!isset($idCountry) || !$idCountry) {
            $idCountry = (int) $this->context->phenyxConfig->get('EPH_COUNTRY_DEFAULT');
        }

        return (int) $idCountry;
    }

    public function isSubmit($submit) {

        return (
            isset($_POST[$submit]) || isset($_POST[$submit . '_x']) || isset($_POST[$submit . '_y'])
            || isset($_GET[$submit]) || isset($_GET[$submit . '_x']) || isset($_GET[$submit . '_y'])
        );
    }

    public function array_replace() {

        if (!function_exists('array_replace')) {
            $args = func_get_args();
            $numArgs = func_num_args();
            $res = [];

            for ($i = 0; $i < $numArgs; $i++) {

                if (is_array($args[$i])) {

                    foreach ($args[$i] as $key => $val) {
                        $res[$key] = $val;
                    }

                } else {
                    trigger_error(__FUNCTION__ . '(): Argument #' . ($i + 1) . ' is not an array', E_USER_WARNING);

                    return null;
                }

            }

            return $res;
        } else {
            return call_user_func_array('array_replace', func_get_args());
        }

    }

    public function dateFormat($params, $smarty) {

        return $this->displayDate($params['date'], null, (isset($params['full']) ? $params['full'] : false));
    }

    public function displayDate($date, $idLang = null, $full = false, $separator = null) {

        if ($idLang !== null) {
            $this->displayParameterAsDeprecated('id_lang');
        }

        if ($separator !== null) {
            $this->displayParameterAsDeprecated('separator');
        }

        if (!$date || !($time = strtotime($date))) {
            return $date;
        }

        if ($date == '0000-00-00 00:00:00' || $date == '0000-00-00') {
            return '';
        }

        if (!Validate::isDate($date) || !Validate::isBool($full)) {
            throw new PhenyxException('Invalid date');
        }

        $dateFormat = ($full ? $this->context->language->date_format_full : $this->context->language->date_format_lite);

        return date($dateFormat, $time);
    }

    public function displayParameterAsDeprecated($parameter) {

        $backtrace = debug_backtrace();
        $callee = next($backtrace);
        $error = 'Parameter <b>' . $parameter . '</b> in function <b>' . (isset($callee['function']) ? $callee['function'] : '') . '()</b> is deprecated in <b>' . $callee['file'] . '</b> on line <b>' . (isset($callee['line']) ? $callee['line'] : '(undefined)') . '</b><br />';
        $message = 'The parameter ' . $parameter . ' in function ' . $callee['function'] . ' (Line ' . (isset($callee['line']) ? $callee['line'] : 'undefined') . ') is deprecated and will be removed in the next major version.';
        $class = isset($callee['class']) ? $callee['class'] : null;

        $this->throwDeprecated($error, $message, $class);
    }

    protected static function throwDeprecated($error, $message, $class) {

        if (_EPH_DISPLAY_COMPATIBILITY_WARNING_) {
            trigger_error($error, E_USER_WARNING);
            Logger::addLog($message, 3, $class);
        }

    }

    public function htmlentitiesDecodeUTF8($string) {

        if (is_array($string)) {
            $string = array_map(['Tools', 'htmlentitiesDecodeUTF8'], $string);

            return (string) array_shift($string);
        }

        return html_entity_decode((string) $string, ENT_QUOTES, 'utf-8');
    }

    public function htmlEntities($string, $flags = ENT_QUOTES, $encoding = null, $double_encode = true) {

        return htmlentities($string, $flags, $encoding, $double_encode);
    }

    public function safePostVars() {

        if (!isset($_POST) || !is_array($_POST)) {
            $_POST = [];
        } else {
            $_POST = array_map(['Tools', 'htmlentitiesUTF8'], $_POST);
        }

    }

    public function deleteDirectory($dirname, $deleteSelf = true) {

        $dirname = rtrim($dirname, '/') . '/';

        if (file_exists($dirname)) {

            if ($files = scandir($dirname)) {

                foreach ($files as $file) {

                    if ($file != '.' && $file != '..' && $file != '.svn') {

                        if (is_dir($dirname . $file)) {
                            $this->deleteDirectory($dirname . $file, true);
                        } else

                        if (file_exists($dirname . $file)) {
                            @chmod($dirname . $file, 0777); // NT ?
                            unlink($dirname . $file);
                        }

                    }

                }

                if ($deleteSelf && file_exists($dirname)) {

                    if (!rmdir($dirname)) {
                        @chmod($dirname, 0777); // NT ?

                        return false;
                    }

                }

                return true;
            }

        }

        return false;
    }

    public function deleteFile($file, $excludeFiles = []) {

        if (isset($excludeFiles) && !is_array($excludeFiles)) {
            $excludeFiles = [$excludeFiles];
        }

        if (file_exists($file) && is_file($file) && array_search(basename($file), $excludeFiles) === false) {
            @chmod($file, 0777); // NT ?
            unlink($file);
        }

    }

    public function fd($object, $type = 'log') {

        $types = ['log', 'debug', 'info', 'warn', 'error', 'assert'];

        if (!in_array($type, $types)) {
            $type = 'log';
        }

        echo '
            <script type="text/javascript">
                console.' . $type . '(' . json_encode($object) . ');
            </script>
        ';
    }

    public function d($object, $kill = true) {

        return ($this->dieObject($object, $kill));
    }

    public function dieObject($object, $kill = true) {

        echo '<xmp style="text-align: left;">';
        print_r($object);
        echo '</xmp><br />';

        if ($kill) {
            die('END');
        }

        return $object;
    }

    public function debug_backtrace($start = 0, $limit = null) {

        $backtrace = debug_backtrace();
        array_shift($backtrace);

        for ($i = 0; $i < $start; ++$i) {
            array_shift($backtrace);
        }

        echo '
        <div style="margin:10px;padding:10px;border:1px solid #666666">
            <ul>';
        $i = 0;

        foreach ($backtrace as $id => $trace) {

            if ((int) $limit && (++$i > $limit)) {
                break;
            }

            $relativeFile = (isset($trace['file'])) ? 'in /' . ltrim(str_replace([_EPH_ROOT_DIR_, '\\'], ['', '/'], $trace['file']), '/') : '';
            $currentLine = (isset($trace['line'])) ? ':' . $trace['line'] : '';

            echo '<li>
                <b>' . ((isset($trace['class'])) ? $trace['class'] : '') . ((isset($trace['type'])) ? $trace['type'] : '') . $trace['function'] . '</b>
                ' . $relativeFile . $currentLine . '
            </li>';
        }

        echo '</ul>
        </div>';
    }

    public function p($object) {

        return ($this->dieObject($object, false));
    }

    public function error_log($object) {

        return error_log(print_r($object, true));
    }

    public function displayAsDeprecated($message = null) {

        $backtrace = debug_backtrace();
        $callee = next($backtrace);
        $class = isset($callee['class']) ? $callee['class'] : null;

        if ($message === null) {
            $message = 'The function ' . $callee['function'] . ' (Line ' . $callee['line'] . ') is deprecated and will be removed in the next major version.';
        }

        $error = 'Function <b>' . $callee['function'] . '()</b> is deprecated in <b>' . $callee['file'] . '</b> on line <b>' . $callee['line'] . '</b><br />';

        $this->throwDeprecated($error, $message, $class);
    }

    public function hash($password) {

        return password_hash($password, PASSWORD_BCRYPT);
    }

    public function encryptIV($data) {

        return md5(_COOKIE_IV_ . $data);
    }

    public function getToken($page = true, $context = null) {

        if ($page === true) {
            return ($this->encrypt($this->context->user->id . $this->context->user->passwd . $_SERVER['SCRIPT_NAME']));
        } else {
            return ($this->encrypt($this->context->user->id . $this->context->user->passwd . $page));
        }

    }

    public function encrypt($passwd) {

        return md5(_COOKIE_KEY_ . $passwd);
    }

    public function getAdminTokenLite($tab, $context = null) {

        return $this->getAdminToken($tab . (int) BackTab::getIdFromClassName($tab) . (int) $this->context->employee->id);
    }

    public function getAdminToken($string) {

        return !empty($string) ? $this->encrypt($string) : false;
    }

    public function getAdminTokenLiteSmarty($params, $smarty) {

        return $this->getAdminToken($params['tab'] . (int) BackTab::getIdFromClassName($params['tab']) . (int) $this->context->employee->id);
    }

    public function getAdminImageUrl($image = null, $entities = false) {

        return $this->getAdminUrl(basename(_EPH_IMG_DIR_) . '/' . $image, $entities);
    }

    public function getAdminUrl($url = null, $entities = false) {

        $link = $this->getHttpHost(true) . __EPH_BASE_URI__;

        if (isset($url)) {
            $link .= ($entities ? $this->htmlentitiesUTF8($url) : $url);
        }

        return $link;
    }

    public function getHttpHost($http = false, $entities = false, $ignore_port = false) {

        $host = (isset($_SERVER['HTTP_X_FORWARDED_HOST']) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : $_SERVER['HTTP_HOST']);

        if ($ignore_port && $pos = strpos($host, ':')) {
            $host = substr($host, 0, $pos);
        }

        if ($entities) {
            $host = htmlspecialchars($host, ENT_COMPAT, 'UTF-8');
        }

        if ($http) {
            $host = ($this->context->phenyxConfig->get('EPH_SSL_ENABLED') ? 'https://' : 'http://') . $host;
        }

        return $host;
    }

    public function htmlentitiesUTF8($string, $type = ENT_QUOTES) {

        if (is_array($string)) {
            return array_map(['Tools', 'htmlentitiesUTF8'], $string);
        }

        return htmlentities((string) $string, $type, 'utf-8');
    }

    public function safeOutput($string, $html = false) {

        if (!$html && !is_null($string)) {
            $string = strip_tags($string);
        }

        return @$this->htmlentitiesUTF8($string, ENT_QUOTES);
    }

    public function displayError($string = 'Fatal error', $htmlentities = true, $context = null) {

        global $_ERRORS;

        @include_once _EPH_TRANSLATIONS_DIR_ . $this->context->language->iso_code . '/errors.php';

        if (defined('_EPH_MODE_DEV_') && _EPH_MODE_DEV_ && $string == 'Fatal error') {
            return ('<pre>' . print_r(debug_backtrace(), true) . '</pre>');
        }

        if (!is_array($_ERRORS)) {
            return $htmlentities ? $this->htmlentitiesUTF8($string) : $string;
        }

        $key = md5(str_replace('\'', '\\\'', $string));
        $str = (isset($_ERRORS) && is_array($_ERRORS) && array_key_exists($key, $_ERRORS)) ? $_ERRORS[$key] : $string;

        return $htmlentities ? $this->htmlentitiesUTF8(stripslashes($str)) : $str;
    }

    public function link_rewrite($str, $utf8Decode = null) {

        if ($utf8Decode !== null) {
            $this->displayParameterAsDeprecated('utf8_decode');
        }

        return $this->str2url($str);
    }

    public function str2url($str) {

        static $arrayStr = [];
        static $allowAccentedChars = null;
        static $hasMbStrtolower = null;

        if ($hasMbStrtolower === null) {
            $hasMbStrtolower = function_exists('mb_strtolower');
        }

        if (isset($arrayStr[$str])) {
            return $arrayStr[$str];
        }

        if (!is_string($str)) {
            return false;
        }

        if ($str == '') {
            return '';
        }

        if ($allowAccentedChars === null) {
            $allowAccentedChars = $this->context->phenyxConfig->get('EPH_ALLOW_ACCENTED_CHARS_URL');
        }

        $returnStr = trim($str);

        if ($hasMbStrtolower) {
            $returnStr = mb_strtolower($returnStr, 'utf-8');
        }

        if (!$allowAccentedChars) {
            $returnStr = $this->replaceAccentedChars($returnStr);
        }

        // Remove all non-whitelist chars.

        if ($allowAccentedChars) {
            $returnStr = preg_replace('/[^a-zA-Z0-9\s\'\:\/\[\]\-\p{L}]/u', '', $returnStr);
        } else {
            $returnStr = preg_replace('/[^a-zA-Z0-9\s\'\:\/\[\]\-]/', '', $returnStr);
        }

        $returnStr = preg_replace('/[\s\'\:\/\[\]\-]+/', ' ', $returnStr);
        $returnStr = str_replace([' ', '/'], '-', $returnStr);

        // If it was not possible to lowercase the string with mb_strtolower, we do it after the transformations.
        // This way we lose fewer special chars.

        if (!$hasMbStrtolower) {
            $returnStr = mb_strtolower($returnStr);
        }

        $arrayStr[$str] = $returnStr;

        return $returnStr;
    }

    public function replaceAccentedChars($str) {

        /* One source among others:
                                                                                                http://www.tachyonsoft.com/uc0000.htm
                                                                                                http://www.tachyonsoft.com/uc0001.htm
                                                                                                http://www.tachyonsoft.com/uc0004.htm
        */
        $patterns = [

            /* Lowercase */
            /* a  */
            '/[\x{00E0}\x{00E1}\x{00E2}\x{00E3}\x{00E4}\x{00E5}\x{0101}\x{0103}\x{0105}\x{0430}\x{00C0}-\x{00C3}\x{1EA0}-\x{1EB7}]/u',
            /* b  */
            '/[\x{0431}]/u',
            /* c  */
            '/[\x{00E7}\x{0107}\x{0109}\x{010D}\x{0446}]/u',
            /* d  */
            '/[\x{010F}\x{0111}\x{0434}\x{0110}\x{00F0}]/u',
            /* e  */
            '/[\x{00E8}\x{00E9}\x{00EA}\x{00EB}\x{0113}\x{0115}\x{0117}\x{0119}\x{011B}\x{0435}\x{044D}\x{00C8}-\x{00CA}\x{1EB8}-\x{1EC7}]/u',
            /* f  */
            '/[\x{0444}]/u',
            /* g  */
            '/[\x{011F}\x{0121}\x{0123}\x{0433}\x{0491}]/u',
            /* h  */
            '/[\x{0125}\x{0127}]/u',
            /* i  */
            '/[\x{00EC}\x{00ED}\x{00EE}\x{00EF}\x{0129}\x{012B}\x{012D}\x{012F}\x{0131}\x{0438}\x{0456}\x{00CC}\x{00CD}\x{1EC8}-\x{1ECB}\x{0128}]/u',
            /* j  */
            '/[\x{0135}\x{0439}]/u',
            /* k  */
            '/[\x{0137}\x{0138}\x{043A}]/u',
            /* l  */
            '/[\x{013A}\x{013C}\x{013E}\x{0140}\x{0142}\x{043B}]/u',
            /* m  */
            '/[\x{043C}]/u',
            /* n  */
            '/[\x{00F1}\x{0144}\x{0146}\x{0148}\x{0149}\x{014B}\x{043D}]/u',
            /* o  */
            '/[\x{00F2}\x{00F3}\x{00F4}\x{00F5}\x{00F6}\x{00F8}\x{014D}\x{014F}\x{0151}\x{043E}\x{00D2}-\x{00D5}\x{01A0}\x{01A1}\x{1ECC}-\x{1EE3}]/u',
            /* p  */
            '/[\x{043F}]/u',
            /* r  */
            '/[\x{0155}\x{0157}\x{0159}\x{0440}]/u',
            /* s  */
            '/[\x{015B}\x{015D}\x{015F}\x{0161}\x{0441}]/u',
            /* ss */
            '/[\x{00DF}]/u',
            /* t  */
            '/[\x{0163}\x{0165}\x{0167}\x{0442}]/u',
            /* u  */
            '/[\x{00F9}\x{00FA}\x{00FB}\x{00FC}\x{0169}\x{016B}\x{016D}\x{016F}\x{0171}\x{0173}\x{0443}\x{00D9}-\x{00DA}\x{0168}\x{01AF}\x{01B0}\x{1EE4}-\x{1EF1}]/u',
            /* v  */
            '/[\x{0432}]/u',
            /* w  */
            '/[\x{0175}]/u',
            /* y  */
            '/[\x{00FF}\x{0177}\x{00FD}\x{044B}\x{1EF2}-\x{1EF9}\x{00DD}]/u',
            /* z  */
            '/[\x{017A}\x{017C}\x{017E}\x{0437}]/u',
            /* ae */
            '/[\x{00E6}]/u',
            /* ch */
            '/[\x{0447}]/u',
            /* kh */
            '/[\x{0445}]/u',
            /* oe */
            '/[\x{0153}]/u',
            /* sh */
            '/[\x{0448}]/u',
            /* shh*/
            '/[\x{0449}]/u',
            /* ya */
            '/[\x{044F}]/u',
            /* ye */
            '/[\x{0454}]/u',
            /* yi */
            '/[\x{0457}]/u',
            /* yo */
            '/[\x{0451}]/u',
            /* yu */
            '/[\x{044E}]/u',
            /* zh */
            '/[\x{0436}]/u',

            /* Uppercase */
            /* A  */
            '/[\x{0100}\x{0102}\x{0104}\x{00C0}\x{00C1}\x{00C2}\x{00C3}\x{00C4}\x{00C5}\x{0410}]/u',
            /* B  */
            '/[\x{0411}]/u',
            /* C  */
            '/[\x{00C7}\x{0106}\x{0108}\x{010A}\x{010C}\x{0426}]/u',
            /* D  */
            '/[\x{010E}\x{0110}\x{0414}\x{00D0}]/u',
            /* E  */
            '/[\x{00C8}\x{00C9}\x{00CA}\x{00CB}\x{0112}\x{0114}\x{0116}\x{0118}\x{011A}\x{0415}\x{042D}]/u',
            /* F  */
            '/[\x{0424}]/u',
            /* G  */
            '/[\x{011C}\x{011E}\x{0120}\x{0122}\x{0413}\x{0490}]/u',
            /* H  */
            '/[\x{0124}\x{0126}]/u',
            /* I  */
            '/[\x{0128}\x{012A}\x{012C}\x{012E}\x{0130}\x{0418}\x{0406}]/u',
            /* J  */
            '/[\x{0134}\x{0419}]/u',
            /* K  */
            '/[\x{0136}\x{041A}]/u',
            /* L  */
            '/[\x{0139}\x{013B}\x{013D}\x{0139}\x{0141}\x{041B}]/u',
            /* M  */
            '/[\x{041C}]/u',
            /* N  */
            '/[\x{00D1}\x{0143}\x{0145}\x{0147}\x{014A}\x{041D}]/u',
            /* O  */
            '/[\x{00D3}\x{014C}\x{014E}\x{0150}\x{041E}]/u',
            /* P  */
            '/[\x{041F}]/u',
            /* R  */
            '/[\x{0154}\x{0156}\x{0158}\x{0420}]/u',
            /* S  */
            '/[\x{015A}\x{015C}\x{015E}\x{0160}\x{0421}]/u',
            /* T  */
            '/[\x{0162}\x{0164}\x{0166}\x{0422}]/u',
            /* U  */
            '/[\x{00D9}\x{00DA}\x{00DB}\x{00DC}\x{0168}\x{016A}\x{016C}\x{016E}\x{0170}\x{0172}\x{0423}]/u',
            /* V  */
            '/[\x{0412}]/u',
            /* W  */
            '/[\x{0174}]/u',
            /* Y  */
            '/[\x{0176}\x{042B}]/u',
            /* Z  */
            '/[\x{0179}\x{017B}\x{017D}\x{0417}]/u',
            /* AE */
            '/[\x{00C6}]/u',
            /* CH */
            '/[\x{0427}]/u',
            /* KH */
            '/[\x{0425}]/u',
            /* OE */

            '/[\x{0152}]/u',
            /* SH */
            '/[\x{0428}]/u',
            /* SHH*/
            '/[\x{0429}]/u',
            /* YA */
            '/[\x{042F}]/u',
            /* YE */
            '/[\x{0404}]/u',
            /* YI */
            '/[\x{0407}]/u',
            /* YO */
            '/[\x{0401}]/u',
            /* YU */
            '/[\x{042E}]/u',
            /* ZH */
            '/[\x{0416}]/u',
        ];

        // ö to oe
        // å to aa
        // ä to ae

        $replacements = [
            'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 'ss', 't', 'u', 'v', 'w', 'y', 'z', 'ae', 'ch', 'kh', 'oe', 'sh', 'shh', 'ya', 'ye', 'yi', 'yo', 'yu', 'zh',
            'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'R', 'S', 'T', 'U', 'V', 'W', 'Y', 'Z', 'AE', 'CH', 'KH', 'OE', 'SH', 'SHH', 'YA', 'YE', 'YI', 'YO', 'YU', 'ZH',
        ];

        return preg_replace($patterns, $replacements, $str);
    }

    public function truncate($str, $maxLength, $suffix = '...') {

        if (mb_strlen($str) <= $maxLength) {
            return $str;
        }

        $str = mb_convert_encoding($str, 'ISO-8859-1', 'UTF-8');
        return mb_convert_encoding((substr($str, 0, $maxLength - mb_strlen($suffix)) . $suffix), 'UTF-8', 'ISO-8859-1');
    }

    public function strlen($str, $encoding = 'UTF-8') {

        if (is_array($str)) {
            return false;
        }

        return mb_strlen($str, $encoding);
    }

    public function truncateString($text, $length = 120, $options = []) {

        $default = [
            'ellipsis' => '...', 'exact' => true, 'html' => true,
        ];

        $options = array_merge($default, $options);
        extract($options);
        /**
         * @var string $ellipsis
         * @var bool   $exact
         * @var bool   $html
         */

        if ($html) {

            if (mb_strlen(preg_replace('/<.*?>/', '', $text)) <= $length) {
                return $text;
            }

            $totalLength = mb_strlen(strip_tags($ellipsis));
            $openTags = [];
            $truncate = '';
            preg_match_all('/(<\/?([\w+]+)[^>]*>)?([^<>]*)/', $text, $tags, PREG_SET_ORDER);

            foreach ($tags as $tag) {

                if (!preg_match('/img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param/s', $tag[2])) {

                    if (preg_match('/<[\w]+[^>]*>/s', $tag[0])) {
                        array_unshift($openTags, $tag[2]);
                    } else

                    if (preg_match('/<\/([\w]+)[^>]*>/s', $tag[0], $closeTag)) {
                        $pos = array_search($closeTag[1], $openTags);

                        if ($pos !== false) {
                            array_splice($openTags, $pos, 1);
                        }

                    }

                }

                $truncate .= $tag[1];
                $contentLength = mb_strlen(preg_replace('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', ' ', $tag[3]));

                if ($contentLength + $totalLength > $length) {
                    $left = $length - $totalLength;
                    $entitiesLength = 0;

                    if (preg_match_all('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', $tag[3], $entities, PREG_OFFSET_CAPTURE)) {

                        foreach ($entities[0] as $entity) {

                            if ($entity[1] + 1 - $entitiesLength <= $left) {
                                $left--;
                                $entitiesLength += mb_strlen($entity[0]);
                            } else {
                                break;
                            }

                        }

                    }

                    $truncate .= mb_substr($tag[3], 0, $left + $entitiesLength);
                    break;
                } else {
                    $truncate .= $tag[3];
                    $totalLength += $contentLength;
                }

                if ($totalLength >= $length) {
                    break;
                }

            }

        } else {

            if (mb_strlen($text) <= $length) {
                return $text;
            }

            $truncate = mb_substr($text, 0, $length - mb_strlen($ellipsis));
        }

        if (!$exact) {
            $spacepos = mb_strrpos($truncate, ' ');

            if ($html) {
                $truncateCheck = mb_substr($truncate, 0, $spacepos);
                $lastOpenTag = mb_strrpos($truncateCheck, '<');
                $lastCloseTag = mb_strrpos($truncateCheck, '>');

                if ($lastOpenTag > $lastCloseTag) {
                    preg_match_all('/<[\w]+[^>]*>/s', $truncate, $lastTagMatches);
                    $lastTag = array_pop($lastTagMatches[0]);
                    $spacepos = mb_strrpos($truncate, $lastTag) + mb_strlen($lastTag);
                }

                $bits = mb_substr($truncate, $spacepos);
                preg_match_all('/<\/([a-z]+)>/', $bits, $droppedTags, PREG_SET_ORDER);

                if (!empty($droppedTags)) {

                    if (!empty($openTags)) {

                        foreach ($droppedTags as $closing_tag) {

                            if (!in_array($closing_tag[1], $openTags)) {
                                array_unshift($openTags, $closing_tag[1]);
                            }

                        }

                    } else {

                        foreach ($droppedTags as $closing_tag) {
                            $openTags[] = $closing_tag[1];
                        }

                    }

                }

            }

            $truncate = mb_substr($truncate, 0, $spacepos);
        }

        $truncate .= $ellipsis;

        if ($html) {

            foreach ($openTags as $tag) {
                $truncate .= '</' . $tag . '>';
            }

        }

        return $truncate;
    }

    public function substr($str, $start, $length = false, $encoding = 'utf-8') {

        if (is_array($str)) {
            return false;
        }

        return mb_substr($str, (int) $start, ($length === false ? mb_strlen($str) : (int) $length), $encoding);
    }

    public function strrpos($str, $find, $offset = 0, $encoding = 'utf-8') {

        return mb_strrpos($str, $find, $offset, $encoding);
    }

    public function normalizeDirectory($directory) {

        return rtrim($directory, '/\\') . DIRECTORY_SEPARATOR;
    }

    public function dateYears() {

        $tab = [];

        for ($i = date('Y'); $i >= 1900; $i--) {
            $tab[] = $i;
        }

        return $tab;
    }

    public function dateDays() {

        $tab = [];

        for ($i = 1; $i != 32; $i++) {
            $tab[] = $i;
        }

        return $tab;
    }

    public function dateMonths() {

        $tab = [];

        for ($i = 1; $i != 13; $i++) {
            $tab[$i] = date('F', mktime(0, 0, 0, $i, date('m'), date('Y')));
        }

        return $tab;
    }

    public function dateFrom($date) {

        $tab = explode(' ', $date);

        if (!isset($tab[1])) {
            $date .= ' ' . $this->hourGenerate(0, 0, 0);
        }

        return $date;
    }

    public function hourGenerate($hours, $minutes, $seconds) {

        return implode(':', [$hours, $minutes, $seconds]);
    }

    public function dateTo($date) {

        $tab = explode(' ', $date);

        if (!isset($tab[1])) {
            $date .= ' ' . $this->hourGenerate(23, 59, 59);
        }

        return $date;
    }

    public function stripslashes($string) {

        if (_EPH_MAGIC_QUOTES_GPC_) {
            $string = stripslashes($string);
        }

        return $string;
    }

    public function strpos($str, $find, $offset = 0, $encoding = 'UTF-8') {

        return mb_strpos($str, $find, $offset, $encoding);
    }

    public function ucwords($str) {

        if (function_exists('mb_convert_case')) {
            return mb_convert_case($str, MB_CASE_TITLE);
        }

        return ucwords(mb_strtolower($str));
    }

    public function iconv($from, $to, $string) {

        if (function_exists('iconv')) {
            return iconv($from, $to . '//TRANSLIT', str_replace('¥', '&yen;', str_replace('£', '&pound;', str_replace('€', '&euro;', $string))));
        }

        return html_entity_decode(htmlentities($string, ENT_NOQUOTES, $from), ENT_NOQUOTES, $to);
    }

    public function isEmpty($field) {

        return ($field === '' || $field === null);
    }

    public function file_exists_no_cache($filename) {

        clearstatcache(true, $filename);

        return file_exists($filename);
    }

    public function file_get_contents($url, $useIncludePath = false, $streamContext = null, $curlTimeout = 5) {

        if ($streamContext == null && preg_match('/^https?:\/\//', $url)) {
            $streamContext = @stream_context_create(['http' => ['timeout' => $curlTimeout]]);
        }

        if (is_resource($streamContext)) {
            $opts = stream_context_get_options($streamContext);
        }

        // Remove the Content-Length header -- let cURL/fopen handle it

        if (!empty($opts['http']['header'])) {
            $headers = explode("\r\n", $opts['http']['header']);

            foreach ($headers as $index => $header) {

                if (substr(strtolower($header), 0, 14) === 'content-length') {
                    unset($headers[$index]);
                }

            }

            $opts['http']['header'] = implode("\r\n", $headers);
            stream_context_set_option($streamContext, ['http' => $opts['http']]);
        }

        if (!preg_match('/^https?:\/\//', $url)) {
            return @file_get_contents($url, $useIncludePath, $streamContext);
        } else

        if (function_exists('curl_init')) {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($curl, CURLOPT_TIMEOUT, $curlTimeout);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);

            if (!empty($opts['http']['header'])) {
                curl_setopt($curl, CURLOPT_HTTPHEADER, explode("\r\n", $opts['http']['header']));
            }

            if ($streamContext != null) {

                if (isset($opts['http']['method']) && mb_strtolower($opts['http']['method']) == 'post') {
                    curl_setopt($curl, CURLOPT_POST, true);

                    if (isset($opts['http']['content'])) {
                        curl_setopt($curl, CURLOPT_POSTFIELDS, $opts['http']['content']);
                    }

                }

            }

            $content = curl_exec($curl);
            curl_close($curl);

            return $content;
        } else

        if (ini_get('allow_url_fopen')) {
            return @file_get_contents($url, $useIncludePath, $streamContext);
        } else {
            return false;
        }

    }

    public function simplexml_load_file($url, $class_name = null) {

        $cache_id = '$this->simplexml_load_file' . $url;

        if (!CacheApi::isStored($cache_id)) {
            $guzzle = new \GuzzleHttp\Client([
                'verify'  => DIGITAL_CORE_DIR . '/vendor/cacert.pem',
                'timeout' => 20,
            ]);
            try {
                $result = @simplexml_load_string((string) $guzzle->get($url)->getBody(), $class_name);
            } catch (Exception $e) {
                return null;
            }

            CacheApi::store($cache_id, $result);

            return $result;
        }

        return CacheApi::retrieve($cache_id);
    }

    public function copy($source, $destination, $streamContext = null) {

        if ($streamContext) {
            $this->displayParameterAsDeprecated('streamContext');
        }

        if (!preg_match('/^https?:\/\//', $source)) {
            return @copy($source, $destination);
        }

        $timeout = ini_get('max_execution_time');

        if (!$timeout || $timeout > 600) {
            $timeout = 600;
        }

        $timeout -= 5; // Room for other processing.

        $guzzle = new \GuzzleHttp\Client([
            'verify'  => __DIR__ . '/../cacert.pem',
            'timeout' => $timeout,
        ]);

        try {
            $guzzle->get($source, ['sink' => $destination]);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    public function toCamelCase($str, $catapitaliseFirstChar = false) {

        $str = mb_strtolower($str);

        if ($catapitaliseFirstChar) {
            $str = $this->ucfirst($str);
        }

        return preg_replace_callback('/_+([a-z])/', function ($c) {

            return strtoupper($c[1]);
        }, $str);
    }

    public function ucfirst($str) {

        return ucfirst($str);
    }

    public function strtoupper($str) {

        if (is_array($str)) {
            return false;
        }

        return mb_strtoupper($str, 'utf-8');
    }

    public function toUnderscoreCase($string) {

        return mb_strtolower(trim(preg_replace('/([A-Z][a-z])/', '_$1', $string), '_'));
    }

    public function getBrightness($hex) {

        if (mb_strtolower($hex) == 'transparent') {
            return '129';
        }

        $hex = str_replace('#', '', $hex);

        if (mb_strlen($hex) == 3) {
            $hex .= $hex;
        }

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        return (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
    }

    public function parserSQL($sql) {

        if (strlen($sql) > 0) {
            $parser = new PHPSQLParser($sql);

            return $parser->parsed;
        }

        return false;
    }

    public function getMediaServer($filename) {

        return $this->usingSecureMode() ? $this->getDomainSSL() : $this->getDomain();
    }

    public function getDomainSsl($http = false, $entities = false) {

        $domain = $this->getHttpHost();

        if ($entities) {
            $domain = htmlspecialchars($domain, ENT_COMPAT, 'UTF-8');
        }

        if ($http) {
            $domain = ($this->context->phenyxConfig->get('EPH_SSL_ENABLED') ? 'https://' : 'http://') . $domain;
        }

        return $domain;
    }

    public function getDomain($http = false, $entities = false) {

        $domain = $this->getHttpHost();

        if ($entities) {
            $domain = htmlspecialchars($domain, ENT_COMPAT, 'UTF-8');
        }

        if ($http) {
            $domain = 'http://' . $domain;
        }

        return $domain;
    }

    public function generateHtaccess($path = null, $rewrite_settings = null, $cache_control = null, $specific = '', $disable_multiviews = null, $medias = false, $disable_modsec = null) {

        if (defined('EPH_INSTALLATION_IN_PROGRESS') && $rewrite_settings === null) {
            return true;
        }

        // Default values for parameters

        if (is_null($path)) {
            $path = _EPH_ROOT_DIR_ . '/.htaccess';
        }

        if (is_null($cache_control)) {
            $cache_control = (int) $this->context->phenyxConfig->get('EPH_HTACCESS_CACHE_CONTROL');
        }

        if (is_null($disable_multiviews)) {
            $disable_multiviews = (int) $this->context->phenyxConfig->get('EPH_HTACCESS_DISABLE_MULTIVIEWS');
        }

        if ($disable_modsec === null) {
            $disable_modsec = (int) $this->context->phenyxConfig->get('EPH_HTACCESS_DISABLE_MODSEC');
        }

        // Check current content of .htaccess and save all code outside of ephenyx comments
        $specific_before = $specific_after = '';

        if (file_exists($path)) {

            if (static::isSubmit('htaccess')) {
                $content = static::getValue('htaccess');
            } else {
                $content = file_get_contents($path);
            }

            if (preg_match('#^(.*)\# ~~start~~.*\# ~~end~~[^\n]*(.*)$#s', $content, $m)) {
                $specific_before = $m[1];
                $specific_after = $m[2];
            } else {
                // For retrocompatibility

                if (preg_match('#\# http://www\.ephenyx\.com - http://www\.ephenyx\.com/forums\s*(.*)<IfModule mod_rewrite\.c>#si', $content, $m)) {
                    $specific_before = $m[1];
                } else {
                    $specific_before = $content;
                }

            }

        }

        // Write .htaccess data

        if (!$write_fd = @fopen($path, 'w')) {
            return false;
        }

        if ($specific_before) {
            fwrite($write_fd, trim($specific_before) . "\n\n");
        }

        $domains = [];

        foreach (CompanyUrl::getCompanyUrls() as $company_url) {
            /** @var ShopUrl $company_url */

            if (!isset($domains[$company_url->domain])) {
                $domains[$company_url->domain] = [];
            }

            $domains[$company_url->domain][] = [
                'physical'   => $company_url->physical_uri,
                'virtual'    => $company_url->virtual_uri,
                'id_company' => $company_url->id_company,
            ];

            if ($company_url->domain == $company_url->domain_ssl) {
                continue;
            }

            if (!isset($domains[$company_url->domain_ssl])) {
                $domains[$company_url->domain_ssl] = [];
            }

            $domains[$company_url->domain_ssl][] = [
                'physical'   => $company_url->physical_uri,
                'virtual'    => $company_url->virtual_uri,
                'id_company' => $company_url->id_company,
            ];
        }

        // Write data in .htaccess file
        fwrite($write_fd, "# ~~start~~ Do not remove this comment, Ephenyx Digital will keep automatically the code outside this comment when .htaccess will be generated again\n");
        fwrite($write_fd, "# .htaccess automatically generated by Ephenyx Digital open-source solution\n");
        fwrite($write_fd, "# httpq://ephenyx.com - https://forum.ephenyx.com/forums\n\n");

        fwrite($write_fd, '# Apache 2.2' . "\n");
        fwrite($write_fd, '<IfModule !mod_authz_core.c>' . "\n");
        fwrite($write_fd, '    <Files ~ "(?i)^.*\.(webp)$">' . "\n");
        fwrite($write_fd, '        Allow from all' . "\n");
        fwrite($write_fd, '    </Files>' . "\n");
        fwrite($write_fd, '</IfModule>' . "\n");
        fwrite($write_fd, '# Apache 2.4' . "\n");
        fwrite($write_fd, '<IfModule mod_authz_core.c>' . "\n");
        fwrite($write_fd, '    <Files ~ "(?i)^.*\.(webp)$">' . "\n");
        fwrite($write_fd, '        Require all granted' . "\n");
        fwrite($write_fd, '        allow from all' . "\n");
        fwrite($write_fd, '    </Files>' . "\n");
        fwrite($write_fd, '</IfModule>' . "\n");

        fwrite($write_fd, "\n");
        //Check browser compatibility from .htacces
        fwrite($write_fd, "\n");
        fwrite($write_fd, '<IfModule mod_setenvif.c>' . "\n");
        fwrite($write_fd, 'SetEnvIf Request_URI "\.(jpe?g|png)$" REQUEST_image' . "\n");
        fwrite($write_fd, '</IfModule>' . "\n");
        fwrite($write_fd, "\n");

        fwrite($write_fd, '<IfModule mod_mime.c>' . "\n");
        fwrite($write_fd, 'AddType image/webp .webp' . "\n");
        fwrite($write_fd, '</IfModule>' . "\n");
        fwrite($write_fd, "<IfModule mod_headers.c>" . "\n");
        fwrite($write_fd, 'Header append Vary Accept env=REQUEST_image' . "\n");
        fwrite($write_fd, '</IfModule>' . "\n");

        if ($disable_modsec) {
            fwrite($write_fd, "<IfModule mod_security.c>\nSecFilterEngine Off\nSecFilterScanPOST Off\n</IfModule>\n\n");
        }

        // RewriteEngine
        fwrite($write_fd, "<IfModule mod_rewrite.c>\n");

        // Ensure HTTP_MOD_REWRITE variable is set in environment
        fwrite($write_fd, "<IfModule mod_env.c>\n");
        fwrite($write_fd, "SetEnv HTTP_MOD_REWRITE On\n");
        fwrite($write_fd, "</IfModule>\n\n");

        // Disable multiviews ?

        if ($disable_multiviews) {
            fwrite($write_fd, "\n# Disable Multiviews\nOptions -Multiviews\n\n");
        }

        fwrite($write_fd, "RewriteEngine on\n");
        fwrite($write_fd, 'RewriteCond %{HTTP_ACCEPT} image/webp' . "\n");
        fwrite($write_fd, 'RewriteCond %{DOCUMENT_ROOT}/$1.webp -f' . "\n");
        fwrite($write_fd, 'RewriteRule (.+)\.(jpe?g|png)$ $1.webp [T=image/webp]' . "\n");

        fwrite($write_fd, 'RewriteRule ^api$ api/ [L]' . "\n\n");
        fwrite($write_fd, 'RewriteRule ^api/(.*)$ %{ENV:REWRITEBASE}webephenyx/dispatcher.php?url=$1 [QSA,L]' . "\n\n");

        $media_domains = '';

        if ($this->context->phenyxConfig->get('EPH_WEBSERVICE_CGI_HOST')) {
            fwrite($write_fd, "RewriteCond %{HTTP:Authorization} ^(.*)\nRewriteRule . - [E=HTTP_AUTHORIZATION:%1]\n\n");
        }

        foreach ($domains as $domain => $list_uri) {
            $physicals = [];

            foreach ($list_uri as $uri) {
                fwrite($write_fd, PHP_EOL . PHP_EOL . '#Domain: ' . $domain . PHP_EOL);

                fwrite($write_fd, 'RewriteRule . - [E=REWRITEBASE:' . $uri['physical'] . ']' . "\n");

                // Webservice
                fwrite($write_fd, 'RewriteRule ^api$ api/ [L]' . "\n\n");
                fwrite($write_fd, 'RewriteRule ^api/(.*)$ %{ENV:REWRITEBASE}webephenyx/dispatcher.php?url=$1 [QSA,L]' . "\n\n");

                if ($domain == 'ephenyx.io') {
                    fwrite($write_fd, 'RewriteRule ^veille$ veille/ [L]' . "\n\n");
                    fwrite($write_fd, 'RewriteRule ^veille/(.*)$ %{ENV:REWRITEBASE}webephenyx/veille.php?url=$1 [QSA,L]' . "\n\n");

                    fwrite($write_fd, 'RewriteRule ^css$ css/ [L]' . "\n\n");
                    fwrite($write_fd, 'RewriteRule ^css/(.*)$ %{ENV:REWRITEBASE}webephenyx/css.php?url=$1 [QSA,L]' . "\n\n");

                    fwrite($write_fd, 'RewriteCond %{HTTP_HOST} ^cdn.ephenyx.io$ [NC]' . "\n\n");
                    fwrite($write_fd, 'RewriteRule ^(.*)$ https://ephenyx.io/ressource/$1 [L,NC,QSA]' . "\n\n");
                    
                    fwrite($write_fd, 'RewriteCond %{HTTP_HOST} ^fonts.ephenyx.io$ [NC]' . "\n\n");
                    fwrite($write_fd, 'RewriteRule ^veille/(.*)$ %{ENV:REWRITEBASE}webephenyx/fonts.php?url=$1 [QSA,L]' . "\n\n");
                    

                    fwrite($write_fd, 'RewriteCond %{HTTP_HOST} ^translations.ephenyx.io$ [NC]' . "\n\n");
                    fwrite($write_fd, 'RewriteRule ^(.*)$ https://ephenyx.io/ressource/$1 [L,NC,QSA]' . "\n\n");
                }

                if (!$rewrite_settings) {
                    $rewrite_settings = (int) $this->context->phenyxConfig->get('EPH_REWRITING_SETTINGS');
                }

                $domain_rewrite_cond = 'RewriteCond %{HTTP_HOST} ^' . $domain . '$' . "\n";
                // Rewrite virtual multishop uri

                if ($uri['virtual']) {

                    if (!$rewrite_settings) {
                        fwrite($write_fd, $media_domains);
                        fwrite($write_fd, $domain_rewrite_cond);
                        fwrite($write_fd, 'RewriteRule ^' . trim($uri['virtual'], '/') . '/?$ ' . $uri['physical'] . $uri['virtual'] . "index.php [L,R]\n");
                    } else {
                        fwrite($write_fd, $media_domains);
                        fwrite($write_fd, $domain_rewrite_cond);
                        fwrite($write_fd, 'RewriteRule ^' . trim($uri['virtual'], '/') . '$ ' . $uri['physical'] . $uri['virtual'] . " [L,R]\n");
                    }

                    fwrite($write_fd, $media_domains);
                    fwrite($write_fd, $domain_rewrite_cond);
                    fwrite($write_fd, 'RewriteRule ^' . ltrim($uri['virtual'], '/') . '(.*) ' . $uri['physical'] . "$1 [L]\n\n");
                }

            }

            // Redirections to dispatcher

            $addrewrite_settings = Hook::getInstance()->exec('addRewriteSeetings', ['media_domains' => $media_domains, 'domain_rewrite_cond' => $domain_rewrite_cond, 'uri' => $uri, 'rewrite_settings' => $rewrite_settings], null, true, false);

            if (is_array($addrewrite_settings)) {

                foreach ($addrewrite_settings as $key => $settings) {

                    foreach ($settings as $setting) {
                        fwrite($write_fd, $setting . "\n");
                    }

                }

            }

            if ($rewrite_settings) {
                fwrite($write_fd, "# AlphaImageLoader for IE and fancybox\n");
                fwrite($write_fd, 'RewriteRule ^images_ie/?([^/]+)\.(jpe?g|png|gif)$ js/jquery/plugins/fancybox/images/$1.$2 [L]' . "\n");
            }

            if ($rewrite_settings) {
                fwrite($write_fd, "\n# Dispatcher\n");
                fwrite($write_fd, "RewriteCond %{REQUEST_FILENAME} -s [OR]\n");
                fwrite($write_fd, "RewriteCond %{REQUEST_FILENAME} -l [OR]\n");
                fwrite($write_fd, "RewriteCond %{REQUEST_FILENAME} -d\n");
                fwrite($write_fd, "RewriteRule ^.*$ - [NC,L]\n");
                fwrite($write_fd, "RewriteRule ^.*\$ %{ENV:REWRITEBASE}index.php [NC,L]\n");

            }

        }

        fwrite($write_fd, "</IfModule>\n\n");

        fwrite($write_fd, "AddType application/vnd.ms-fontobject .eot\n");
        fwrite($write_fd, "AddType font/ttf .ttf\n");
        fwrite($write_fd, "AddType font/otf .otf\n");
        fwrite($write_fd, "AddType application/font-woff .woff\n");
        fwrite($write_fd, "AddType application/font-woff2 .woff2\n");
        fwrite($write_fd, "<IfModule mod_headers.c>
            <FilesMatch \"\.(ttf|ttc|otf|eot|woff|woff2|svg)$\">
        Header set Access-Control-Allow-Origin \"*\"
    </FilesMatch>
</IfModule>\n\n"
        );

        // Cache control

        if ($cache_control) {
            $cache_control = "<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/gif \"access plus 1 month\"
    ExpiresByType image/jpeg \"access plus 1 month\"
    ExpiresByType image/png \"access plus 1 month\"
    ExpiresByType image/webp \"access plus 1 month\"
    ExpiresByType text/css \"access plus 1 week\"
    ExpiresByType text/javascript \"access plus 1 week\"
    ExpiresByType application/javascript \"access plus 1 week\"
    ExpiresByType application/x-javascript \"access plus 1 week\"
    ExpiresByType image/x-icon \"access plus 1 year\"
    ExpiresByType image/svg+xml \"access plus 1 year\"
    ExpiresByType image/vnd.microsoft.icon \"access plus 1 year\"
    ExpiresByType application/font-woff \"access plus 1 year\"
    ExpiresByType application/x-font-woff \"access plus 1 year\"
    ExpiresByType font/woff2 \"access plus 1 year\"
    ExpiresByType application/vnd.ms-fontobject \"access plus 1 year\"
    ExpiresByType font/opentype \"access plus 1 year\"
    ExpiresByType font/ttf \"access plus 1 year\"
    ExpiresByType font/otf \"access plus 1 year\"
    ExpiresByType application/x-font-ttf \"access plus 1 year\"
    ExpiresByType application/x-font-otf \"access plus 1 year\"
</IfModule>

<IfModule mod_headers.c>
    Header unset Etag
</IfModule>
FileETag none
<IfModule mod_deflate.c>
    <IfModule mod_filter.c>
        AddOutputFilterByType DEFLATE text/html text/css text/javascript application/javascript application/x-javascript font/ttf application/x-font-ttf font/otf application/x-font-otf font/opentype
    </IfModule>
</IfModule>\n\n";
            fwrite($write_fd, $cache_control);
        }

        // In case the user hasn't rewrite mod enabled
        fwrite($write_fd, "#If rewrite mod isn't enabled\n");

        // Do not remove ($domains is already iterated upper)
        reset($domains);
        $domain = current($domains);
        fwrite($write_fd, 'ErrorDocument 404 ' . $domain[0]['physical'] . "index.php?controller=404\n\n");

        fwrite($write_fd, "# ~~end~~ Do not remove this comment, Ephenyx Shop will keep automatically the code outside this comment when .htaccess will be generated again");

        if ($specific_after) {
            fwrite($write_fd, "\n\n" . trim($specific_after));
        }

        fclose($write_fd);

        if (!defined('EPH_INSTALLATION_IN_PROGRESS')) {
            Hook::getInstance()->exec('actionHtaccessCreate');
        }

        return true;
    }

    public function generateCurrentJson() {

        if (file_exists(_EPH_CONFIG_DIR_ . 'json/new_json.json')) {
            $md5List = file_get_contents(_EPH_CONFIG_DIR_ . 'json/new_json.json');
            unlink(_EPH_CONFIG_DIR_ . 'json/new_json.json');
            return $this->jsonDecode($md5List, true);
        }

        $plugins = [];
        $installed_plugins = Plugin::getPluginsDirOnDisk();

        foreach ($installed_plugins as $plugin) {

            if (Plugin::isInstalled($plugin, false)) {
                $plugins[] = $plugin;
            }

        }

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
        ];
        $iso_langs = [];
        $languages = Language::getLanguages(false);

        foreach ($languages as $language) {
            $recursive_directory[] = 'content/translations/' . $language['iso_code'];
            $iso_langs[] = $language['iso_code'];
        }

        foreach ($plugins as $plugin) {

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

        $excludes = ['/phenyx-shop-default/css/', '/phenyx-shop-default/fonts/', '/phenyx-shop-default/font/', '/phenyx-shop-default/img/', '/phenyx-shop-default/js/', '/phenyx-shop-default/plugins/', '/phenyx-shop-default/pdf/'];
        $extraExludes = Hook::getInstance()->exec('actionGetExludes', [], null, true);

        if (is_array($extraExludes) && count($extraExludes)) {

            foreach ($extraExludes as $plugin => $exclude) {
                $excludes = array_merge(
                    $excludes,
                    $exclude
                );
            }

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

                foreach ($plugins as $plugin) {

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

    public function generateShopFile($iso_langs, $plugins, $excludes) {

        $recursive_directory = [
            'content/themes/phenyx-theme-default',
            'vendor/phenyxdigitale',
            'phenyxShop/app/xml',
        ];

        foreach ($iso_langs as $lang) {
            $recursive_directory[] = 'phenyxShop/content/translations/' . $lang;
        }

        foreach ($plugins as $plugin) {
            $recursive_directory[] = 'includes/plugins/' . $plugin;
        }

        $iterator = new AppendIterator();

        foreach ($recursive_directory as $key => $directory) {

            if (is_dir(_EPH_ROOT_DIR_ . '/' . $directory)) {
                $iterator->append(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(_EPH_ROOT_DIR_ . '/' . $directory . '/')));
            }

        }

        $iterator->append(new DirectoryIterator(_EPH_ROOT_DIR_ . '/phenyxShop/app/'));
        $iterator->append(new DirectoryIterator(_EPH_ROOT_DIR_ . '/content/themes/'));
        $iterator->append(new DirectoryIterator(_EPH_ROOT_DIR_ . '/phenyxShop/'));

        foreach ($iterator as $file) {
            $filePath = $file->getPathname();
            $filePath = str_replace(_EPH_ROOT_DIR_, '', $filePath);

            if (in_array($file->getFilename(), ['.', '..', '.htaccess', 'composer.lock', 'settings.inc.php', '.php-ini', '.php-version'])) {
                continue;
            }

            if (is_dir($file->getPathname())) {

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

            $ext = pathinfo($file->getFilename(), PATHINFO_EXTENSION);

            if (str_contains($filePath, '/plugins/') && str_contains($filePath, '/translations/')) {

                foreach ($plugins as $plugin) {

                    if (str_contains($filePath, '/plugins/' . $plugin . '/translations/')) {
                        $test = str_replace('/includes/plugins/' . $plugin . '/translations/', '', $filePath);
                        $test = str_replace('.php', '', $test);

                        if (!in_array($test, $iso_langs)) {
                            continue;
                        }

                    }

                }

            }

            if ($ext == 'txt') {
                continue;
            }

            if ($ext == 'zip') {
                continue;
            }

            if (str_contains($filePath, '/uploads/')) {
                continue;
            }

            if (str_contains($filePath, '/cache/')) {
                continue;
            }

            $md5List[$filePath] = md5_file($file->getPathname());
        }

        return $md5List;

    }

    public function generateDigitalFiles($iso_langs, $plugins) {

        $recursive_directory = [
            'phenyxDigital/app/xml',
            'content/backoffice',
            'content/css',
            'content/fonts',
            'content/localization',
            'content/img/pdfWorker',
            'content/js',
            'content/mails',
            'content/mp3',
            'content/pdf',
            'content/themes/phenyx-theme-default',
            'includes/classes',
            'includes/controllers',
            'vendor/phenyxdigitale',
            'webephenyx',
        ];

        foreach ($iso_langs as $lang) {
            $recursive_directory[] = 'phenyxDigital/content/translations/' . $lang;
        }

        foreach ($plugins as $plugin) {
            $recursive_directory[] = 'includes/plugins/' . $plugin;
        }

        $iterator = new AppendIterator();

        foreach ($recursive_directory as $key => $directory) {

            if (is_dir(_EPH_ROOT_DIR_ . '/' . $directory)) {
                $iterator->append(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(_EPH_ROOT_DIR_ . '/' . $directory . '/')));
            }

        }

        $iterator->append(new DirectoryIterator(_EPH_ROOT_DIR_ . '/phenyxDigital/app/'));
        $iterator->append(new DirectoryIterator(_EPH_ROOT_DIR_ . '/content/themes/'));
        $iterator->append(new DirectoryIterator(_EPH_ROOT_DIR_ . '/phenyxDigital/'));

        foreach ($iterator as $file) {
            $filePath = $file->getPathname();
            $filePath = str_replace(_EPH_ROOT_DIR_, '', $filePath);

            if (in_array($file->getFilename(), ['.', '..', '.htaccess', 'composer.lock', 'settings.inc.php', '.user.ini', '.php-ini', '.php-version'])) {
                continue;
            }

            if (is_dir($file->getPathname())) {

                continue;
            }

            $ext = pathinfo($file->getFilename(), PATHINFO_EXTENSION);

            if (str_contains($filePath, '/plugins/') && str_contains($filePath, '/translations/')) {

                foreach ($plugins as $plugin) {

                    if (str_contains($filePath, '/plugins/' . $plugin . '/translations/')) {
                        $test = str_replace('/includes/plugins/' . $plugin . '/translations/', '', $filePath);
                        $test = str_replace('.php', '', $test);

                        if (!in_array($test, $iso_langs)) {
                            continue;
                        }

                    }

                }

            }

            if ($ext == 'txt') {
                continue;
            }

            if ($ext == 'zip') {
                continue;
            }

            if (str_contains($filePath, '/uploads/')) {
                continue;
            }

            if (str_contains($filePath, '/cache/')) {
                continue;
            }

            $fileName = $file->getFilename();

            if (substr($fileName, 0, 4) == 'truc') {
                continue;
            }

            $md5List[$filePath] = md5_file($file->getPathname());
        }

        return $md5List;

    }

    public function generateLastPhenyxShopZip() {

        if (file_exists(_EPH_ROOT_DIR_ . '/phenyxTools/phenyxShop.zip')) {
            unlink(_EPH_ROOT_DIR_ . '/phenyxTools/phenyxShop.zip');
        }

        $rootPath = _EPH_ROOT_DIR_ . '/phenyxShop';
        $zip = new ZipArchive();
        $zip->open(_EPH_ROOT_DIR_ . '/phenyxTools/phenyxShop.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($rootPath),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $name => $file) {

            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($rootPath) + 1);
                $zip->addFile($filePath, $relativePath);
            }

        }

        $zip->close();
    }

    public function deleteFiles($file) {

        if (file_exists(_EPH_ROOT_DIR_ . $file)) {
            unlink(_EPH_ROOT_DIR_ . $file);
        }

        return true;

    }

    public function generateIndex() {

        if (defined('_DB_PREFIX_') && $this->context->phenyxConfig->get('EPH_DISABLE_OVERRIDES')) {
            PhenyxAutoload::getInstance()->_include_override_path = false;
        }

        PhenyxAutoload::getInstance()->generateIndex();
    }

    public function getDefaultIndexContent() {

        // Use a random, existing index.php as template.
        $content = file_get_contents(_EPH_ROOT_DIR_ . '/includes/classes/index.php');

        // Drop the license section, we can't really claim a license for an
        // auto-generated file.
        $replacement = '/* Auto-generated file, don\'t edit. */';
        $content = preg_replace('/\/\*.*\*\//s', $replacement, $content);

        return $content;
    }

    public function jsonDecode($json, $assoc = false) {

        if (is_array($json)) {
            return $json;
        }

        if (is_null($assoc)) {

            if (!is_null($json)) {
                return json_decode($json);
            }

        }

        if (!is_null($json)) {
            return json_decode($json, $assoc);
        }

    }

    public function jsonEncode($data, $encodeFlags = null) {

        if (is_null($encodeFlags)) {
            return json_encode($data);
        }

        return json_encode($data, $encodeFlags);
    }

    public function displayFileAsDeprecated() {

        $backtrace = debug_backtrace();
        $callee = current($backtrace);
        $error = 'File <b>' . $callee['file'] . '</b> is deprecated<br />';
        $message = 'The file ' . $callee['file'] . ' is deprecated and will be removed in the next major version.';
        $class = isset($callee['class']) ? $callee['class'] : null;

        $this->throwDeprecated($error, $message, $class);
    }

    public function enableCache($level = 1, $context = null) {

        $smarty = $this->context->smarty;

        if (!$this->context->phenyxConfig->get('EPH_SMARTY_CACHE')) {
            return;
        }

        if ($smarty->force_compile == 0 && $smarty->caching == $level) {
            return;
        }

        static::$_forceCompile = (int) $smarty->force_compile;
        static::$_caching = (int) $smarty->caching;
        $smarty->force_compile = 0;
        $smarty->caching = (int) $level;
        $smarty->cache_lifetime = 31536000; // 1 Year
    }

    public function restoreCacheSettings($context = null) {

        if (isset(static::$_forceCompile)) {
            $this->context->smarty->force_compile = (int) static::$_forceCompile;
        }

        if (isset(static::$_caching)) {
            $this->context->smarty->caching = (int) static::$_caching;
        }

    }

    public function isCallable($function) {

        $disabled = explode(',', ini_get('disable_functions'));

        return (!in_array($function, $disabled) && is_callable($function));
    }

    public function pRegexp($s, $delim) {

        $s = str_replace($delim, '\\' . $delim, $s);

        foreach (['?', '[', ']', '(', ')', '{', '}', '-', '.', '+', '*', '^', '$', '`', '"', '%'] as $char) {
            $s = str_replace($char, '\\' . $char, $s);
        }

        return $s;
    }

    public function str_replace_once($needle, $replace, $haystack) {

        $pos = false;

        if ($needle) {
            $pos = strpos($haystack, $needle);
        }

        if ($pos === false) {
            return $haystack;
        }

        return substr_replace($haystack, $replace, $pos, strlen($needle));
    }

    public function checkPhpVersion() {

        $version = null;

        if (defined('PHP_VERSION')) {
            $version = PHP_VERSION;
        } else {
            $version = phpversion('');
        }

        //Case management system of ubuntu, php version return 5.2.4-2ubuntu5.2

        if (strpos($version, '-') !== false) {
            $version = substr($version, 0, strpos($version, '-'));
        }

        return $version;
    }

    public function ZipTest($fromFile) {

        $zip = new ZipArchive();

        return ($zip->open($fromFile, ZIPARCHIVE::CHECKCONS) === true);
    }

    public function getSafeModeStatus() {

        $this->displayAsDeprecated();

        return false;
    }

    public function ZipExtract($fromFile, $toDir) {

        if (!file_exists($toDir)) {
            mkdir($toDir, 0777);
        }

        $zip = new ZipArchive();

        if ($zip->open($fromFile) === true && $zip->extractTo($toDir) && $zip->close()) {
            return true;
        }

        return false;
    }

    public function chmodr($path, $filemode) {

        if (!is_dir($path)) {
            return @chmod($path, $filemode);
        }

        $dh = opendir($path);

        while (($file = readdir($dh)) !== false) {

            if ($file != '.' && $file != '..') {
                $fullpath = $path . '/' . $file;

                if (is_link($fullpath)) {
                    return false;
                } else

                if (!is_dir($fullpath) && !@chmod($fullpath, $filemode)) {
                    return false;
                } else

                if (!$this->chmodr($fullpath, $filemode)) {
                    return false;
                }

            }

        }

        closedir($dh);

        if (@chmod($path, $filemode)) {
            return true;
        } else {
            return false;
        }

    }

    public function display404Error() {

        header('HTTP/1.1 404 Not Found');
        header('Status: 404 Not Found');
        include dirname(__FILE__) . '/../404.php';
        die;
    }

    public function url($begin, $end) {

        return $begin . ((strpos($begin, '?') !== false) ? '&' : '?') . $end;
    }

    public function dieOrLog($msg, $die = true) {

        $this->displayAsDeprecated();

        if ($die || (defined('_EPH_MODE_DEV_') && _EPH_MODE_DEV_)) {
            die($msg);
        }

        return Logger::addLog($msg);
    }

    public function nl2br($str) {

        if (!is_null($str)) {
            return str_replace(["\r\n", "\r", "\n"], '<br />', $str);
        }

        return $str;

    }

    public function clearSmartyCache() {

        $smarty = $this->context->smarty;
        $this->clearCache($smarty);
        $this->clearCompile($smarty);
    }

    public function clearCache($smarty = null, $tpl = false, $cacheId = null, $compileId = null) {

        if ($smarty === null) {
            $smarty = $this->context->smarty;
        }

        if ($smarty === null) {
            return;
        }

        if (!$tpl && $cacheId === null && $compileId === null) {
            return $smarty->clearAllCache();
        }

        return $smarty->clearCache($tpl, $cacheId, $compileId);
    }

    public function getCmsPath($idCms, $path) {

        $ajax_mode = $this->context->phenyxConfig->get('EPH_FRONT_AJAX') ? 1 : 0;
        $cms_ajax_mode = $this->context->phenyxConfig->get('EPH_CMS_AJAX') ? 1 : 0;

        $idCms = (int) $idCms;

        $path = '<span class="navigation_end">' . $path . '</span>';

        $pipe = $this->context->phenyxConfig->get('EPH_NAVIGATION_PIPE');

        if (empty($pipe)) {
            $pipe = '>';
        }

        $fullPath = [];
        $finalPath = '';
        $cms = new CMS($idCms, $this->context->language->id);

        if ($cms->level_depth == 1) {
            return $path;
        }

        $level_depth = $cms->level_depth - 1;

        for ($i = $level_depth; $i > 0; $i--) {
            $cms = new CMS($cms->id_parent, $this->context->language->id);

            if ($ajax_mode && $cms_ajax_mode) {
                $fullPath[$i] = '<a href="javascript:void(0)" onClick="openAjaxCms(' . $cms->id . ')" title="' . $cms->meta_title . '" data-gg="">' . htmlentities($cms->meta_title, ENT_NOQUOTES, 'UTF-8') . '</a><span class="navigation-pipe">' . $pipe . '</span>';
            } else {
                $fullPath[$i] = '<a href="' . $this->context->_link->getCMSLink($cms->id) . '" title="' . $cms->meta_title . '" data-gg="">' . htmlentities($cms->meta_title, ENT_NOQUOTES, 'UTF-8') . '</a><span class="navigation-pipe">' . $pipe . '</span>';
            }

        }

        ksort($fullPath);

        foreach ($fullPath as $key => $value) {
            $finalPath .= $value;
        }

        return $finalPath . $path;

    }

    public function getWikiPath($idWiki, $path) {

        $ajax_mode = $this->context->phenyxConfig->get('EPH_FRONT_AJAX') ? 1 : 0;
        $wiki_ajax_mode = $this->context->phenyxConfig->get('EPH_WIKI_AJAX') ? 1 : 0;

        $idWiki = (int) $idWiki;

        $path = '<span class="navigation_end">' . $path . '</span>';

        $pipe = $this->context->phenyxConfig->get('EPH_NAVIGATION_PIPE');

        if (empty($pipe)) {
            $pipe = '>';
        }

        $fullPath = [];
        $finalPath = '';
        $wiki = new PhenyxWiki($idWiki, $this->context->language->id);

        if ($wiki->level_depth == 1) {
            return $path;
        }

        $level_depth = $wiki->level_depth - 1;

        for ($i = $level_depth; $i > 0; $i--) {
            $wiki = new PhenyxWiki($wiki->id_parent, $this->context->language->id);

            if ($ajax_mode && $wiki_ajax_mode) {
                $fullPath[$i] = '<a href="javascript:void(0)" onClick="openAjaxCms(' . $wiki->id . ')" data-gg="">' . htmlentities($wiki->meta_title, ENT_NOQUOTES, 'UTF-8') . '</a><span class="navigation-pipe">' . $pipe . '</span>';
            } else {
                $fullPath[$i] = '<a href="' . $this->context->_link->getPhenyxWikiLink($wiki->id) . '" data-gg="">' . htmlentities($wiki->meta_title, ENT_NOQUOTES, 'UTF-8') . '</a><span class="navigation-pipe">' . $pipe . '</span>';
            }

        }

        ksort($fullPath);

        foreach ($fullPath as $key => $value) {
            $finalPath .= $value;
        }

        return $finalPath . $path;

    }

    public function getFormPath($idForm, $path) {

        $idForm = (int) $idForm;

        $path = '<span class="navigation_end">' . $path . '</span>';

        return $path;

    }

    public function clearCompile($smarty = null) {

        if ($smarty === null) {
            $smarty = $this->context->smarty;
        }

        if ($smarty === null) {
            return;
        }

        return $smarty->clearCompiledTemplate();
    }

    public function cleanFrontCache() {

        $recursive_directory = [
            'app/cache/smarty/cache',
            'app/cache/smarty/compile',
            'app/cache/purifier/CSS',
            'app/cache/purifier/URI',
        ];

        foreach ($recursive_directory as $key => $directory) {

            if (is_dir(_EPH_ROOT_DIR_ . '/' . $directory)) {
                $path = _EPH_ROOT_DIR_ . '/' . $directory;
                $this->deleteDirectory($path, false);
                $this->generateIndexFiles($path . '/');
            }

        }

        $this->context->media->clearAdminCache();
        $this->context->media->clearCache();
        Hook::getInstance()->exec('clearFrontCache', []);

    }

    public function cleanThemeDirectory($context = null) {
        
        $plugToCheck = [];

        
        if (is_dir($this->context->theme->path . 'css/plugins/')) {
            $css = glob($this->context->theme->path . 'css/plugins/' . "*", GLOB_ONLYDIR);

        }
        foreach($css as $path) {
           $plugToCheck[] = basename($path);
        }
        
        if (is_dir($this->context->theme->path . 'js/plugins/')) {
            $js = glob($this->context->theme->path . 'js/plugins/' . "*", GLOB_ONLYDIR);
        }
        foreach($js as $path) {
           $plugToCheck[] = basename($path);
        }
        
        if (is_dir($this->context->theme->path . 'plugins/')) {
            $plugs = glob($this->context->theme->path . 'plugins/' . "*", GLOB_ONLYDIR);
        }
        foreach($plugs as $path) {
           $plugToCheck[] = basename($path);
        }
        
        $folder = [];
        $plugins = Plugin::getPluginsOnDisk();

        foreach ($plugins as $plugin) {

            if (file_exists(_EPH_PLUGIN_DIR_ . $plugin->name . '/' . $plugin->name . '.php')) {
                $folder[] = $plugin->name;
            }

        }
        

        foreach ($plugToCheck as $plugin) {

            if (!in_array($plugin, $folder)) {

                if (is_dir($this->context->theme->path . 'css/plugins/'. $plugin)) {
                    $this->recursiveDeleteOnDisk($this->context->theme->path . 'css/plugins/'. $plugin);

                }

                if (is_dir($this->context->theme->path . 'js/plugins/'. $plugin)) {
                    $this->recursiveDeleteOnDisk($this->context->theme->path . 'js/plugins/'. $plugin);
                }

                if (is_dir($this->context->theme->path . 'plugins/'. $plugin)) {
                    $this->recursiveDeleteOnDisk($this->context->theme->path . '/plugins/'. $plugin);
                }

            }

        }

        Hook::getInstance()->exec('cleanThemeDirectory', ['plugintochecks' => $plugintochecks]);

    }

    public function recursiveDeleteOnDisk($dir) {

        if (is_dir($dir)) {
            $objects = scandir($dir);

            foreach ($objects as $object) {

                if ($object != '.' && $object != '..') {

                    if (filetype($dir . '/' . $object) == 'dir') {
                        $this->recursiveDeleteOnDisk($dir . '/' . $object);
                    } else {
                        unlink($dir . '/' . $object);
                    }

                }

            }

            reset($objects);
            rmdir($dir);
           
        }

    }

    public function cleanEmptyDirectory() {

        $recursive_directory = ['includes/classes', 'includes/controllers', 'includes/plugins', 'content/js', 'content/backoffice/backend'];

        $iterator = new AppendIterator();

        foreach ($recursive_directory as $key => $directory) {
            $iterator->append(new DirectoryIterator(_EPH_ROOT_DIR_ . '/' . $directory . '/'));
        }

        foreach ($iterator as $file) {
            $fileName = $file->getFilename();
            $filePath = $file->getPathname();

            if (str_contains($filePath, '/cache/')) {
                continue;
            }

            $path = str_replace($fileName, '', $filePath);

            if (is_dir($path)) {
                $this->removeEmptyDirs($path);
            }

        }

        $iterator = new AppendIterator();
        $iterator->append(new DirectoryIterator(_EPH_ROOT_DIR_ . '/'));

        foreach ($iterator as $file) {

            $filePath = $file->getPathname();
            $ext = pathinfo($file->getFilename(), PATHINFO_EXTENSION);

            if ($ext == 'txt') {
                unlink($filePath);
            }

        }

        mkdir(_EPH_ROOT_DIR_ . '/content/backoffice/backend/cache', 0777, true);
        $this->generateIndexFiles(_EPH_ROOT_DIR_ . '/content/backoffice/backend/cache/');

    }

    public function reGenerateilesIndex() {

        $recursive_directory = [
            'includes',
            'app',
            'content',
        ];

        $iterator = new AppendIterator();

        foreach ($recursive_directory as $key => $directory) {

            if (is_dir(_EPH_ROOT_DIR_ . '/' . $directory)) {
                $iterator->append(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(_EPH_ROOT_DIR_ . '/' . $directory . '/')));
            }

        }

        foreach ($iterator as $file) {

            if ($file->getFilename() == '..') {
                $filePathTest = $file->getPathname();
                $test = str_replace('..', '', $filePathTest);

                if (!file_exists($test . 'index.php')) {
                    $diretory = str_replace(_EPH_ROOT_DIR_, '', $test);
                    $level = substr_count($diretory, '/') - 1;
                    $this->generateIndexFiles($test, $level);
                }

            }

            if ($file->getFilename() == 'index.php') {
                $path = '';
                $filePath = $file->getPathname();
                $diretory = str_replace(_EPH_ROOT_DIR_, '', $filePath);
                $level = substr_count($diretory, '/') - 1;
                $this->generateIndexFiles(str_replace('index.php', '', $filePath), $level);
            }

        }

    }

    public function rebuildInexFiles() {

        $recursive_directory = [
            'includes',
            'app',
            'content',
        ];

        $iterator = new AppendIterator();

        foreach ($recursive_directory as $key => $directory) {

            if (is_dir(_EPH_ROOT_DIR_ . '/' . $directory)) {
                $iterator->append(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(_EPH_ROOT_DIR_ . '/' . $directory . '/')));
            }

        }

        foreach ($iterator as $file) {

            if ($file->getFilename() == '..') {
                $filePathTest = $file->getPathname();
                $test = str_replace('..', '', $filePathTest);

                if (!file_exists($test . 'index.php')) {
                    $diretory = str_replace(_EPH_ROOT_DIR_, '', $test);
                    $level = substr_count($diretory, '/') - 1;
                    $this->generateIndexFiles($test, $level);
                }

            }

            if ($file->getFilename() == 'index.php') {
                $path = '';
                $filePath = $file->getPathname();
                $diretory = str_replace(_EPH_ROOT_DIR_, '', $filePath);
                $level = substr_count($diretory, '/') - 1;

                for ($i = 0; $i <= $level; $i++) {
                    $path .= "../";
                }

                $this->generateIndexFiles(str_replace('index.php', '', $filePath), $level);
            }

            if ($file->getFilename() == '..index.php') {
                $filePath = $file->getPathname();
                unlink($filePath);
            }

            if ($file->getFilename() == '.index.php') {
                $filePath = $file->getPathname();
                unlink($filePath);
            }

        }

    }

    public function generateIndexFiles($directory, $level = 1) {

        $path = '';

        for ($i = 0; $i <= $level; $i++) {
            $path .= "../";
        }

        $file = fopen($directory . "index.php", "w");
        fwrite($file, "<?php" . "\n\n");
        fwrite($file, "header(\"Expires: Mon, 26 Jul 1997 05:00:00 GMT\");" . "\n");
        fwrite($file, "header(\"Last-Modified: \".gmdate(\"D, d M Y H:i:s\").\" GMT\");" . "\n\n");
        fwrite($file, "header(\"Cache-Control: no-store, no-cache, must-revalidate\");" . "\n");
        fwrite($file, "header(\"Cache-Control: post-check=0, pre-check=0\", false);" . "\n");
        fwrite($file, "header(\"Pragma: no-cache\");" . "\n\n");
        fwrite($file, "header(\"Location: " . $path . "\");" . "\n");
        fwrite($file, "exit;");
        fclose($file);
    }

    public function clearColorListCache($id_product = false) {

        // Change template dir if called from the BackOffice
        $current_template_dir = $this->context->smarty->getTemplateDir();
        $this->context->smarty->setTemplateDir(_EPH_THEME_DIR_);
        $this->clearCache(null, _EPH_THEME_DIR_ . 'product-list-colors.tpl', Product::getColorsListCacheId((int) $id_product, false));
        $this->context->smarty->setTemplateDir($current_template_dir);
    }

    public function getMemoryLimit() {

        $memory_limit = @ini_get('memory_limit');

        return $this->getOctets($memory_limit);
    }

    public function getOctets($option) {

        if (preg_match('/[0-9]+k/i', $option)) {
            return 1024 * (int) $option;
        }

        if (preg_match('/[0-9]+m/i', $option)) {
            return 1024 * 1024 * (int) $option;
        }

        if (preg_match('/[0-9]+g/i', $option)) {
            return 1024 * 1024 * 1024 * (int) $option;
        }

        return $option;
    }

    public function isX86_64arch() {

        return (PHP_INT_MAX == '9223372036854775807');
    }

    public function isPHPCLI() {

        return (defined('STDIN') || (mb_strtolower(php_sapi_name()) == 'cli' && (!isset($_SERVER['REMOTE_ADDR']) || empty($_SERVER['REMOTE_ADDR']))));
    }

    public function argvToGET($argc, $argv) {

        if ($argc <= 1) {
            return;
        }

        // get the first argument and parse it like a query string
        parse_str($argv[1], $args);

        if (!is_array($args) || !count($args)) {
            return;
        }

        $_GET = array_merge($args, $_GET);
        $_SERVER['QUERY_STRING'] = $argv[1];
    }

    public function getMaxUploadSize($max_size = 0) {

        $post_max_size = $this->convertBytes(ini_get('post_max_size'));
        $upload_max_filesize = $this->convertBytes(ini_get('upload_max_filesize'));

        if ($max_size > 0) {
            $result = min($post_max_size, $upload_max_filesize, $max_size);
        } else {
            $result = min($post_max_size, $upload_max_filesize);
        }

        return $result;
    }

    public function convertBytes($value) {

        if (is_numeric($value)) {
            return $value;
        } else {
            $value_length = strlen($value);
            $qty = (int) substr($value, 0, $value_length - 1);
            $unit = mb_strtolower(substr($value, $value_length - 1));

            switch ($unit) {
            case 'k':
                $qty *= 1024;
                break;
            case 'm':
                $qty *= 1048576;
                break;
            case 'g':
                $qty *= 1073741824;
                break;
            }

            return $qty;
        }

    }

    public function recurseCopy($src, $dst, $del = false) {

        if (!file_exists($src)) {
            return false;
        }

        $dir = opendir($src);

        if (!file_exists($dst)) {
            mkdir($dst);
        }

        while (false !== ($file = readdir($dir))) {

            if (($file != '.') && ($file != '..')) {

                if (is_dir($src . DIRECTORY_SEPARATOR . $file)) {
                    static::recurseCopy($src . DIRECTORY_SEPARATOR . $file, $dst . DIRECTORY_SEPARATOR . $file, $del);
                } else {
                    copy($src . DIRECTORY_SEPARATOR . $file, $dst . DIRECTORY_SEPARATOR . $file);

                    if ($del && is_writable($src . DIRECTORY_SEPARATOR . $file)) {
                        unlink($src . DIRECTORY_SEPARATOR . $file);
                    }

                }

            }

        }

        closedir($dir);

        if ($del && is_writable($src)) {
            rmdir($src);
        }

    }

    public function file_exists_cache($filename) {

        if (!isset(static::$file_exists_cache[$filename])) {
            static::$file_exists_cache[$filename] = file_exists($filename);
        }

        return static::$file_exists_cache[$filename];
    }

    public function scandir($path, $ext = 'php', $dir = '', $recursive = false) {

        $path = rtrim(rtrim($path, '\\'), '/') . '/';
        $real_path = rtrim(rtrim($path . $dir, '\\'), '/') . '/';
        $files = scandir($real_path);

        if (!$files) {
            return [];
        }

        $filtered_files = [];

        $real_ext = false;

        if (!empty($ext)) {
            $real_ext = '.' . $ext;
        }

        $real_ext_length = strlen($real_ext);

        $subdir = ($dir) ? $dir . '/' : '';

        foreach ($files as $file) {

            if (!$real_ext || (strpos($file, $real_ext) && strpos($file, $real_ext) == (strlen($file) - $real_ext_length))) {
                $filtered_files[] = $subdir . $file;
            }

            if ($recursive && $file[0] != '.' && is_dir($real_path . $file)) {

                foreach ($this->scandir($path, $ext, $subdir . $file, $recursive) as $subfile) {
                    $filtered_files[] = $subfile;
                }

            }

        }

        return $filtered_files;
    }

    public function version_compare($v1, $v2, $operator = '<') {

        $this->alignVersionNumber($v1, $v2);

        return version_compare($v1, $v2, $operator);
    }

    public function alignVersionNumber(&$v1, &$v2) {

        $len1 = count(explode('.', trim($v1, '.')));
        $len2 = count(explode('.', trim($v2, '.')));
        $len = 0;
        $str = '';

        if ($len1 > $len2) {
            $len = $len1 - $len2;
            $str = &$v2;
        } else

        if ($len2 > $len1) {
            $len = $len2 - $len1;
            $str = &$v1;
        }

        for ($len; $len > 0; $len--) {
            $str .= '.0';
        }

    }

    public function modRewriteActive() {

        return true;
    }

    public function apacheModExists($name) {

        if (function_exists('apache_get_plugins')) {
            static $apache_plugin_list = null;

            if (!is_array($apache_plugin_list)) {
                $apache_plugin_list = apache_get_plugins();
            }

            // we need strpos (example, evasive can be evasive20)

            foreach ($apache_plugin_list as $plugin) {

                if (strpos($plugin, $name) !== false) {
                    return true;
                }

            }

        }

        return false;
    }

    public function unSerialize($serialized, $object = false) {

        if (is_string($serialized) && (strpos($serialized, 'O:') === false || !preg_match('/(^|;|{|})O:[0-9]+:"/', $serialized)) && !$object || $object) {
            return @unserialize($serialized);
        }

        return false;
    }

    public function arrayUnique($array) {

        if (version_compare(phpversion(), '5.2.9', '<')) {
            return array_unique($array);
        } else {
            return array_unique($array, SORT_REGULAR);
        }

    }

    public function cleanNonUnicodeSupport($pattern) {

        if (!defined('PREG_BAD_UTF8_OFFSET')) {
            return $pattern;
        }

        return preg_replace('/\\\[px]\{[a-z]{1,2}\}|(\/[a-z]*)u([a-z]*)$/i', '$1$2', $pattern);
    }

    public function addonsRequest($request, $params = []) {

        return false;
    }

    public function fileAttachment($input = 'fileUpload', $return_content = true) {

        $file_attachment = null;

        if (isset($_FILES[$input]['name']) && !empty($_FILES[$input]['name']) && !empty($_FILES[$input]['tmp_name'])) {
            $file_attachment['rename'] = uniqid() . mb_strtolower(substr($_FILES[$input]['name'], -5));

            if ($return_content) {
                $file_attachment['content'] = file_get_contents($_FILES[$input]['tmp_name']);
            }

            $file_attachment['tmp_name'] = $_FILES[$input]['tmp_name'];
            $file_attachment['name'] = $_FILES[$input]['name'];
            $file_attachment['mime'] = $_FILES[$input]['type'];
            $file_attachment['error'] = $_FILES[$input]['error'];
            $file_attachment['size'] = $_FILES[$input]['size'];
        }

        return $file_attachment;
    }

    public function changeFileMTime($file_name) {

        @touch($file_name);
    }

    public function waitUntilFileIsModified($file_name, $timeout = 180) {

        @ini_set('max_execution_time', $timeout);

        if (($time_limit = ini_get('max_execution_time')) === null) {
            $time_limit = 30;
        }

        $time_limit -= 5;
        $start_time = microtime(true);
        $last_modified = @filemtime($file_name);

        while (true) {

            if (((microtime(true) - $start_time) > $time_limit) || @filemtime($file_name) > $last_modified) {
                break;
            }

            clearstatcache();
            usleep(300);
        }

    }

    public function rtrimString($str, $str_search) {

        $length_str = strlen($str_search);

        if (strlen($str) >= $length_str && substr($str, -$length_str) == $str_search) {
            $str = substr($str, 0, -$length_str);
        }

        return $str;
    }

    public function formatBytes($size, $precision = 2) {

        if (!$size) {
            return '0';
        }

        $base = log($size) / log(1024);
        $suffixes = ['', 'k', 'M', 'G', 'T'];

        return round(pow(1024, $base - floor($base)), $precision) . $suffixes[floor($base)];
    }

    public function boolVal($value) {

        if (empty($value)) {
            $value = false;
        }

        return (bool) $value;
    }

    public function getUserPlatform() {

        if (isset(static::$_user_plateform)) {
            return static::$_user_plateform;
        }

        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        static::$_user_plateform = 'unknown';

        if (preg_match('/linux/i', $user_agent)) {
            static::$_user_plateform = 'Linux';
        } else

        if (preg_match('/macintosh|mac os x/i', $user_agent)) {
            static::$_user_plateform = 'Mac';
        } else

        if (preg_match('/windows|win32/i', $user_agent)) {
            static::$_user_plateform = 'Windows';
        }

        return static::$_user_plateform;
    }

    public function getUserBrowser() {

        if (isset(static::$_user_browser)) {
            return static::$_user_browser;
        }

        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        static::$_user_browser = 'unknown';

        if (preg_match('/MSIE/i', $user_agent) && !preg_match('/Opera/i', $user_agent)) {
            static::$_user_browser = 'Internet Explorer';
        } else

        if (preg_match('/Firefox/i', $user_agent)) {
            static::$_user_browser = 'Mozilla Firefox';
        } else

        if (preg_match('/Chrome/i', $user_agent)) {
            static::$_user_browser = 'Google Chrome';
        } else

        if (preg_match('/Safari/i', $user_agent)) {
            static::$_user_browser = 'Apple Safari';
        } else

        if (preg_match('/Opera/i', $user_agent)) {
            static::$_user_browser = 'Opera';
        } else

        if (preg_match('/Netscape/i', $user_agent)) {
            static::$_user_browser = 'Netscape';
        }

        return static::$_user_browser;
    }

    public function getDescriptionClean($description) {

        if (is_null($description)) {
            return $description;
        }

        return strip_tags(stripslashes($description));
    }

    public function purifyHTML($html, $uriUnescape = null, $allowStyle = false) {

        static $use_html_purifier = null;
        static $purifier = null;

        if (defined('EPH_INSTALLATION_IN_PROGRESS') || !$this->context->phenyxConfig->configurationIsLoaded()) {
            return $html;
        }

        if ($use_html_purifier === null) {
            $use_html_purifier = (bool) $this->context->phenyxConfig->get('EPH_USE_HTMLPURIFIER');
        }

        if ($use_html_purifier) {

            if ($purifier === null) {
                $config = HTMLPurifier_Config::createDefault();

                $config->set('Attr.EnableID', true);
                $config->set('HTML.Trusted', true);
                $config->set('Cache.SerializerPath', _EPH_CACHE_DIR_ . 'purifier');
                $config->set('Attr.AllowedFrameTargets', ['_blank', '_self', '_parent', '_top']);
                $config->set('Core.NormalizeNewlines', false);

                if (is_array($uriUnescape)) {
                    $config->set('URI.UnescapeCharacters', implode('', $uriUnescape));
                }

                if ($this->context->phenyxConfig->get('EPH_ALLOW_HTML_IFRAME')) {
                    $config->set('HTML.SafeIframe', true);
                    $config->set('HTML.SafeObject', true);
                    $config->set('URI.SafeIframeRegexp', '/.*/');
                }

                /** @var HTMLPurifier_HTMLDefinition|HTMLPurifier_HTMLPlugin $def */
                // http://developers.whatwg.org/the-video-element.html#the-video-element

                if ($def = $config->getHTMLDefinition(true)) {
                    $def->addElement(
                        'video',
                        'Block',
                        'Optional: (source, Flow) | (Flow, source) | Flow',
                        'Common',
                        [
                            'src'      => 'URI',
                            'type'     => 'Text',
                            'width'    => 'Length',
                            'height'   => 'Length',
                            'poster'   => 'URI',
                            'preload'  => 'Enum#auto,metadata,none',
                            'controls' => 'Bool',
                        ]
                    );
                    $def->addElement(
                        'source',
                        'Block',
                        'Flow',
                        'Common',
                        [
                            'src'  => 'URI',
                            'type' => 'Text',
                        ]
                    );
                    $def->addElement(
                        'meta',
                        'Inline',
                        'Empty',
                        'Common',
                        [
                            'itemprop'  => 'Text',
                            'itemscope' => 'Bool',
                            'itemtype'  => 'URI',
                            'name'      => 'Text',
                            'content'   => 'Text',
                        ]
                    );
                    $def->addElement(
                        'link',
                        'Inline',
                        'Empty',
                        'Common',
                        [
                            'rel'   => 'Text',
                            'href'  => 'Text',
                            'sizes' => 'Text',
                        ]
                    );

                    if ($allowStyle) {
                        $def->addElement('style', 'Block', 'Flow', 'Common', ['type' => 'Text']);
                    }

                }

                $purifier = new HTMLPurifier($config);
            }

            if (_EPH_MAGIC_QUOTES_GPC_) {
                $html = stripslashes($html);
            }

            $html = $purifier->purify($html);

            if (_EPH_MAGIC_QUOTES_GPC_) {
                $html = addslashes($html);
            }

        }

        return $html;
    }

    public function safeDefine($constant, $value) {

        if (!defined($constant)) {
            define($constant, $value);
        }

    }

    public function arrayReplaceRecursive($base, $replacements) {

        if (function_exists('array_replace_recursive')) {
            return array_replace_recursive($base, $replacements);
        }

        foreach (array_slice(func_get_args(), 1) as $replacements) {
            $brefStack = [ & $base];
            $headStack = [$replacements];

            do {
                end($brefStack);

                $bref = &$brefStack[key($brefStack)];
                $head = array_pop($headStack);
                unset($brefStack[key($brefStack)]);

                foreach (array_keys($head) as $key) {

                    if (isset($key, $bref) && is_array($bref[$key]) && is_array($head[$key])) {
                        $brefStack[] = &$bref[$key];
                        $headStack[] = $head[$key];
                    } else {
                        $bref[$key] = $head[$key];
                    }

                }

            } while (count($headStack));

        }

        return $base;
    }

    public function smartyImplode($params, $template) {

        if (!isset($params['value'])) {
            trigger_error("[plugin] implode parameter 'value' cannot be empty", E_USER_NOTICE);
            return;
        }

        if (empty($params['separator'])) {
            $params['separator'] = ',';
        }

        return implode($params['separator'], $params['value']);
    }

    protected static $encodeTable = [
        'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l',
        'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x',
        'y', 'z', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9',
    ];

    protected static $decodeTable = [
        'a' => 0, 'b'  => 1, 'c'  => 2, 'd'  => 3, 'e'  => 4, 'f'  => 5,
        'g' => 6, 'h'  => 7, 'i'  => 8, 'j'  => 9, 'k'  => 10, 'l' => 11,
        'm' => 12, 'n' => 13, 'o' => 14, 'p' => 15, 'q' => 16, 'r' => 17,
        's' => 18, 't' => 19, 'u' => 20, 'v' => 21, 'w' => 22, 'x' => 23,
        'y' => 24, 'z' => 25, '0' => 26, '1' => 27, '2' => 28, '3' => 29,
        '4' => 30, '5' => 31, '6' => 32, '7' => 33, '8' => 34, '9' => 35,
    ];

    public function convertEmailToIdn($email) {

        if (mb_detect_encoding($email, 'UTF-8', true) && mb_strpos($email, '@') > -1) {
            // Convert to IDN
            list($local, $domain) = explode('@', $email, 2);
            $domain = $this->utf8ToIdn($domain);
            $email = "$local@$domain";
        }

        return $email;
    }

    /**
     * Convert an IDN email to UTF-8 (domain part only)
     *
     * @param string $email
     *
     * @return string
     */
    public function convertEmailFromIdn($email) {

        if (mb_strpos($email, '@') > -1) {
            // Convert from IDN if necessary
            list($local, $domain) = explode('@', $email, 2);
            $domain = $this->idnToUtf8($domain);
            $email = "$local@$domain";
        }

        return $email;
    }

    /**
     * Encode a domain to its Punycode version
     *
     * @param string $input Domain name in Unicode to be encoded
     *
     * @return string Punycode representation in ASCII
     *
     * @since 1.0.4
     *
     * @copyright 2014 TrueServer B.V. (https://github.com/true/php-punycode)
     */
    public function utf8ToIdn($input) {

        $input = mb_strtolower($input);
        $parts = explode('.', $input);

        foreach ($parts as &$part) {
            $length = strlen($part);

            if ($length < 1) {
                return false;
            }

            $part = static::encodePart($part);
        }

        $output = implode('.', $parts);
        $length = strlen($output);

        if ($length > 255) {
            return false;
        }

        return $output;
    }

    /**
     * Decode a Punycode domain name to its Unicode counterpart
     *
     * @param string $input Domain name in Punycode
     *
     * @return string Unicode domain name
     *
     * @since 1.0.4
     *
     * @copyright 2014 TrueServer B.V. (https://github.com/true/php-punycode)
     */
    public function idnToUtf8($input) {

        $input = strtolower($input);
        $parts = explode('.', $input);

        foreach ($parts as &$part) {
            $length = strlen($part);

            if ($length > 63 || $length < 1) {
                return false;
            }

            if (strpos($part, static::PUNYCODE_PREFIX) !== 0) {
                continue;
            }

            $part = substr($part, strlen(static::PUNYCODE_PREFIX));
            $part = static::decodePart($part);
        }

        $output = implode('.', $parts);
        $length = strlen($output);

        if ($length > 255) {
            return false;
        }

        return $output;
    }

    /**
     * Encode a part of a domain name, such as tld, to its Punycode version
     *
     * @param string $input Part of a domain name
     *
     * @return string Punycode representation of a domain part
     *
     * @since 1.0.4
     *
     * @copyright 2014 TrueServer B.V. (https://github.com/true/php-punycode)
     */
    protected static function encodePart($input) {

        $codePoints = static::listCodePoints($input);
        $n = static::PUNYCODE_INITIAL_N;
        $bias = static::PUNYCODE_INITIAL_BIAS;
        $delta = 0;
        $h = $b = count($codePoints['basic']);
        $output = '';

        foreach ($codePoints['basic'] as $code) {
            $output .= static::codePointToChar($code);
        }

        if ($input === $output) {
            return $output;
        }

        if ($b > 0) {
            $output .= static::PUNYCODE_DELIMITER;
        }

        $codePoints['nonBasic'] = array_unique($codePoints['nonBasic']);
        sort($codePoints['nonBasic']);
        $i = 0;
        $length = static::strlen($input);

        while ($h < $length) {
            $m = $codePoints['nonBasic'][$i++];
            $delta = $delta + ($m - $n) * ($h + 1);
            $n = $m;

            foreach ($codePoints['all'] as $c) {

                if ($c < $n || $c < static::PUNYCODE_INITIAL_N) {
                    $delta++;
                }

                if ($c === $n) {
                    $q = $delta;

                    for ($k = static::PUNYCODE_BASE;; $k += static::PUNYCODE_BASE) {
                        $t = static::calculateThreshold($k, $bias);

                        if ($q < $t) {
                            break;
                        }

                        $code = $t + (($q - $t) % (static::PUNYCODE_BASE - $t));
                        $output .= static::$encodeTable[$code];
                        $q = ($q - $t) / (static::PUNYCODE_BASE - $t);
                    }

                    $output .= static::$encodeTable[$q];
                    $bias = static::adapt($delta, $h + 1, ($h === $b));
                    $delta = 0;
                    $h++;
                }

            }

            $delta++;
            $n++;
        }

        $out = static::PUNYCODE_PREFIX . $output;
        $length = strlen($out);

        if ($length > 63 || $length < 1) {
            return false;
        }

        return $out;
    }

    /**
     * Decode a part of domain name, such as tld
     *
     * @param string $input Part of a domain name
     *
     * @return string Unicode domain part
     *
     * @since 1.0.4
     *
     * @copyright 2014 TrueServer B.V. (https://github.com/true/php-punycode)
     */
    protected static function decodePart($input) {

        $n = static::PUNYCODE_INITIAL_N;
        $i = 0;
        $bias = static::PUNYCODE_INITIAL_BIAS;
        $output = '';
        $pos = strrpos($input, static::PUNYCODE_DELIMITER);

        if ($pos !== false) {
            $output = substr($input, 0, $pos++);
        } else {
            $pos = 0;
        }

        $outputLength = strlen($output);
        $inputLength = strlen($input);

        while ($pos < $inputLength) {
            $oldi = $i;
            $w = 1;

            for ($k = static::PUNYCODE_BASE;; $k += static::PUNYCODE_BASE) {
                $digit = static::$decodeTable[$input[$pos++]];
                $i = $i + ($digit * $w);
                $t = static::calculateThreshold($k, $bias);

                if ($digit < $t) {
                    break;
                }

                $w = $w * (static::PUNYCODE_BASE - $t);
            }

            $bias = static::adapt($i - $oldi, ++$outputLength, ($oldi === 0));
            $n = $n + (int) ($i / $outputLength);
            $i = $i % ($outputLength);
            $output = static::substr($output, 0, $i) . static::codePointToChar($n) . static::substr($output, $i, $outputLength - 1);
            $i++;
        }

        return $output;
    }

    /**
     * Calculate the bias threshold to fall between TMIN and TMAX
     *
     * @param integer $k
     * @param integer $bias
     *
     * @return integer
     *
     * @since 1.0.4
     *
     * @copyright 2014 TrueServer B.V. (https://github.com/true/php-punycode)
     */
    protected static function calculateThreshold($k, $bias) {

        if ($k <= $bias+static::PUNYCODE_TMIN) {
            return static::PUNYCODE_TMIN;
        } else

        if ($k >= $bias+static::PUNYCODE_TMAX) {
            return static::PUNYCODE_TMAX;
        }

        return $k - $bias;
    }

    /**
     * Bias adaptation
     *
     * @param integer $delta
     * @param integer $numPoints
     * @param boolean $firstTime
     *
     * @return integer
     *
     * @since 1.0.4
     *
     * @copyright 2014 TrueServer B.V. (https://github.com/true/php-punycode)
     */
    protected static function adapt($delta, $numPoints, $firstTime) {

        $delta = (int) (
            ($firstTime)
            ? $delta / static::PUNYCODE_DAMP
            : $delta / 2
        );
        $delta += (int) ($delta / $numPoints);
        $k = 0;

        while ($delta > ((static::PUNYCODE_BASE-static::PUNYCODE_TMIN) * static::PUNYCODE_TMAX) / 2) {
            $delta = (int) ($delta / (static::PUNYCODE_BASE-static::PUNYCODE_TMIN));
            $k = $k+static::PUNYCODE_BASE;
        }

        $k = $k + (int) (((static::PUNYCODE_BASE-static::PUNYCODE_TMIN + 1) * $delta) / ($delta+static::PUNYCODE_SKEW));

        return $k;
    }

    /**
     * List code points for a given input
     *
     * @param string $input
     *
     * @return array Multi-dimension array with basic, non-basic and aggregated code points
     *
     * @since 1.0.4
     *
     * @copyright 2014 TrueServer B.V. (https://github.com/true/php-punycode)
     */
    protected static function listCodePoints($input) {

        $codePoints = [
            'all'      => [],
            'basic'    => [],
            'nonBasic' => [],
        ];
        $length = static::strlen($input);

        for ($i = 0; $i < $length; $i++) {
            $char = static::substr($input, $i, 1);
            $code = static::charToCodePoint($char);

            if ($code < 128) {
                $codePoints['all'][] = $codePoints['basic'][] = $code;
            } else {
                $codePoints['all'][] = $codePoints['nonBasic'][] = $code;
            }

        }

        return $codePoints;
    }

    /**
     * Convert a single or multi-byte character to its code point
     *
     * @param string $char
     * @return integer
     *
     * @since 1.0.4
     *
     * @copyright 2014 TrueServer B.V. (https://github.com/true/php-punycode)
     */
    protected static function charToCodePoint($char) {

        $code = ord($char[0]);

        if ($code < 128) {
            return $code;
        } else

        if ($code < 224) {
            return (($code - 192) * 64) + (ord($char[1]) - 128);
        } else

        if ($code < 240) {
            return (($code - 224) * 4096) + ((ord($char[1]) - 128) * 64) + (ord($char[2]) - 128);
        } else {
            return (($code - 240) * 262144) + ((ord($char[1]) - 128) * 4096) + ((ord($char[2]) - 128) * 64) + (ord($char[3]) - 128);
        }

    }

    /**
     * Convert a code point to its single or multi-byte character
     *
     * @param integer $code
     * @return string
     *
     * @since 1.0.4
     *
     * @copyright 2014 TrueServer B.V. (https://github.com/true/php-punycode)
     *
     */
    protected static function codePointToChar($code) {

        if ($code <= 0x7F) {
            return chr($code);
        } else

        if ($code <= 0x7FF) {
            return chr(($code >> 6) + 192) . chr(($code & 63) + 128);
        } else

        if ($code <= 0xFFFF) {
            return chr(($code >> 12) + 224) . chr((($code >> 6) & 63) + 128) . chr(($code & 63) + 128);
        } else {
            return chr(($code >> 18) + 240) . chr((($code >> 12) & 63) + 128) . chr((($code >> 6) & 63) + 128) . chr(($code & 63) + 128);
        }

    }

    /**
     * Base 64 encode that does not require additional URL Encoding for i.e. cookies
     *
     * This greatly reduces the size of a cookie
     *
     * @param mixed $data
     *
     * @return string
     *
     * @since 1.0.4
     */
    public function base64UrlEncode($data) {

        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Base 64 decode for base64UrlEncoded data
     *
     * @param mixed $data
     *
     * @return string
     *
     * @since 1.0.4
     */
    public function base64UrlDecode($data) {

        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }

    /**
     * Grabs a size tag from a DOMElement (as HTML)
     *
     * @param string $html
     *
     * @return array|false
     *
     * @since 1.0.4
     */
    public function parseFaviconSizeTag($html) {

        $srcFound = false;
        $favicon = [];
        preg_match('/\{(.*)\}/U', $html, $m);

        if (!$m || count($m) < 2) {
            return false;
        }

        $tags = explode(' ', $m[1]);

        foreach ($tags as $tag) {
            $components = explode('=', $tag);

            if (count($components) === 1) {

                if ($components[0] === 'src') {
                    $srcFound = true;
                }

                continue;
            }

            switch ($components[0]) {
            case 'type':
                $favicon['type'] = $components[1];
                break;
            case 'size':
                $dimension = explode('x', $components[1]);

                if (count($dimension) !== 2) {
                    return false;
                }

                $favicon['width'] = $dimension[0];
                $favicon['height'] = $dimension[1];
                break;
            }

        }

        if ($srcFound && array_key_exists('width', $favicon) && array_key_exists('height', $favicon)) {

            if (!isset($favicon['type'])) {
                $favicon['type'] = 'png';
            }

            return $favicon;
        }

        return false;
    }

    /**
     * Returns current server timezone setting.
     *
     * @return string
     *
     * @since   1.0.7
     * @version 1.0.7 Initial version.
     */
    public function getTimeZone() {

        $timezone = $this->context->phenyxConfig->get('EPH_TIMEZONE');

        if (!$timezone) {
            // Fallback use php timezone settings.
            $timezone = date_default_timezone_get();
        }

        return $timezone;
    }

    public function isWebPSupported() {

        if ($this->context->phenyxConfig->get('plugin-webpconverter-demo-mode')) {
            return false;
        }

        if (Plugin::isEnabled('webpgenerator')) {

            if (isset($_SERVER["HTTP_ACCEPT"])) {

                if (strpos($_SERVER["HTTP_ACCEPT"], "image/webp") > 0) {
                    return true;
                }

                $agent = $_SERVER['HTTP_USER_AGENT'];

                if (strlen(strstr($agent, 'Firefox')) > 0) {
                    return true;
                }

                if (strlen(strstr($agent, 'Edge')) > 0) {
                    return true;
                }

            }

        }

    }

    public function isImagickCompatible() {

        try {

            if (!class_exists('Imagick')) {
                return false;
            }

            /**
             * Check if the Imagick::queryFormats method exists
             */

            if (!method_exists(\Imagick::class, 'queryFormats')) {
                return false;
            }

            return in_array('WEBP', \Imagick::queryFormats(), false);
        } catch (Exception $exception) {
            return false;
        }

    }

    public function is_assoc(array $array) {

        $keys = array_keys($array);

        return array_keys($keys) !== $keys;
    }

    public function generateStrongPassword($length = 9, $add_dashes = false, $available_sets = 'luds') {

        $sets = [];

        if (strpos($available_sets, 'l') !== false) {
            $sets[] = 'abcdefghjkmnpqrstuvwxyz';
        }

        if (strpos($available_sets, 'u') !== false) {
            $sets[] = 'ABCDEFGHJKMNPQRSTUVWXYZ';
        }

        if (strpos($available_sets, 'd') !== false) {
            $sets[] = '23456789';
        }

        if (strpos($available_sets, 's') !== false) {
            $sets[] = '-@_.()';
        }

        $all = '';
        $password = '';

        foreach ($sets as $set) {
            $password .= $set[array_rand(str_split($set))];
            $all .= $set;
        }

        $all = str_split($all);

        for ($i = 0; $i < $length - count($sets); $i++) {
            $password .= $all[array_rand($all)];
        }

        $password = str_shuffle($password);

        if (!$add_dashes) {
            return $password;
        }

        $dash_len = floor(sqrt($length));
        $dash_str = '';

        while (strlen($password) > $dash_len) {
            $dash_str .= substr($password, 0, $dash_len) . '-';
            $password = substr($password, $dash_len);
        }

        $dash_str .= $password;
        return $dash_str;
    }

    public function sendEmail($postfields, $meta_description = null) {

        $phenyxConfig = Configuration::getInstance();
        $mail_allowed = $phenyxConfig->get('EPH_ALLOW_SEND_EMAIL') ? 1 : 0;

        if ($mail_allowed) {

            if (!isset($this->context->phenyxConfig)) {
                $this->context->phenyxConfig = $phenyxConfig;

            }

            if (!isset($this->context->company)) {
                $this->context->company = Company::initialize();

            }

            if (!isset($this->context->theme)) {
                $this->context->theme = new Theme((int) $this->context->company->id_theme);
            }

            $htmlContent = $postfields['htmlContent'];
            $url = 'https://' . $this->context->company->domain_ssl;
            $tpl = $this->context->smarty->createTemplate(_EPH_MAIL_DIR_ . 'header.tpl');
            $bckImg = !empty($phenyxConfig->get('EPH_BCK_LOGO_MAIL')) ? $url . '/content/img/' . $phenyxConfig->get('EPH_BCK_LOGO_MAIL') : false;
            $tpl->assign([
                'title'        => $postfields['subject'],
                'css_dir'      => 'https://' . $this->context->company->domain_ssl . $this->context->theme->css_theme,
                'shop_link'    => $this->context->_link->getBaseFrontLink(),
                'shop_name'    => $this->context->company->company_name,
                'bckImg'       => $bckImg,
                'logoMailLink' => $url . '/content/img/' . $phenyxConfig->get('EPH_LOGO_MAIL'),
            ]);

            if (!is_null($meta_description)) {
                $tpl->assign([
                    'meta_description' => $meta_description,
                ]);
            }

            $header = $tpl->fetch();
            $tpl = $this->context->smarty->createTemplate(_EPH_MAIL_DIR_ . 'footer.tpl');
            $tpl->assign([
                'tag' => $phenyxConfig->get('EPH_FOOTER_EMAIL'),
            ]);
            $footer = $tpl->fetch();
            $postfields['htmlContent'] = $header . $htmlContent . $footer;
            $mail_method = $phenyxConfig->get('EPH_MAIL_METHOD');

            if ($mail_method == 1) {
                $encrypt = $phenyxConfig->get('EPH_MAIL_SMTP_ENCRYPTION');
                $mail = new PHPMailer();
                $mail->IsSMTP();
                $mail->SMTPAuth = true;
                $mail->Host = $phenyxConfig->get('EPH_MAIL_SERVER');
                $mail->Port = $phenyxConfig->get('EPH_MAIL_SMTP_PORT');
                //$mail->SMTPDebug = SMTP::DEBUG_CONNECTION;
                $mail->Username = $phenyxConfig->get('EPH_MAIL_USER');
                $mail->Password = $phenyxConfig->get('EPH_MAIL_PASSWD');
                $mail->setFrom($postfields['sender']['email'], $postfields['sender']['name']);

                foreach ($postfields['to'] as $key => $value) {
                    $mail->addAddress($value['email'], $value['name']);
                }

                $mail->Subject = $postfields['subject'];

                if ($encrypt != 'off') {

                    if ($encrypt == 'ENCRYPTION_STARTTLS') {
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    } else {
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                    }

                }

                $mail->Body = $postfields['htmlContent'];
                $mail->isHTML(true);

                if (isset($postfields['attachment']) && !is_null($postfields['attachment'])) {
                    $mail->addAttachment($postfields['attachment']);
                }

                if (!$mail->send()) {
                    return false;
                } else {
                    return true;
                }

            } else

            if ($mail_method == 2) {
                $api_key = $phenyxConfig->get('EPH_SENDINBLUE_API');

                $curl = curl_init();

                curl_setopt_array($curl, [
                    CURLOPT_URL            => "https://api.brevo.com/v3/smtp/email",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING       => "",
                    CURLOPT_MAXREDIRS      => 10,
                    CURLOPT_TIMEOUT        => 30,
                    CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST  => "POST",
                    CURLOPT_POSTFIELDS     => json_encode($postfields),
                    CURLOPT_HTTPHEADER     => [
                        "accept: application/json",
                        "api-key: " . $api_key,
                        "content-type: application/json",
                    ],
                ]);

                $response = curl_exec($curl);
                $err = curl_error($curl);
                curl_close($curl);

                if ($err) {
                    return false;
                } else {
                    return true;
                }

            }

        } else {
            return true;
        }

    }

    public function hex2rgb($colour) {

        if ($colour[0] == '#') {
            $colour = substr($colour, 1);
        }

        if (strlen($colour) == 6) {
            list($r, $g, $b) = [$colour[0] . $colour[1], $colour[2] . $colour[3], $colour[4] . $colour[5]];
        } else

        if (strlen($colour) == 3) {
            list($r, $g, $b) = [$colour[0] . $colour[0], $colour[1] . $colour[1], $colour[2] . $colour[2]];
        } else {
            return false;
        }

        $r = hexdec($r);
        $g = hexdec($g);
        $b = hexdec($b);
        return ['red' => $r, 'green' => $g, 'blue' => $b];
    }

    public function convertTime($dec) {

        $seconds = ($dec * 3600);
        $hours = floor($dec);
        $seconds -= $hours * 3600;
        $minutes = floor($seconds / 60);
        $seconds -= $minutes * 60;
        return $this->lz($hours) . ":" . $this->lz($minutes) . ":" . (int) $this->lz($seconds);
    }

    public function convertTimetoHex($hours, $minutes) {

        return $hours + round($minutes / 60, 2);
    }

    public function lz($num) {

        return (strlen($num) < 2) ? "0{$num}" : $num;
    }

    public function convertFrenchDate($date) {

        $date = DateTime::createFromFormat('d/m/Y', $date);
        return date_format($date, "Y-m-d");
    }

    public function encrypt_decrypt($action, $string, $secret_key, $secret_iv) {

        $output = false;
        $encrypt_method = "AES-256-CBC";
        $key = hash('sha256', $secret_key);
        $iv = substr(hash('sha256', $secret_iv), 0, 16);

        if ($action == 'encrypt') {
            $output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
            $output = base64_encode($output);
        } else

        if ($action == 'decrypt') {
            $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
        }

        return $output;
    }

    public function skip_accents($str, $charset = 'utf-8') {

        $str = htmlentities($str, ENT_NOQUOTES, $charset);

        $str = preg_replace('#&([A-za-z])(?:acute|cedil|caron|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str);
        $str = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $str);
        $str = preg_replace('#&[^;]+;#', '', $str);

        return $str;
    }

    public function random_float($min, $max) {

        return random_int($min, $max - 1) + (random_int(0, PHP_INT_MAX - 1) / PHP_INT_MAX);
    }

    public function getMonthById($idMonth) {

        switch ($idMonth) {
        case '01':
            $month = 'Janvier';
            break;
        case '02':
            $month = 'Fevrier';
            break;
        case '03':
            $month = 'Mars';
            break;
        case '04':
            $month = 'Avril';
            break;
        case '05':
            $month = 'Mai';
            break;
        case '06':
            $month = 'Juin';
            break;
        case '07':
            $month = 'Juillet';
            break;
        case '08':
            $month = 'Aout';
            break;
        case '09':
            $month = 'Septembre';
            break;
        case '10':
            $month = 'Octobre';
            break;
        case '11':
            $month = 'Novembre';
            break;
        case '12':
            $month = 'Décembre';
            break;

        }

        return $month;
    }

    public function str_rsplit($string, $length) {

        // splits a string "starting" at the end, so any left over (small chunk) is at the beginning of the array.

        if (!$length) {return false;}

        if ($length > 0) {return str_split($string, $length);}

        // normal split

        $l = strlen($string);
        $length = min(-$length, $l);
        $mod = $l % $length;

        if (!$mod) {return str_split($string, $length);}

        // even/max-length split

        // split
        return array_merge([substr($string, 0, $mod)], str_split(substr($string, $mod), $length));
    }

    public function getContentLinkTitle($url) {

        $html = $this->file_get_contents_curl($url);

        $image_url = [];

        $doc = new DOMDocument();
        @$doc->loadHTML($html);

        $metas = $doc->getElementsByTagName('meta');

        for ($i = 0; $i < $metas->length; $i++) {
            $meta = $metas->item($i);

            if ($meta->getAttribute('property') == 'og:title') {
                $title = $meta->getAttribute('content');
            }

        }

        if (empty($title)) {
            $nodes = $doc->getElementsByTagName('title');
            $title = $nodes->item(0)->nodeValue;
        }

        return $title;

    }

    public function getContentLink($url) {

        $html = $this->file_get_contents_curl($url);

        $image_url = [];

        $doc = new DOMDocument();
        @$doc->loadHTML($html);

        $metas = $doc->getElementsByTagName('meta');

        for ($i = 0; $i < $metas->length; $i++) {
            $meta = $metas->item($i);

            if ($meta->getAttribute('property') == 'og:title') {
                $title = $meta->getAttribute('content');
            }

            if ($meta->getAttribute('property') == 'og:image') {
                $image_url[0] = $meta->getAttribute('content');
            }

            if ($meta->getAttribute('name') == 'description') {
                $body_content = $meta->getAttribute('content');
            }

        }

        if (empty($title)) {
            $nodes = $doc->getElementsByTagName('title');
            $title = $nodes->item(0)->nodeValue;
        }

        if (empty($image_url[0])) {

            $content = $this->file_get_html($url);

            foreach ($content->find('img') as $element) {

                if (filter_var($element->src, FILTER_VALIDATE_URL)) {
                    list($width, $height) = getimagesize($element->src);

                    if ($width > 150 || $height > 150) {
                        $image_url[0] = $element->src;
                        break;
                    }

                }

            }

        }

        $image_div = "";

        if (!empty($image_url[0])) {
            $image_div = "<div class='image-extract col-lg-12'>" .
                "<input type='hidden' id='index' value='0'/>" .
                "<img id='image_url' src='" . $image_url[0] . "' />";

            if (count($image_url) > 1) {
                $image_div .= "<div>" .
                "<input type='button' class='btnNav' id='prev-extract' onClick=navigateImage(" . json_encode($image_url) . ",'prev') disabled />" .
                "<input type='button' class='btnNav' id='next-extract' target='_blank' onClick=navigateImage(" . json_encode($image_url) . ",'next') />" .
                    "</div>";
            }

            $image_div .= "</div>";
        }

        $output = $image_div . "<div class='content-extract col-lg-12'>" .
            "<h3><a href='" . $url . "' target='_blank'>" . $title . "</a></h3>" .
            "<div>" . $body_content . "</div>" .
            "</div>";

        return $output;

    }

    public function deleteBulkFiles($file) {

        if (file_exists(_EPH_ROOT_DIR_ . $file)) {
            unlink(_EPH_ROOT_DIR_ . $file);
        }

        return true;

    }

    public function removeEmptyDirs($path) {

        $dirs = glob($path . "*", GLOB_ONLYDIR);

        foreach ($dirs as $dir) {
            $files = glob($dir . "/*");
            $innerDirs = glob($dir . "/*", GLOB_ONLYDIR);

            if (is_array($files) && count($files) == 1 && basename($files[0]) == 'index.php') {

                unlink($files[0]);
                rmdir($dir);

            } else

            if (empty($files)) {
                rmdir($dir);
            } else

            if (is_array($innerDirs) && count($innerDirs) > 0) {
                $this->removeEmptyDirs($dir . '/');
            }

        }

    }

    public function buildMaps() {

        $this->context = $this->context;

        $map_seeting = [];

        $seetings = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('cml.name, c.*, ccl.`name` as `category`, cml.`description`')
                ->from('composer_map', 'c')
                ->leftJoin('composer_map_lang', 'cml', 'cml.`id_composer_map` = c.`id_composer_map` AND cml.`id_lang` = ' . $this->context->language->id)
                ->leftJoin('composer_category_lang', 'ccl', 'ccl.`id_composer_category` = c.`id_composer_category` AND ccl.`id_lang` = ' . $this->context->language->id)
                ->where('c.`is_corporate` = 1')
        );
        $excludeField = ['show_settings_on_create', 'content_element', 'is_container'];

        foreach ($seetings as &$seeting) {

            foreach ($seeting as $key => $value) {

                if ($key == 'show_settings_on_create') {

                    if ($value == 2) {
                        $seeting['show_settings_on_create'] = false;
                    } else

                    if ($value == 1) {
                        $seeting['show_settings_on_create'] = true;
                    } else

                    if (empty($value)) {
                        unset($seeting['show_settings_on_create']);
                    }

                }

                if ($key == 'content_element') {

                    if ($value == 0) {
                        $seeting['content_element'] = false;
                    } else

                    if ($value == 1) {
                        unset($seeting['content_element']);
                    }

                }

                if ($key == 'is_container') {

                    if ($value == 1) {
                        $seeting['is_container'] = true;
                    } else {
                        unset($seeting['is_container']);
                    }

                }

            }

        }

        foreach ($seetings as &$seeting) {

            foreach ($seeting as $key => $value) {

                if (in_array($key, $excludeField)) {
                    continue;
                }

                if (empty($value)) {
                    unset($seeting[$key]);

                }

            }

            unset($seeting['id_composer_category']);
            unset($seeting['active']);

        }

        foreach ($seetings as &$seeting) {

            $params = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
                (new DbQuery())
                    ->select('cpt.`value`as `type`, cmpl.heading, cmp.*, cmpl.description, cmpl.param_group as `group`')
                    ->from('composer_map_params', 'cmp')
                    ->leftJoin('composer_map_params_lang', 'cmpl', 'cmpl.`id_composer_map_params` = cmp.`id_composer_map_params` AND cmpl.`id_lang` = ' . $this->context->language->id)
                    ->leftJoin('composer_param_type', 'cpt', 'cpt.`id_composer_param_type` = cmp.`id_type`')
                    ->where('cmp.`id_composer_map` = ' . $seeting['id_composer_map'])
            );

            foreach ($params as &$param) {

                unset($param['id_type']);

                foreach ($param as $key => $value) {

                    if (empty($value)) {
                        unset($param[$key]);
                    }

                }

                if (!empty($param['value']) && $param['param_name'] != 'content') {
                    $param['value'] = $this->jsonDecode($param['value'], true);
                }

                if ($param['param_name'] == 'img_size') {
                    $param['values'] = $this->getComposerImageTypes();
                } else {
                    $values = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
                        (new DbQuery())
                            ->select('cv.`value_key`, cvl.`name`')
                            ->from('composer_value', 'cv')
                            ->leftJoin('composer_value_lang', 'cvl', 'cvl.`id_composer_value` = cv.`id_composer_value` AND cvl.`id_lang` = ' . $this->context->language->id)
                            ->where('cv.`id_composer_map_params` = ' . $param['id_composer_map_params'])
                    );
                    $param['values'] = $values;

                }

            }

            if (!empty($params)) {

                foreach ($params as &$param) {

                    if (!empty($param['dependency'])) {
                        $param['dependency'] = $this->jsonDecode($param['dependency'], true);
                    }

                    if (!empty($param['settings'])) {
                        $param['settings'] = $this->jsonDecode($param['settings'], true);
                    }

                    unset($param['id_composer_map']);
                    unset($param['id_composer_map_params']);
                    unset($param['position']);

                }

            }

            unset($seeting['id_composer_map']);
            unset($seeting['id_lang']);
            $seeting['params'] = $params;
            $map_seeting[$seeting['base']] = $seeting;
        }

        $this->context->phenyxConfig->updateValue('_EPH_SEETINGS_MAP_FILE_', $this->jsonEncode($map_seeting));
        return $map_seeting;

    }

    public function getComposerImageTypes() {

        $images_types = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('*')
                ->from('vc_image_type')
                ->orderBy('`name` ASC')
        );
        $values = [];
        $values[] = [
            'value_key' => '',
            'name'      => '',
        ];

        foreach ($images_types as $type) {
            $values[] = [
                'value_key' => $type['name'],
                'name'      => $type['name'],
            ];

        }

        return $values;
    }

    public function fieldAttachedImages($att_ids = [], $imageSize = null) {

        $links = [];

        foreach ($att_ids as $th_id) {

            $result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getRow(
                (new DbQuery())
                    ->select('*')
                    ->from('vc_media')
                    ->where('`id_vc_media` = ' . (int) $th_id)
            );

            if (isset($result['base_64']) && !empty($result['base_64'])) {
                $links[$th_id] = $result['base_64'];

            } else

            if (isset($result['file_name']) && !empty($result['file_name'])) {
                $thumb_src = __EPH_BASE_URI__ . 'content/img/composer/';

                if (!empty($result['subdir'])) {
                    $thumb_src .= $result['subdir'];
                }

                $thumb_src .= $result['file_name'];

                if (!empty($imageSize)) {
                    $path_parts = pathinfo($thumb_src);
                    $thumb_src = $path_parts['dirname'] . DIRECTORY_SEPARATOR . $path_parts['filename'] . '-' . $imageSize . '.' . $path_parts['extension'];

                }

                if (empty($result['base_64'])) {
                    $extension = pathinfo($thumb_src, PATHINFO_EXTENSION);
                    $img = new Imagick(_EPH_ROOT_DIR_ . $thumb_src);
                    $imgBuff = $img->getimageblob();
                    $img->clear();
                    $img = base64_encode($imgBuff);
                    $base64 = 'data:image/' . $extension . ';base64,' . $img;
                    $imageType = new ComposerMedia($result['id_vc_media']);
                    $imageType->file_name = $result['file_name'];
                    $imageType->base_64 = $base64;
                    $imageType->subdir = $result['subdir'];

                    foreach (Language::getIDs(false) as $idLang) {
                        $imageType->legend[$idLang] = pathinfo($thumb_src, PATHINFO_FILENAME);
                    }

                    if ($imageType->update()) {
                        $thumb_src = $base64;
                    }

                }

                $links[$th_id] = $thumb_src;
            }

        }

        return $links;
    }

    public function getSliderWidth($size) {

        $width = '100%';
        $types = $this->getImageTypeByName($size);

        if (isset($types)) {
            $width = $types['width'] . 'px';
        }

        return $width;
    }

    public function getImageTypeByName($name) {

        $result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getRow(
            (new DbQuery())
                ->select('*')
                ->from('vc_image_type')
                ->where('`name` LIKE  \'' . $name . '\'')
        );

        if (!empty($result)) {
            $image['width'] = $result['width'];
            $image['height'] = $result['height'];

            return $image;
        }

        return false;
    }

    public function get_media_thumbnail_url($id = '') {

        if (isset($id) && !empty($id)) {
            $db = Db::getInstance();
            $tablename = _DB_PREFIX_ . 'vc_media';
            $sql = new DbQuery();
            $sql->select('`file_name`, `subdir`');
            $sql->from(('vc_media'));
            $sql->where('id_vc_media = ' . (int) $id);
            $db_results = $db->executeS($sql);

            $url = isset($db_results[0]['subdir']) && !empty($db_results[0]['subdir']) ? $db_results[0]['subdir'] . '/' : '';
            return $url .= isset($db_results[0]['file_name']) ? $db_results[0]['file_name'] : '';
        } else {
            return '';
        }

    }

    public function ModifyImageUrl($img_src = '') {

        $img_pathinfo = pathinfo($img_src);
        $mainstr = $img_pathinfo['basename'];
        $static_url = $img_pathinfo['dirname'] . '/' . $mainstr;
        return '//' . $this->getMediaServer($static_url) . $static_url;
    }

    public function getDistantTables($currentTables) {

        $tableToKeep = [];
        $tableToCheck = [];

        foreach ($currentTables as $table) {
            $tableToKeep[] = $table['Tables_in_' . _DB_NAME_];
        }

        $distantTables = Db::getInstance()->executeS('SHOW TABLES');

        foreach ($distantTables as $table) {
            $tableToCheck[] = $table['Tables_in_' . $dbName];
        }

        $tableToDelete = [];

        foreach ($tableToCheck as $table) {

            if (in_array($table, $tableToKeep)) {
                continue;
            }

            $schema = Db::getInstance()->executeS('SHOW CREATE TABLE `' . $table . '`');

            if (count($schema) != 1 || !isset($schema[0]['Table']) || !isset($schema[0]['Create Table'])) {
                continue;
            }

            $tableToDelete[$table] = 'DROP TABLE IF EXISTS `' . $schema[0]['Table'] . '`;' . PHP_EOL;
        }

        return $tableToDelete;

    }

    public function singleFontsUrl() {

        $url = '//fonts.googleapis.com/css?family=';
        $main_str = '';
        $subsets_str = '';
        $subsets = [];
        $all_fonts = [];
        $font_types = ['bodyfont', 'headingfont', 'additionalfont'];

        if (isset($font_types) && !empty($font_types)) {

            foreach ($font_types as $font_type) {
                $famil = $this->context->phenyxConfig->get($font_type . '_family');
                $all_fonts[$famil]['fonts'] = $famil;
                $all_fonts[$famil]['variants'] = $this->context->phenyxConfig->get($font_type . '_variants');
                $subset = $this->context->phenyxConfig->get($font_type . '_subsets');

                if (isset($subset) && !empty($subset)) {
                    $subsetarr = @explode(",", $subset);

                    if (isset($subsetarr) && !empty($subsetarr) && is_array($subsetarr)) {

                        foreach ($subsetarr as $arr) {
                            $subsets[$arr] = $arr;
                        }

                    }

                }

            }

        }

        $main = [];

        if (isset($all_fonts) && !empty($all_fonts)) {

            foreach ($all_fonts as $all_font) {
                $main[] = $all_font['fonts'] . ':' . $all_font['variants'];
            }

        }

        if (isset($subsets) && !empty($subsets) && is_array($subsets)) {
            $subsets_str = implode(",", $subsets);
        }

        if (isset($main) && !empty($main) && is_array($main)) {
            $main_str = implode("|", $main);
        }

        if (isset($main_str) && !empty($main_str)) {
            $url .= $main_str;
        }

        if (isset($subsets_str) && !empty($subsets_str)) {
            $url .= '&subset=' . $subsets_str;
        }

        if (isset($main_str) && !empty($main_str)) {
            return $url;
        } else {
            return false;
        }

    }

    public function getPhenyxFontName($key) {

        $name = str_replace(' ', '', $this->context->phenyxConfig->get($key . '_family'));
        return $name . '_' . $this->context->phenyxConfig->get($key . '_variants');
    }

    public function GetPhenyxFontsURL($key = "", $var = [], $sub = [], $family = '') {

        if ($key == "") {
            return false;
        }

        if ($this->usingSecureMode()) {
            $link = 'https://ephenyx.io/css?family=';
        } else {
            $link = 'https://ephenyx.io/css?family=';
        }

        if (empty($family)) {
            $family = $this->context->phenyxConfig->get($key . '_family');
            $variants = $this->context->phenyxConfig->get($key . '_variants');
            $subsets = $this->context->phenyxConfig->get($key . '_subsets');

            if (isset($family) && !empty($family)) {
                $family = str_replace(" ", "+", $family);
                $link .= $family;

                if (isset($variants) && !empty($variants)) {
                    $link .= ':' . $variants;

                    if (isset($subsets) && !empty($subsets)) {
                        $link .= '&subset=' . $subsets;
                    }

                }

                return $link;
            }

        } else {
            $family = str_replace(" ", "+", $family);
            $link .= $family;

            if (is_array($var) && count($var)) {

                foreach ($var as $key => $value) {
                    $link .= ':' . $value;
                }

                if (is_array($sub) && count($sub)) {

                    foreach ($sub as $key => $value) {
                        $link .= '&subset=' . $value;
                    }

                }

            }

            return $link;
        }

    }

    public function GetAdminPhenyxFontsURL($key = "", $var = '', $sub = '') {

        if ($key == "") {
            return false;
        }

        if ($this->usingSecureMode()) {
            $link = 'https://ephenyxapi.com/css?family=';
        } else {
            $link = 'https://ephenyxapi.com/css?family=';
        }

        $family = $this->context->phenyxConfig->get($key . '_family');
        $variants = $this->context->phenyxConfig->get($key . '_variants');
        $subsets = $this->context->phenyxConfig->get($key . '_subsets');

        if (isset($family) && !empty($family)) {
            $family = str_replace(" ", "+", $family);
            $link .= $family;

            if (isset($variants) && !empty($variants)) {
                $link .= ':' . $variants;

                if (isset($subsets) && !empty($subsets)) {
                    $link .= '&subset=' . $subsets;
                }

            }

            return $link;
        } else {
            $family = str_replace(" ", "+", $key);
            $link .= $family;

            if (isset($var) && !empty($var)) {
                $link .= ':' . $variants;

                if (isset($sub) && !empty($sub)) {
                    $link .= '&subset=' . $subsets;
                }

            }

            return $link;
        }

    }

    public function cleanPluginDataBase() {

        $plugins = Db::getInstance()->executeS(
            (new DbQuery())
                ->select('`id_plugin`, name')
                ->from('plugin')
        );

        foreach ($plugins as $plugin) {

            if (!file_exists(_EPH_PLUGIN_DIR_ . $plugin['name'] . '/' . $plugin['name'] . '.php')) {

                $sql = 'DELETE FROM ' . _DB_PREFIX_ . 'hook_plugin WHERE id_plugin = ' . $plugin['id_plugin'];
                Db::getInstance()->execute($sql);
                $sql = 'DELETE FROM ' . _DB_PREFIX_ . 'hook_plugin_exceptions WHERE id_plugin = ' . $plugin['id_plugin'];
                Db::getInstance()->execute($sql);
                $sql = 'DELETE FROM ' . _DB_PREFIX_ . 'plugin WHERE id_plugin = ' . $plugin['id_plugin'];
                Db::getInstance()->execute($sql);
                $sql = 'DELETE FROM ' . _DB_PREFIX_ . 'plugin_perfs WHERE plugin LIKE \'' . $plugin['name'] . '\'';
                Db::getInstance()->execute($sql);
                $sql = 'DELETE FROM ' . _DB_PREFIX_ . 'plugin_access WHERE id_plugin = ' . $plugin['id_plugin'];
                Db::getInstance()->execute($sql);
                $sql = 'DELETE FROM ' . _DB_PREFIX_ . 'plugin_country WHERE id_plugin = ' . $plugin['id_plugin'];
                Db::getInstance()->execute($sql);
                $sql = 'DELETE FROM ' . _DB_PREFIX_ . 'plugin_currency WHERE id_plugin = ' . $plugin['id_plugin'];
                Db::getInstance()->execute($sql);
                $sql = 'DELETE FROM ' . _DB_PREFIX_ . 'plugin_group WHERE id_plugin = ' . $plugin['id_plugin'];
                Db::getInstance()->execute($sql);
                $sql = 'DELETE FROM ' . _DB_PREFIX_ . 'plugin_preference WHERE plugin LIKE \'' . $plugin['name'] . '\'';

            }

        }

        $hooks = Hook::getInstance()->getPluginHook();

        foreach ($hooks as $hook) {
            $plugins = Db::getInstance()->executeS(
                (new DbQuery())
                    ->select('m.`id_plugin`, m.name')
                    ->from('plugin', 'm')
                    ->leftJoin('hook_plugin', 'hm', 'hm.`id_plugin` = m.`id_plugin`')
                    ->where('hm.`id_hook` = ' . $hook['id_hook'])
                    ->orderBy('m.`id_plugin` ASC')
            );

            foreach ($plugins as $plugin) {

                if (!file_exists(_EPH_PLUGIN_DIR_ . $plugin['name'] . '/' . $plugin['name'] . '.php')) {

                    $sql = 'DELETE FROM ' . _DB_PREFIX_ . 'hook_plugin WHERE id_plugin = ' . $plugin['id_plugin'];
                    Db::getInstance()->execute($sql);
                    $sql = 'DELETE FROM ' . _DB_PREFIX_ . 'hook_plugin_exceptions WHERE id_plugin = ' . $plugin['id_plugin'];
                    Db::getInstance()->execute($sql);
                    $sql = 'DELETE FROM ' . _DB_PREFIX_ . 'plugin WHERE id_plugin = ' . $plugin['id_plugin'];
                    Db::getInstance()->execute($sql);
                    $sql = 'DELETE FROM ' . _DB_PREFIX_ . 'plugin_access WHERE id_plugin = ' . $plugin['id_plugin'];
                    Db::getInstance()->execute($sql);
                    $sql = 'DELETE FROM ' . _DB_PREFIX_ . 'plugin_carrier WHERE id_plugin = ' . $plugin['id_plugin'];
                    Db::getInstance()->execute($sql);
                    $sql = 'DELETE FROM ' . _DB_PREFIX_ . 'plugin_country WHERE id_plugin = ' . $plugin['id_plugin'];
                    Db::getInstance()->execute($sql);
                    $sql = 'DELETE FROM ' . _DB_PREFIX_ . 'plugin_currency WHERE id_plugin = ' . $plugin['id_plugin'];
                    Db::getInstance()->execute($sql);
                    $sql = 'DELETE FROM ' . _DB_PREFIX_ . 'plugin_group WHERE id_plugin = ' . $plugin['id_plugin'];
                    Db::getInstance()->execute($sql);
                    $sql = 'DELETE FROM ' . _DB_PREFIX_ . 'plugin_preference WHERE plugin LIKE \'' . $plugin['name'] . '\'';

                }

            }

        }

    }

    public function buildIncrementSelect($objet, $idLang, $fieldName, $rootName, $idParent = null) {

        $classObject = get_class($objet);
        $classVars = get_class_vars($classObject);
        $primary = $classVars['definition']['primary'];
        $table = $classVars['definition']['table'];

        $root = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('`' . $primary . '`')
                ->from($table)
                ->where('id_parent = 0')
        );

        $object_array = [];

        $select = '';

        $select .= '<option value="' . $root . '">' . $rootName . '</option>';
        $result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('a.`' . $primary . '`,a.id_parent, b.`' . $fieldName . '`')
                ->from($table, 'a')
                ->leftJoin($table . '_lang', 'b', 'a.`' . $primary . '` = b.`' . $primary . '` AND b.`id_lang`  = ' . (int) $idLang)
                ->where('id_parent = ' . $root)
                ->orderBy('a.`position` ASC')
        );

        if (is_array($result)) {

            foreach ($result as &$row) {
                $row['children'] = $classObject::getChlidren($row[$primary]);
                $object_array[$row[$primary]] = $row;
            }

            foreach ($object_array as $key => $value) {
                $select .= '<option value="' . $value[$primary] . '" ';

                if ($value[$primary] == $idParent) {
                    $select .= 'selected="selected"';
                }

                $select .= '>' . $value[$fieldName] . '</option>';

                foreach ($value['children'] as $child) {
                    $select .= '<option value="' . $child[$primary] . '" ';

                    if ($child[$primary] == $idParent) {
                        $select .= 'selected="selected"';
                    }

                    $select .= '>' . $value[$fieldName] . ' > ' . $child[$fieldName] . '</option>';

                    if (is_array($child['children']) && count($child['children'])) {

                        foreach ($child['children'] as $key => $value) {

                            foreach ($value['children'] as $child) {
                                $select .= '<option value="' . $child[$primary] . '" ';

                                if ($child[$primary] == $idParent) {
                                    $select .= 'selected="selected"';
                                }

                                $select .= '>' . $value[$fieldName] . ' > ' . $child[$fieldName] . '</option>';
                            }

                        }

                    }

                }

            }

        }

        return $select;

    }

    public function get_media_alt($id = '') {

        if (isset($id) && !empty($id)) {
            $db = Db::getInstance();
            $id_lang = (int) $this->context->language->id;
            $sql = new DbQuery();
            $sql->select('`legend`');
            $sql->from('vc_media', 'vm');
            $sql->innerJoin('vc_media_lang', 'vml', '`vml`.`id_vc_media` = `vml`.`id_vc_media` AND `vml`.`id_lang` = ' . $id_lang);
            $sql->where('vm.id_vc_media = ' . (int) $id);
            $db_results = $db->getRow($sql);
            return isset($db_results['legend']) ? $db_results['legend'] : '';
        } else {
            return '';
        }

    }

    public function getAutoCompleteCity() {

        if ($this->context->cache_enable && is_object($this->context->cache_api)) {
            $value = $this->context->cache_api->getData('getAutoCompleteCity');
            $temp = empty($value) ? null : $this->jsonDecode($value, true);

            if (!empty($temp)) {
                return $temp;
            }

        }

        $result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('`post_code`, `city`')
                ->from('post_code')
                ->orderBy('city')
        );

        if ($this->context->cache_enable && is_object($this->context->cache_api)) {
            $temp = $result === null ? null : $this->jsonEncode($result);
            $this->context->cache_api->putData('getAutoCompleteCity', $temp, 864000);
        }

        return $result;

    }

    public function renderComposerFooter() {

        $composer = Composer::getInstance();

        return $composer->renderEditorFooter();
    }

    public function getIoPlugins() {

        $plugins = [];
        $installed_plugins = Plugin::getPluginsDirOnDisk();

        foreach ($installed_plugins as $plugin) {
            $plugins[$plugin] = Plugin::isInstalled($plugin, false);
        }

        return $plugins;
    }

    public function getIoLangs() {

        $langs = [];
        $languages = Language::getLanguages(false);

        foreach ($languages as $language) {
            $langs[$language['iso_code']] = [
                $language['iso_code'] => $language['name'],
            ];
        }

        return $langs;
    }
    
    public function getTabs() {
        
        $idLang = $this->context->phenyxConfig->get('EPH_LANG_DEFAULT');
        
        $topbars = BackTab::getBackTabs($idLang, 1, false);
        
        foreach ($topbars as $index => $tab) {

            
            $topbars[$index]['name'] = $tab['name'];
            $subTabs = BackTab::getBackTabs($idLang, $tab['id_back_tab'], false);

            foreach ($subTabs as $index2 => &$subTab) {

                
                $terTabs = BackTab::getBackTabs($idLang, $subTab['id_back_tab'], false);

                foreach ($terTabs as $index3 => $terTab) {

                    
                }

                $subTabs[$index2]['sub_tabs'] = array_values($terTabs);

            }

            $topbars[$index]['sub_tabs'] = array_values($subTabs);
        }
        
        return $topbars;

    }

    public function generateTabs($use_cache = true) {
        
        if ($use_cache && $this->context->cache_enable && is_object($this->context->cache_api)) {
            $value = $this->context->cache_api->getData('generateTabs_'.$this->context->employee->id);
            $temp = empty($value) ? null : $this->jsonDecode($value, true);

            if (!empty($temp) && is_array($temp) && count($temp)) {
                return $temp;
            }

        }

        $topbars = BackTab::getBackTabs($this->context->language->id, 1, $use_cache);

        foreach ($topbars as $index => $tab) {

            if (!BackTab::checkTabRights($tab['id_back_tab'])) {
                unset($topbars[$index]);
                continue;
            }

            if ($tab['master'] && $this->context->employee->phenyx_admin == 0) {
                unset($topbars[$index]);
                continue;
            }

            if (!empty($tab['plugin'])) {

                if (!Plugin::isActive($tab['plugin'])) {
                    unset($topbars[$index]);
                    continue;
                }

            }

            if (!is_null($tab['function'])) {
                $topbars[$index]['function'] = str_replace("‘", "'", $tab['function']);
            }

            $topbars[$index]['name'] = $tab['name'];
            $subTabs = BackTab::getBackTabs($this->context->language->id, $tab['id_back_tab'], $use_cache);

            foreach ($subTabs as $index2 => &$subTab) {

                if (!BackTab::checkTabRights($subTab['id_back_tab'])) {
                    unset($subTabs[$index2]);
                    continue;
                }

                if ($subTab['master'] && $this->context->employee->phenyx_admin == 0) {
                    unset($subTabs[$index2]);
                    continue;
                }

                if (!empty($subTab['plugin'])) {
                    if (!Plugin::isActive($subTab['plugin'])) {
                        unset($subTabs[$index2]);
                        continue;
                    }
                }

                if ((bool) $subTab['active']) {

                    if (!is_null($subTab['function'])) {
                        $subTabs[$index2]['function'] = str_replace("‘", "'", $subTab['function']);
                    }

                    $subTabs[$index2]['name'] = $subTab['name'];
                }

                $terTabs = BackTab::getBackTabs($this->context->language->id, $subTab['id_back_tab'], $use_cache);

                foreach ($terTabs as $index3 => $terTab) {

                    if (!BackTab::checkTabRights($terTab['id_back_tab'])) {
                        unset($terTabs[$index3]);
                        continue;
                    }

                    if ($terTab['master'] && $this->context->employee->phenyx_admin == 0) {
                        unset($terTabs[$index3]);
                        continue;
                    }

                    if (!empty($terTab['plugin'])) {

                        if (!Plugin::isActive($terTab['plugin'])) {
                            unset($terTabs[$index3]);
                            continue;
                        }

                    }

                    if ((bool) $terTab['active']) {

                        if (!is_null($terTab['function'])) {
                            $terTabs[$index3]['function'] = str_replace("‘", "'", $terTab['function']);
                        }

                        $terTabs[$index3]['name'] = $terTab['name'];
                    }

                }

                $subTabs[$index2]['sub_tabs'] = array_values($terTabs);

            }

            $topbars[$index]['sub_tabs'] = array_values($subTabs);
        }
        
        $hookBars = Hook::getInstance()->exec('actionAfterAdminTabs', ['topbars' => $topbars], null, true);

        if (is_array($hookBars)) {

            foreach ($hookBars as $plugin => $hookBar) {
                if(is_array($hookBar)) {
                    $topbars = $hookBar;
                }
            }

        }
       
        if ($this->context->cache_enable && is_object($this->context->cache_api)) {
            $temp = $topbars === null ? null : $this->jsonEncode($topbars);
            $this->context->cache_api->putData('generateTabs_'.$this->context->employee->id, $temp);
        }

        return $topbars;
    }

    public function getGoogleTranslation($google_api_key, $text, $target) {

        if (empty($text)) {
            return $text;
        }

        $translate = new TranslateClient([
            'key' => $google_api_key,
        ]);

        $result = $translate->translate($text, [
            'target' => $target,
        ]);
        $translation = new Translation();
        $translation->iso_code = $target;
        $translation->origin = $text;
        $translation->translation = $result['text'];
        $translation->date_upd = date('Y-m-d H:i:s');
        try {
            $translation->add();
        } catch (exception $e) {
            PhenyxLogger::addLog($this->l('getGoogleTranslation', 'PhenyxTool', false, false), 1, null, 'Tools', $e->getMessage(), true, 0);
        }

        $return = [
            'translation' => $result['text'],
        ];
        return $return;
    }

    public function getRedisSeverbyId($idServer) {

        $sql = new DbQuery();
        $sql->select('*');
        $sql->from('redis_servers');
        $sql->where('`id_redis_server` = ' . $idServer);

        $server = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getRow($sql);

        return $this->jsonDecode($this->jsonEncode($server));

    }

    public function str_contains($search, $string) {

        return str_contains($string, $search);
    }

    public function str_starts_with($search, $string) {

        return str_starts_with($string, $search);
    }

    public function str_ends_with($search, $string) {

        return str_ends_with($string, $search);
    }

    public function Rtrim($string, $char) {

        if (!is_null($string)) {
            return rtrim($string, $char);
        }

        return $string;
    }

    public function build_date($args) {

        return date($args);
    }

    public function smartyCount($array) {

        if (is_array($array)) {
            return count($array);
        }

        return null;
    }

    public function resizeImg($image) {

        $destination = $image;

        $fileext = $this->strtolower(pathinfo($destination, PATHINFO_EXTENSION));
        $newFile = str_replace("." . $fileext, '.webp', $image);

        if (file_exists($newFile)) {
            return true;
        }

        return $this->context->img_manager->actionOnImageResizeAfter($destination, $newFile);

    }

    public function isNull($string) {

        return is_null($string);

    }

    public function isObject($object) {

        return is_object($object);

    }

    public function isBool($string) {

        return is_bool($string);

    }

    public function isFloat($string) {

        return is_float($string);

    }

    public function phpVersion() {

        return phpversion();

    }

    public function isString($string) {

        return is_string($string);

    }

    public function isInteger($string) {

        return is_int($string);

    }

    public function strReplace($field, $replace, $string) {

        if (is_string($string)) {
            return str_replace($field, $replace, $string);
        }

    }

    public function smartyConstant($string) {

        return constant($string);

    }

    public function varExport($array, $return = false) {

        if (is_array($array)) {
            return var_export($array, $return);
        }

        return null;

    }

    public function pregReplace($pattern, $replacement, $subject, $limit = -1, &$count = null) {

        return preg_replace($pattern, $replacement, $string, $limit, $count);

    }

    public function intVal($value, $base = 10) {

        return intval($value, $base);
    }

    public function strStr($haystack, $needle, $before = false) {

        return strstr($haystack, $needle, $before);
    }

    public function arrayValues($array) {

        if (is_array($array)) {
            return array_values($array);
        }

        return null;

    }

    public function printR($array) {

        if (is_array($array)) {
            return print_r($array);
        }

        return $array;

    }

    public function inArray($string, $array) {

        if (is_array($array)) {
            return in_array($string, $array);
        }

        return $array;

    }

    public function arrayChunk($array, $length, $preserve_keys = false) {

        if (is_array($array)) {
            return array_chunk($array, $length, $preserve_keys);
        }

        return null;

    }

    public function mdString($string) {

        if (is_string($string)) {
            return md5($string);
        }

        return null;

    }

    public function timeToSeconds(string $time) {

        if (str_contains($time, ':')) {
            $arr = explode(':', $time);

            if (count($arr) === 3) {
                return $arr[0] * 3600 + $arr[1] * 60 + $arr[2];
            }

            return $arr[0] * 60 + $arr[1];
        }

        return $time;
    }

    public function secondsToTime($seconds) {

        if ($seconds > 0) {
            $secs = $seconds % 60;
            $secs = $secs < 10 ? '0' . $secs : $secs;
            $hrs = $seconds / 60;
            $hrs = floor($hrs);
            $mins = $hrs % 60;
            $mins = $mins < 10 ? '0' . $mins : $mins;
            $hrs = round($hrs / 60);
            $hrs = $hrs < 10 ? '0' . $hrs : $hrs;

            return $hrs . ':' . $mins . ':' . $secs;
        }

        return $seconds;
    }

    public function minifyHTML($htmlContent) {

        if (trim($htmlContent) === "") {
            return $htmlContent;
        }

        // Remove extra white-space(s) between HTML attribute(s)
        $htmlContent = preg_replace_callback('#<([^\/\s<>!]+)(?:\s+([^<>]*?)\s*|\s*)(\/?)>#s', function ($matches) {

            return '<' . $matches[1] . preg_replace('#([^\s=]+)(\=([\'"]?)(.*?)\3)?(\s+|$)#s', ' $1$2', $matches[2]) . $matches[3] . '>';
        }, str_replace("\r", "", $htmlContent));
        // Minify inline CSS declaration(s)

        if (strpos($htmlContent, ' style=') !== false) {
            $htmlContent = preg_replace_callback('#<([^<]+?)\s+style=([\'"])(.*?)\2(?=[\/\s>])#s', function ($matches) {

                return '<' . $matches[1] . ' style=' . $matches[2] . $this->minify_css($matches[3]) . $matches[2];
            }, $htmlContent);
        }

        if (strpos($htmlContent, '</style>') !== false) {
            $htmlContent = preg_replace_callback('#<style(.*?)>(.*?)</style>#is', function ($matches) {

                return '<style' . $matches[1] . '>' . $this->minify_css($matches[2]) . '</style>';
            }, $htmlContent);
        }

        if (strpos($htmlContent, '</script>') !== false) {
            $htmlContent = preg_replace_callback('#<script(.*?)>(.*?)</script>#is', function ($matches) {

                return '<script' . $matches[1] . '>' . $this->minify_js($matches[2]) . '</script>';
            }, $htmlContent);
        }

        return preg_replace(
            [
                // t = text
                // o = tag open
                // c = tag close
                // Keep important white-space(s) after self-closing HTML tag(s)
                '#<(img|input)(>| .*?>)#s',
                // Remove a line break and two or more white-space(s) between tag(s)
                '#(<!--.*?-->)|(>)(?:\n*|\s{2,})(<)|^\s*|\s*$#s',
                '#(<!--.*?-->)|(?<!\>)\s+(<\/.*?>)|(<[^\/]*?>)\s+(?!\<)#s', // t+c || o+t
                '#(<!--.*?-->)|(<[^\/]*?>)\s+(<[^\/]*?>)|(<\/.*?>)\s+(<\/.*?>)#s', // o+o || c+c
                '#(<!--.*?-->)|(<\/.*?>)\s+(\s)(?!\<)|(?<!\>)\s+(\s)(<[^\/]*?\/?>)|(<[^\/]*?\/?>)\s+(\s)(?!\<)#s', // c+t || t+o || o+t -- separated by long white-space(s)
                '#(<!--.*?-->)|(<[^\/]*?>)\s+(<\/.*?>)#s', // empty tag
                '#<(img|input)(>| .*?>)<\/\1>#s', // reset previous fix
                '#(&nbsp;)&nbsp;(?![<\s])#', // clean up ...
                '#(?<=\>)(&nbsp;)(?=\<)#', // --ibid
                // Remove HTML comment(s) except IE comment(s)
                '#\s*<!--(?!\[if\s).*?-->\s*|(?<!\>)\n+(?=\<[^!])#s',
            ],
            [
                '<$1$2<$1>',
                '$1$2$3',
                '$1$2$3',
                '$1$2$3$4$5',
                '$1$2$3$4$5$6$7',
                '$1$2$3',
                '<$1$2',
                '$1 ',
                '$1',
                "",
            ],
            $htmlContent);
    }

    public function minify_css($input) {

        if (trim($input) === "") {
            return $input;
        }

        return preg_replace(
            [
                // Remove comment(s)
                '#("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\')|\/\*(?!\!)(?>.*?\*\/)|^\s*|\s*$#s',
                // Remove unused white-space(s)
                '#("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\'|\/\*(?>.*?\*\/))|\s*+;\s*+(})\s*+|\s*+([*$~^|]?+=|[{};,>~]|\s(?![0-9\.])|!important\b)\s*+|([[(:])\s++|\s++([])])|\s++(:)\s*+(?!(?>[^{}"\']++|"(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\')*+{)|^\s++|\s++\z|(\s)\s+#si',
                // Replace `0(cm|em|ex|in|mm|pc|pt|px|vh|vw|%)` with `0`
                '#(?<=[\s:])(0)(cm|em|ex|in|mm|pc|pt|px|vh|vw|%)#si',
                // Replace `:0 0 0 0` with `:0`
                '#:(0\s+0|0\s+0\s+0\s+0)(?=[;\}]|\!important)#i',
                // Replace `background-position:0` with `background-position:0 0`
                '#(background-position):0(?=[;\}])#si',
                // Replace `0.6` with `.6`, but only when preceded by `:`, `,`, `-` or a white-space
                '#(?<=[\s:,\-])0+\.(\d+)#s',
                // Minify string value
                '#(\/\*(?>.*?\*\/))|(?<!content\:)([\'"])([a-z_][a-z0-9\-_]*?)\2(?=[\s\{\}\];,])#si',
                '#(\/\*(?>.*?\*\/))|(\burl\()([\'"])([^\s]+?)\3(\))#si',
                // Minify HEX color code
                '#(?<=[\s:,\-]\#)([a-f0-6]+)\1([a-f0-6]+)\2([a-f0-6]+)\3#i',
                // Replace `(border|outline):none` with `(border|outline):0`
                '#(?<=[\{;])(border|outline):none(?=[;\}\!])#',
                // Remove empty selector(s)
                '#(\/\*(?>.*?\*\/))|(^|[\{\}])(?:[^\s\{\}]+)\{\}#s',
            ],
            [
                '$1',
                '$1$2$3$4$5$6$7',
                '$1',
                ':0',
                '$1:0 0',
                '.$1',
                '$1$3',
                '$1$2$4$5',
                '$1$2$3',
                '$1:0',
                '$1$2',
            ],
            $input);
    }

    public function minify_js($input) {

        if (trim($input) === "") {
            return $input;
        }

        return preg_replace(
            [
                // Remove comment(s)
                '#\s*("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\')\s*|\s*\/\*(?!\!|@cc_on)(?>[\s\S]*?\*\/)\s*|\s*(?<![\:\=])\/\/.*(?=[\n\r]|$)|^\s*|\s*$#',
                // Remove white-space(s) outside the string and regex
                '#("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\'|\/\*(?>.*?\*\/)|\/(?!\/)[^\n\r]*?\/(?=[\s.,;]|[gimuy]|$))|\s*([!%&*\(\)\-=+\[\]\{\}|;:,.<>?\/])\s*#s',
                // Remove the last semicolon
                '#;+\}#',
                // Minify object attribute(s) except JSON attribute(s). From `{'foo':'bar'}` to `{foo:'bar'}`
                '#([\{,])([\'])(\d+|[a-z_][a-z0-9_]*)\2(?=\:)#i',
                // --ibid. From `foo['bar']` to `foo.bar`
                '#([a-z0-9_\)\]])\[([\'"])([a-z_][a-z0-9_]*)\2\]#i',
            ],
            [
                '$1',
                '$1$2',
                '}',
                '$1$3',
                '$1.$3',
            ],
            $input);
    }

    public function getHolidays($year = null) {

        if ($year === null) {
            $year = intval(strftime('%Y'));
        }

        $easterDate = easter_date($year);
        $easterDay = date('j', $easterDate);
        $easterMonth = date('n', $easterDate);
        $easterYear = date('Y', $easterDate);

        $holidays = [
            // Jours feries fixes
            mktime(0, 0, 0, 1, 1, $year), // 1er janvier
            mktime(0, 0, 0, 5, 1, $year), // Fete du travail
            mktime(0, 0, 0, 5, 8, $year), // Victoire des allies
            mktime(0, 0, 0, 7, 14, $year), // Fete nationale
            mktime(0, 0, 0, 8, 15, $year), // Assomption
            mktime(0, 0, 0, 11, 1, $year), // Toussaint
            mktime(0, 0, 0, 11, 11, $year), // Armistice
            mktime(0, 0, 0, 12, 25, $year), // Noel

            // Jour feries qui dependent de paques
            mktime(0, 0, 0, $easterMonth, $easterDay + 1, $easterYear), // Lundi de paques
            mktime(0, 0, 0, $easterMonth, $easterDay + 39, $easterYear), // Ascension
            mktime(0, 0, 0, $easterMonth, $easterDay + 50, $easterYear), // Pentecote
        ];

        sort($holidays);

        return $holidays;
    }

    public function parseEmailContent($content, $tpl) {

        $translate = [];
        $this->context = $this->context;
        preg_match_all("~{l s='([^{]*)' mail='true'}~i", $content, $match);
        preg_match_all("~{l s='([^{]*)' sprintf=([^{]*) mail='true'}~i", $content, $match2);
        $search = array_merge(
            $match,
            $match2
        );

        foreach ($search as $key => $strings) {

            if ($key == 0) {

                foreach ($strings as $k => $string) {
                    $trans = '<span class="parent-translate" id="' . $tpl . md5($search[1][$k]) . '"><span class="translate-string" contenteditable="true">';
                    $trans .= $this->context->translations->getMailsTranslation($search[1][$k], $tpl);
                    $trans .= '</span></span>';
                    $translate[$search[0][$k]] = $trans;
                }

            }

            if ($key == 2) {

                foreach ($strings as $k => $string) {
                    $id = $tpl . md5($search[3][$k]);
                    $translation = $this->context->translations->getMailsTranslation($search[3][$k], $tpl);
                    $sprintf = explode(",", str_replace(['[', ']'], '', $search[4][$k]));

                    foreach ($sprintf as $index => $value) {

                        if ($index == 0) {
                            $translate[$search[2][$k]] = $this->strReplaceFirst('%s', '</span><span>{' . trim($value) . '}</span><span data-index="' . $index . '" class="translate-string" contenteditable="true">', $translation);
                        } else {
                            $translate[$search[2][$k]] = $this->strReplaceFirst('%s', '</span><span>{' . trim($value) . '}</span><span class="translate-string" data-index="' . $index . '" contenteditable="true">', $translate[$search[2][$k]]);
                        }

                    }

                    $translate[$search[2][$k]] = '<span class="parent-translate" id="' . $id . '"><span class="translate-string" contenteditable="true">' . $translate[$search[2][$k]] . '</span></span>';
                }

            }

        }

        return str_replace(array_keys($translate), $translate, $content);
    }

    public function ip_info($ip = NULL, $purpose = "location", $deep_detect = TRUE) {
        $output = NULL;

        if (filter_var($ip, FILTER_VALIDATE_IP) === FALSE) {
            $ip = $_SERVER["REMOTE_ADDR"];

            if ($deep_detect) {

                if (filter_var(@$_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP)) {
                    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
                }

                if (filter_var(@$_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP)) {
                    $ip = $_SERVER['HTTP_CLIENT_IP'];
                }

            }

        }

        $purpose = str_replace(["name", "\n", "\t", " ", "-", "_"], NULL, strtolower(trim($purpose)));
        $support = ["country", "countrycode", "state", "region", "city", "location", "address"];
        $continents = [
            "AF" => "Africa",
            "AN" => "Antarctica",
            "AS" => "Asia",
            "EU" => "Europe",
            "OC" => "Australia (Oceania)",
            "NA" => "North America",
            "SA" => "South America",
        ];

        if (filter_var($ip, FILTER_VALIDATE_IP) && in_array($purpose, $support)) {
            $ipdat = @json_decode(file_get_contents("http://www.geoplugin.net/json.gp?ip=" . $ip));

            if (@strlen(trim($ipdat->geoplugin_countryCode)) == 2) {

                switch ($purpose) {
                case "location":
                    $output = [
                        "city"           => @$ipdat->geoplugin_city,
                        "state"          => @$ipdat->geoplugin_regionName,
                        "country"        => @$ipdat->geoplugin_countryName,
                        "country_code"   => @$ipdat->geoplugin_countryCode,
                        "continent"      => @$continents[strtoupper($ipdat->geoplugin_continentCode)],
                        "continent_code" => @$ipdat->geoplugin_continentCode,
                    ];
                    break;
                case "address":
                    $address = [$ipdat->geoplugin_countryName];

                    if (@strlen($ipdat->geoplugin_regionName) >= 1) {
                        $address[] = $ipdat->geoplugin_regionName;
                    }

                    if (@strlen($ipdat->geoplugin_city) >= 1) {
                        $address[] = $ipdat->geoplugin_city;
                    }

                    $output = implode(", ", array_reverse($address));
                    break;
                case "city":
                    $output = @$ipdat->geoplugin_city;
                    break;
                case "state":
                    $output = @$ipdat->geoplugin_regionName;
                    break;
                case "region":
                    $output = @$ipdat->geoplugin_regionName;
                    break;
                case "country":
                    $output = @$ipdat->geoplugin_countryName;
                    break;
                case "countrycode":
                    $output = @$ipdat->geoplugin_countryCode;
                    break;
                }

            }

        }

        return $output;
    }

}
