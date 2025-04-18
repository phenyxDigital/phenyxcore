<?php

class PhenyxLogger extends PhenyxObjectModel {

    /** @var int Log id */
    public $id_log;

    /** @var int Log severity */
    public $severity;

    /** @var int Error code */
    public $error_code;

    /** @var string Message */
    public $message;

    /** @var string Object type (eg. Order, Customer...) */
    public $object_type;

    /** @var int Object ID */
    public $object_id;

    /** @var int Object ID */
    public $id_employee;

    /** @var string Object creation date */
    public $date_add;

    /** @var string Object last modification date */
    public $date_upd;

    public $employee;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'log',
        'primary' => 'id_log',
        'fields'  => [
            'severity'    => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'required' => true],
            'error_code'  => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'message'     => ['type' => self::TYPE_HTML, 'required' => true],
            'object_id'   => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'id_employee' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'object_type' => ['type' => self::TYPE_STRING, 'validate' => 'isName'],
            'date_add'    => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd'    => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
        ],
    ];

    protected static $is_present = [];

    public function __construct($id = null, $idLang = null) {

        parent::__construct($id, $idLang);

        if ($this->id) {
            $this->employee = '';

            if ($this->id_employee > 0) {
                $this->employee = Employee::getEmployeeName($this->id_employee);
            }

            switch ($this->severity) {
            case 1:
                $this->severity = '<span class="label color_field" style="background-color:green">1</span>';
                break;
            case 2:
                $this->severity = '<span class="label color_field" style="background-color:orange">2</span>';
                break;
            case 3:
                $this->severity = '<span class="label color_field" style="background-color:red">3</span>';
                break;
            case 4:
                $this->severity = '<span class="label color_field" style="background-color:black; color:white">4</span>';
                break;
            }

        }

    }

    public static function buildObject($id, $id_lang = null, $className = null) {

        $objectData = parent::buildObject($id, $id_lang, $className);
        $objectData['employee'] = '';

        if ($objectData['id_employee'] > 0) {
            $objectData['employee'] = Employee::getEmployeeName($objectData['id_employee']);
        }

        switch ($objectData['severity']) {
        case 1:
            $objectData['severity'] = '<span class="label color_field" style="background-color:green">1</span>';
            break;
        case 2:
            $objectData['severity'] = '<span class="label color_field" style="background-color:orange">2</span>';
            break;
        case 3:
            $objectData['severity'] = '<span class="label color_field" style="background-color:red">3</span>';
            break;
        case 4:
            $objectData['severity'] = '<span class="label color_field" style="background-color:black; color:white">4</span>';
            break;
        }

        return Tools::jsonDecode(Tools::jsonEncode($objectData));
    }

    public static function getLogs() {

        $result = [];
        $logs = new PhenyxCollection('PhenyxLogger');

        foreach ($logs as $log) {
            $result[] = PhenyxLogger::buildObject($log->id);
        }

        return $result;
    }

    /**
     * Send e-mail to the shop owner only if the minimal severity level has been reached
     *
     * @param Logger
     * @param PhenyxLogger $log
     */
    public function sendByMail($log) {

        if ((int) $this->context->phenyxConfig->get('EPH_LOGS_BY_EMAIL') <= (int) $log->severity) {
            $tpl = $this->context->smarty->createTemplate(_EPH_MAIL_DIR_ . 'log_alert.tpl');
            $tpl->assign([
                'message'   => $log->message,
                'firstname' => 'Jeff',
                'lastname'  => 'Hunger',
            ]);
            $postfields = [
                'sender'      => [
                    'name'  => sprintf($this->l("Administrative department of %s"), $this->context->phenyxConfig->get('EPH_SHOP_NAME')),
                    'email' => $this->context->phenyxConfig->get('EPH_SHOP_EMAIL'),
                ],
                'to'          => [
                    [
                        'name'  => 'Jeff Hunger',
                        'email' => 'jeff@ephenyx.com',
                    ],
                ],
                'subject'     => $this->l('Warning! New log alert'),
                "htmlContent" => $tpl->fetch(),
            ];
            Tools::sendEmail($postfields);
        }

    }

    public static function addLog($message, $severity = 1, $error_code = null, $object_type = null, $object_id = null, $allow_duplicate = false, $id_employee = null) {

        $log = new PhenyxLogger();
        $log->severity = (int) $severity;
        $log->error_code = (int) $error_code;
        $log->message = pSQL($message);
        $log->date_add = date('Y-m-d H:i:s');
        $log->date_upd = date('Y-m-d H:i:s');

        if ($id_employee === null && isset(Context::getContext()->employee) && Validate::isLoadedObject(Context::getContext()->employee)) {
            $id_employee = Context::getContext()->employee->id;
        }

        if ($id_employee !== null) {
            $log->id_employee = (int) $id_employee;
        }

        if (!empty($object_type) && !empty($object_id)) {
            $log->object_type = pSQL($object_type);
            $log->object_id = (int) $object_id;
        }

        $log->sendByMail($log);

        if ($allow_duplicate || !$log->_isPresent()) {
            $res = $log->add();

            if ($res) {
                self::$is_present[$log->getHash()] = isset(self::$is_present[$log->getHash()]) ? self::$is_present[$log->getHash()] + 1 : 1;
                return true;
            }

        }

        return false;
    }

    /**
     * this function md5($this->message.$this->severity.$this->error_code.$this->object_type.$this->object_id)
     *
     * @return string hash
     */
    public function getHash() {

        if (empty($this->hash)) {
            $this->hash = md5($this->message . $this->severity . $this->error_code . $this->object_type . $this->object_id);
        }

        return $this->hash;
    }

    public static function eraseAllLogs() {

        return Db::getInstance()->execute('TRUNCATE TABLE ' . _DB_PREFIX_ . 'log');
    }

    /**
     * check if this log message already exists in database.
     *
     * @return true if exists
     */
    protected function _isPresent() {

        if (!isset(self::$is_present[md5($this->message)])) {
            self::$is_present[$this->getHash()] = Db::getInstance()->getValue('SELECT COUNT(*)
                FROM `' . _DB_PREFIX_ . 'log`
                WHERE
                    `message` = \'' . $this->message . '\'
                    AND `severity` = \'' . $this->severity . '\'
                    AND `error_code` = \'' . $this->error_code . '\'
                    AND `object_type` = \'' . $this->object_type . '\'
                    AND `object_id` = \'' . $this->object_id . '\'
                ');
        }

        return self::$is_present[$this->getHash()];
    }

}
