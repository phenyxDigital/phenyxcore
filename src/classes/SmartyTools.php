<?php

/**
 * Class SmartyTools
 *
 * @since 1.9.1.0
 */
class SmartyTools {

   

    public static function getMemoryLimit() {

        $memory_limit = @ini_get('memory_limit');

        return Tools::getOctets($memory_limit);
    }

    public static function getOctets($option) {

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
    
    public static function rtrimString($str, $str_search) {

        $length_str = strlen($str_search);

        if (strlen($str) >= $length_str && substr($str, -$length_str) == $str_search) {
            $str = substr($str, 0, -$length_str);
        }

        return $str;
    }

    public static function formatBytes($size, $precision = 2) {

        if (!$size) {
            return '0';
        }

        $base = log($size) / log(1024);
        $suffixes = ['', 'k', 'M', 'G', 'T'];

        return round(pow(1024, $base - floor($base)), $precision) . $suffixes[floor($base)];
    }

    public static function boolVal($value) {

        if (empty($value)) {
            $value = false;
        }

        return (bool) $value;
    }
    
    public static function isString($string) {
        
        return is_string($string);
        
    }
    
     public static function varExport($array, $return = false) {
        
        if(is_array($array)) {
            return var_export($array, $return);
        }
        return null;
        
    }
    
    public static function isFloat($string) {
        
        return is_float($string);
        
    }
    
    public static function isInteger($string) {
        
        return is_int($string);
        
    }
    
    public static function initGet($string) {
        
        if(is_string($string)) {
            return ini_get($option);
        }
        return false;
        
    }
    
    public static function strReplace($field, $replace, $string) {
        
        if(is_string($string)) {
            return str_replace($field, $replace, $string);
        }
        
    }
    
    public static function isArray($str) {

        if (is_array($str)) {
            return true;
        }

        return false;
    }
    
    public static function isNull($string) {
        
        return is_null($string);
        
    }
    
    public static function isObject($object) {
        
        return is_object($object);
        
    }
    
    public static function isBool($string) {
        
        return is_bool($string);
        
    }
    
    public static function str_contains($search, $string) {
        
        return str_contains($string, $search);
    }
    
    public static function str_starts_with($search, $string) {
        
        return str_starts_with($string, $search);
    }
    
    public static function str_ends_with($search, $string) {
        
        return str_ends_with($string, $search);
    }
    
    public static function Rtrim($string, $char) {
        if(!is_null($string)) {
            return rtrim($string, $char);
        }
        return $string;
    }
    
    public static function build_date($args) {
        
        return date($args);
    }
    
    public static function smartyCount($array) {
        if(is_array($array)) {
            return count($array);
        }
        return null;
    }
    
    public static function addCslashes($string, $character) {
        
        return addcslashes($string, $characters);
    }
    
    public static function curRent($array) {
        return current($array);
    }
    
    public static function reSet($array) {
        return reset($array);
    }
    
    public static function printR($array) {
        if(is_array($array)) {
            return print_r($array);
        }
        return $array;
        
    }
    
    public static function inArray($string, $array) {
        if(is_array($array)) {
            return in_array($string, $array);
        }
        return $array;
        
    }
    
    public static function getAdminTranslation($string, $className) {
        
        $context = Context::getContext();
        if (!isset($context->phenyxConfig)) {
            $context->phenyxConfig = new Configuration();
            
        }
        if (!isset($context->company)) {
            $context->company = new Company($context->phenyxConfig->get('EPH_COMPANY_ID'));
            
        }
        if (!isset($context->language)) {
            $context->language = Tools::jsonDecode(Tools::jsonEncode(Language::buildObject('Language', $context->phenyxConfig->get('EPH_LANG_DEFAULT')))); 
        }
        if (!isset($context->translations)) {

            $context->translations = new Translate($context->language->iso_code, $context->company);
        }
        return $context->translations->getAdminTranslation($string, $$className);
        
    }
    
    public static function arrayChunk($array, $length, $preserve_keys = false) {
        
        if(is_array($array)) {
            return array_chunk($array, $length, $preserve_keys);
        }
        return null;
        
    }
    
    public static function strTolower($string) {
        
        return strtolower($string);
    }
    
    public static function strStr($haystack, $needle, $before = false) {
        
        return strstr($haystack, $needle, $before);
    }
    
    public static function pregReplace($pattern, $replacement, $subject, $limit = -1, &$count = null) {
        
        return preg_replace($pattern, $replacement, $string, $limit, $count);
        
    }

    public static function intVal($value, $base = 10) {
        
        return intval($value, $base);               
    }
    
    public static function trimString($string) {
        
        if(!is_null($string)) {
            return trim($string);
        }
        return null;
    }
    
    public static function arrayValues($array) {
        
        if(is_array($array)) {
            return array_values($array);
        }
        return null;
        
    }
    
    public static function implodeArray($array, $args) {
        
        if(is_array($array)) {
            return implode($args, $array);
        }
    }
    
    public static function sizeOf($array) {
        if(is_array($array)) {
            return sizeof($array);
        }
        return null;
    }
    
    public static function sprinTf($string, $args) {
        
        return sprintf($string, $args);
    }
    
    public static function htmlEntities($string, $flags = ENT_QUOTES, $encoding = null, $double_encode = true) {
        
        return htmlentities($string, $flags, $encoding, $double_encode);
    }
    
    public static function ucFirst($str) {

        return ucfirst($str);
    }
    
    public static function arrayKeyExists($key, $array) {
        
        if(is_array($array)) {
            return array_key_exists($key, $array);
        }
        return false;
        
    }
    
    public static function fileExists($path) {
        
        return file_exists($path);
    }

  
}
