<?php

/**
 * Class AddressFormat
 *
 * @since 1.9.1.0
 */
class AddressFormat extends PhenyxObjectModel {

    public $require_context = false;
    // @codingStandardsIgnoreStart
    /** @var int */
    public $id_address_format;
    /** @var int */
    public $id_country;
    /** @var string */
    public $format;
    /** @var array $_errorFormatList */
    protected $_errorFormatList = [];
    // @codingStandardsIgnoreEnd

    /**
     * @see PhenyxObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'address_format',
        'primary' => 'id_country',
        'fields'  => [
            'format'     => ['type' => self::TYPE_HTML, 'validate' => 'isGenericName', 'required' => true],
            'id_country' => ['type' => self::TYPE_INT],
        ],
    ];

    public static $requireFormFieldsList = [
        'firstname',
        'lastname',
        'address1',
        'postcode',
        'city',
        'Country:name',
    ];

    public static $forbiddenPropertyList = [
        'deleted',
        'date_add',
        'alias',
        'secure_key',
        'note',
        'newsletter',
        'ip_registration_newsletter',
        'newsletter_date_add',
        'optin',
        'passwd',
        'last_passwd_gen',
        'active',
        'is_guest',
        'date_upd',
        'country',
        'years',
        'days',
        'months',
        'description',
        'meta_description',
        'short_description',
        'link_rewrite',
        'meta_title',
        'meta_keywords',
        'display_tax_label',
        'need_zip_code',
        'contains_states',
        'call_prefixes',
        'show_public_prices',
        'max_payment',
        'max_payment_days',
        'geoloc_postcode',
        'logged',
        'account_number',
        'groupBox',
        'ape',
        'max_payment',
        'outstanding_allow_amount',
        'call_prefix',
        'definition',
        'debug_list',
    ];

    public static $forbiddenClassList = [
        'Manufacturer',
        'Supplier',
    ];

    const _CLEANING_REGEX_ = '#([^\w:_]+)#i';

    protected function _checkValidateClassField($className, $fieldName, $isIdField) {

        $isValide = false;

        if (!class_exists($className)) {
            $this->_errorFormatList[] = $this->l('This class name does not exist.') . ': ' . $className;
        } else {
            $obj = new $className();
            $reflect = new ReflectionObject($obj);

            // Check if the property is accessible
            $publicProperties = $reflect->getProperties(ReflectionProperty::IS_PUBLIC);

            foreach ($publicProperties as $property) {
                $propertyName = $property->getName();

                if (($propertyName == $fieldName) && ($isIdField ||
                    (!preg_match('/\bid\b|id_\w+|\bid[A-Z]\w+/', $propertyName)))
                ) {
                    $isValide = true;
                }

            }

            if (!$isValide) {
                $this->_errorFormatList[] = $this->l('This property does not exist in the class or is forbidden.') . ': ' . $className . ': ' . $fieldName;
            }

            unset($obj);
            unset($reflect);
        }

        return $isValide;
    }

    protected function _checkLiableAssociation($patternName, $fieldsValidate) {

        $patternName = trim($patternName);

        if ($associationName = explode(':', $patternName)) {
            $totalNameUsed = count($associationName);

            if ($totalNameUsed > 2) {
                $this->_errorFormatList[] = $this->l('This association has too many elements.');
            } else if ($totalNameUsed == 1) {
                $associationName[0] = strtolower($associationName[0]);

                if (in_array($associationName[0], static::$forbiddenPropertyList) ||
                    !$this->_checkValidateClassField('Address', $associationName[0], false)
                ) {
                    $this->_errorFormatList[] = $this->l('This name is not allowed.') . ': ' . $associationName[0];
                }

            } else if ($totalNameUsed == 2) {

                if (empty($associationName[0]) || empty($associationName[1])) {
                    $this->_errorFormatList[] = $this->l('Syntax error with this pattern.') . ': ' . $patternName;
                } else {
                    $associationName[0] = ucfirst($associationName[0]);
                    $associationName[1] = strtolower($associationName[1]);

                    if (in_array($associationName[0], static::$forbiddenClassList)) {
                        $this->_errorFormatList[] = $this->l('This name is not allowed.') . ': ' . $associationName[0];
                    } else {
                        // Check if the id field name exist in the Address class
                        // Don't check this attribute on Address (no sense)

                        if ($associationName[0] != 'Address') {
                            $this->_checkValidateClassField('Address', 'id_' . strtolower($associationName[0]), true);
                        }

                        // Check if the field name exist in the class write by the user
                        $this->_checkValidateClassField($associationName[0], $associationName[1], false);
                    }

                }

            }

        }

    }

    public function checkFormatFields() {

        $this->_errorFormatList = [];
        $fieldsValidate = Address::getFieldsValidate();
        $usedKeyList = [];

        $multipleLineFields = explode("\n", $this->format);

        if ($multipleLineFields && is_array($multipleLineFields)) {

            foreach ($multipleLineFields as $lineField) {

                if (($patternsName = preg_split(static::_CLEANING_REGEX_, $lineField, -1, PREG_SPLIT_NO_EMPTY))) {

                    if (is_array($patternsName)) {

                        foreach ($patternsName as $patternName) {

                            if (!in_array($patternName, $usedKeyList)) {
                                $this->_checkLiableAssociation($patternName, $fieldsValidate);
                                $usedKeyList[] = $patternName;
                            } else {
                                $this->_errorFormatList[] = $this->l('This key has already been used.') . ': ' . $patternName;
                            }

                        }

                    }

                }

            }

        }

        return (count($this->_errorFormatList)) ? false : true;
    }

    public function getErrorList() {

        return $this->_errorFormatList;
    }

    protected static function _setOriginalDisplayFormat(&$formattedValueList, $currentLine, $currentKeyList) {

        if ($currentKeyList && is_array($currentKeyList)) {

            if ($originalFormattedPatternList = explode(' ', $currentLine)) {
                // Foreach the available pattern

                foreach ($originalFormattedPatternList as $patternNum => $pattern) {
                    // Var allows to modify the good formatted key value when multiple key exist into the same pattern
                    $mainFormattedKey = '';

                    // Multiple key can be found in the same pattern

                    foreach ($currentKeyList as $key) {
                        // Check if we need to use an older modified pattern if a key has already be matched before
                        $replacedValue = empty($mainFormattedKey) ? $pattern : $formattedValueList[$mainFormattedKey];

                        $chars = $start = $end = str_replace($key, '', $replacedValue);

                        if (preg_match(static::_CLEANING_REGEX_, $chars)) {

                            if (mb_substr($replacedValue, 0, mb_strlen($chars)) == $chars) {
                                $end = '';
                            } else {
                                $start = '';
                            }

                            if ($chars) {
                                $replacedValue = str_replace($chars, '', $replacedValue);
                            }

                        }

                        if ($formattedValue = preg_replace('/^' . $key . '$/', $formattedValueList[$key] ?: '', $replacedValue, -1, $count)) {

                            if ($count) {
                                // Allow to check multiple key in the same pattern,

                                if (empty($mainFormattedKey)) {
                                    $mainFormattedKey = $key;
                                }

                                // Set the pattern value to an empty string if an older key has already been matched before

                                if ($mainFormattedKey != $key) {
                                    $formattedValueList[$key] = '';
                                }

                                // Store the new pattern value
                                $formattedValueList[$mainFormattedKey] = $start . $formattedValue . $end;
                                unset($originalFormattedPatternList[$patternNum]);
                            }

                        }

                    }

                }

            }

        }

    }

    public static function cleanOrderedAddress(&$orderedAddressField) {

        foreach ($orderedAddressField as &$line) {
            $cleanedLine = '';

            if (($keyList = preg_split(static::_CLEANING_REGEX_, $line, -1, PREG_SPLIT_NO_EMPTY))) {

                foreach ($keyList as $key) {
                    $cleanedLine .= $key . ' ';
                }

                $cleanedLine = trim($cleanedLine);
                $line = $cleanedLine;
            }

        }

    }

    public static function getFormattedAddressFieldsValues($address, $addressFormat, $idLang = null) {

        if (!$idLang) {
            $idLang = Context::getContext()->language->id;
        }

        $tab = [];
        $temporyObject = [];

        // Check if $address exist and it's an instanciate object of Address

        if ($address && ($address instanceof Address)) {

            if (Plugin::isInstalled('vatnumber')
                && Plugin::isEnabled('vatnumber')
                && file_exists(_EPH_PLUGIN_DIR_ . 'vatnumber/vatnumber.php')) {
                include_once _EPH_PLUGIN_DIR_ . 'vatnumber/vatnumber.php';

                if (method_exists('VatNumber', 'adjustAddressForLayout')) {
                    VatNumber::adjustAddressForLayout($address);
                }

            }

            foreach ($addressFormat as $line) {

                if (($keyList = preg_split(static::_CLEANING_REGEX_, $line, -1, PREG_SPLIT_NO_EMPTY)) && is_array($keyList)) {

                    foreach ($keyList as $pattern) {

                        if ($associateName = explode(':', $pattern)) {
                            $totalName = count($associateName);

                            if ($totalName == 1 && isset($address->{$associateName[0]})) {
                                $tab[$associateName[0]] = $address->{$associateName[0]};
                            } else {
                                $tab[$pattern] = '';

                                // Check if the property exist in both classes

                                if (($totalName == 2) && class_exists($associateName[0]) &&
                                    property_exists($associateName[0], $associateName[1]) &&
                                    property_exists($address, 'id_' . strtolower($associateName[0]))
                                ) {
                                    $idFieldName = 'id_' . strtolower($associateName[0]);

                                    if (!isset($temporyObject[$associateName[0]])) {
                                        $temporyObject[$associateName[0]] = new $associateName[0]($address->{$idFieldName});
                                    }

                                    if ($temporyObject[$associateName[0]]) {
                                        $tab[$pattern] = (is_array($temporyObject[$associateName[0]]->{$associateName[1]})) ?
                                        ((isset($temporyObject[$associateName[0]]->{$associateName[1]}
                                            [$idLang])) ?
                                            $temporyObject[$associateName[0]]->{$associateName[1]}
                                            [$idLang] : '') :
                                        $temporyObject[$associateName[0]]->{$associateName[1]};
                                    }

                                }

                            }

                        }

                    }

                    AddressFormat::_setOriginalDisplayFormat($tab, $line, $keyList);
                }

            }

        }

        AddressFormat::cleanOrderedAddress($addressFormat);
        // Free the instanciate objects

        foreach ($temporyObject as &$object) {
            unset($object);
        }

        return $tab;
    }

    public static function generateAddress(Address $address, $patternRules = [], $newLine = "\r\n", $separator = ' ', $style = []) {

        $addressFields = AddressFormat::getOrderedAddressFields($address->id_country);
        $addressFormatedValues = AddressFormat::getFormattedAddressFieldsValues($address, $addressFields);

        $addressText = '';

        foreach ($addressFields as $line) {

            if (($patternsList = preg_split(static::_CLEANING_REGEX_, $line, -1, PREG_SPLIT_NO_EMPTY))) {
                $tmpText = '';

                foreach ($patternsList as $pattern) {

                    if ((!array_key_exists('avoid', $patternRules)) ||
                        (is_array($patternRules) && array_key_exists('avoid', $patternRules) && !in_array($pattern, $patternRules['avoid']))
                    ) {
                        $tmpText .= (isset($addressFormatedValues[$pattern]) && !empty($addressFormatedValues[$pattern])) ?
                        (((isset($style[$pattern])) ?
                            (sprintf($style[$pattern], $addressFormatedValues[$pattern])) :
                            $addressFormatedValues[$pattern]) . $separator) : '';
                    }

                }

                $tmpText = trim($tmpText);
                $addressText .= (!empty($tmpText)) ? $tmpText . $newLine : '';
            }

        }

        $addressText = preg_replace('/' . preg_quote($newLine, '/') . '$/i', '', $addressText);
        $addressText = rtrim($addressText, $separator);

        return $addressText;
    }

    public static function generateAddressSmarty($params, $smarty) {

        return AddressFormat::generateAddress(
            $params['address'],
            (isset($params['patternRules']) ? $params['patternRules'] : []),
            (isset($params['newLine']) ? $params['newLine'] : "\r\n"),
            (isset($params['separator']) ? $params['separator'] : ' '),
            (isset($params['style']) ? $params['style'] : [])
        );
    }

    public static function getValidateFields($className) {

        $propertyList = [];

        if (class_exists($className)) {
            $object = new $className();
            $reflect = new ReflectionObject($object);

            // Check if the property is accessible
            $publicProperties = $reflect->getProperties(ReflectionProperty::IS_PUBLIC);

            foreach ($publicProperties as $property) {
                $propertyName = $property->getName();

                if ((!in_array($propertyName, AddressFormat::$forbiddenPropertyList)) &&
                    (!preg_match('#id|id_\w#', $propertyName))
                ) {
                    $propertyList[] = $propertyName;
                }

            }

            unset($object);
            unset($reflect);
        }

        return $propertyList;
    }

    public static function getLiableClass($className) {

        $objectList = [];

        if (class_exists($className)) {
            $object = new $className();
            $reflect = new ReflectionObject($object);

            // Get all the name object liable to the Address class
            $publicProperties = $reflect->getProperties(ReflectionProperty::IS_PUBLIC);

            foreach ($publicProperties as $property) {
                $propertyName = $property->getName();

                if (preg_match('#id_\w#', $propertyName) && strlen($propertyName) > 3) {
                    $nameObject = ucfirst(substr($propertyName, 3));

                    if (!in_array($nameObject, static::$forbiddenClassList) &&
                        class_exists($nameObject)
                    ) {
                        $objectList[$nameObject] = new $nameObject();
                    }

                }

            }

            unset($object);
            unset($reflect);
        }

        return $objectList;
    }

    public static function getOrderedAddressFields($idCountry = 0, $splitAll = false, $cleaned = false) {

        $out = [];
        $fieldSet = explode("\n", AddressFormat::getAddressCountryFormat($idCountry));

        foreach ($fieldSet as $fieldItem) {

            if ($splitAll) {

                if ($cleaned) {
                    $keyList = ($cleaned) ? preg_split(static::_CLEANING_REGEX_, $fieldItem, -1, PREG_SPLIT_NO_EMPTY) :
                    explode(' ', $fieldItem);
                }

                if (isset($keyList)) {

                    foreach ($keyList as $wordItem) {
                        $out[] = trim($wordItem);
                    }

                }

            } else {
                $out[] = ($cleaned) ? implode(' ', preg_split(static::_CLEANING_REGEX_, trim($fieldItem), -1, PREG_SPLIT_NO_EMPTY))
                : trim($fieldItem);
            }

        }

        return $out;
    }

    public static function getFormattedLayoutData($address) {

        $layoutData = [];

        if ($address && $address instanceof Address) {

            if (Plugin::isInstalled('vatnumber')
                && Plugin::isEnabled('vatnumber')
                && file_exists(_EPH_PLUGIN_DIR_ . 'vatnumber/vatnumber.php')) {
                include_once _EPH_PLUGIN_DIR_ . 'vatnumber/vatnumber.php';

                if (method_exists('VatNumber', 'adjustAddressForLayout')) {
                    VatNumber::adjustAddressForLayout($address);
                }

            }

            $layoutData['ordered'] = AddressFormat::getOrderedAddressFields((int) $address->id_country);
            $layoutData['formated'] = AddressFormat::getFormattedAddressFieldsValues($address, $layoutData['ordered']);
            $layoutData['object'] = [];

            $reflect = new ReflectionObject($address);
            $publicProperties = $reflect->getProperties(ReflectionProperty::IS_PUBLIC);

            foreach ($publicProperties as $property) {

                if (isset($address->{$property->getName()})) {
                    $layoutData['object'][$property->getName()] = $address->{$property->getName()};
                }

            }

        }

        return $layoutData;
    }

    public static function getAddressCountryFormat($idCountry = 0) {

        $idCountry = (int) $idCountry;

        $tmpObj = new AddressFormat();
        $tmpObj->id_country = $idCountry;
        $out = $tmpObj->getFormat($tmpObj->id_country);
        unset($tmpObj);

        return $out;
    }

    public function getFormat($idCountry) {

        $out = $this->_getFormatDB($idCountry);

        if (empty($out)) {
            $out = $this->_getFormatDB($this->context->phenyxConfig->get('EPH_COUNTRY_DEFAULT'));
        }

        return $out;
    }

    protected function _getFormatDB($idCountry) {

        if (!CacheApi::isStored('AddressFormat::_getFormatDB' . $idCountry)) {
            $format = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
                (new DbQuery())
                    ->select('`format`')
                    ->from(bqSQL(static::$definition['table']))
                    ->where('`id_country` = ' . (int) $idCountry)
            );
            $format = trim($format);
            CacheApi::store('AddressFormat::_getFormatDB' . $idCountry, $format);

            return $format;
        }

        return CacheApi::retrieve('AddressFormat::_getFormatDB' . $idCountry);
    }

    public static function getFieldsRequired() {

        $address = new Address;

        return array_unique(array_merge($address->getFieldsRequiredDB(), AddressFormat::$requireFormFieldsList));
    }

}