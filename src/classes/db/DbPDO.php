<?php

/**
 * Class DbPDO
 *
 * @since 1.9.1.0
 */
class DbPDO extends Db {

    /** @var PDO */
    protected $link;

    /* @var PDOStatement */
    protected $result;

    /**
     * Returns a new PDO object (database link)
     *
     * @param string $host
     * @param string $user
     * @param string $password
     * @param string $dbname
     * @param int $timeout
     *
     * @return PDO
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    protected static function _getPDO($host, $user, $password, $dbname, $timeout = 5) {

        $dsn = 'mysql:';

        if ($dbname) {
            $dsn .= 'dbname=' . $dbname . ';';
        }

        if (preg_match('/^(.*):([0-9]+)$/', $host, $matches)) {
            $dsn .= 'host=' . $matches[1] . ';port=' . $matches[2];
        } else

        if (preg_match('#^.*:(/.*)$#', $host, $matches)) {
            $dsn .= 'unix_socket=' . $matches[1];
        } else {
            $dsn .= 'host=' . $host;
        }

        return new PDO($dsn, $user, $password, [PDO::ATTR_TIMEOUT => $timeout, PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true]);
    }

    /**
     * Tries to connect and create a new database
     *
     * @param string $host
     * @param string $user
     * @param string $password
     * @param string $dbname
     * @param bool $dropit If true, drops the created database.
     *
     * @return bool|int
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function createDatabase($host, $user, $password, $dbname, $dropit = false) {

        try {
            $link = static::_getPDO($host, $user, $password, false);
            $success = $link->exec('CREATE DATABASE `' . str_replace('`', '\\`', $dbname) . '`');

            if ($dropit && ($link->exec('DROP DATABASE `' . str_replace('`', '\\`', $dbname) . '`') !== false)) {
                return true;
            }

        } catch (PDOException $e) {
            return false;
        }

        return $success;
    }

    /**
     * Tries to connect to the database
     *
     * @see DbCore::connect()
     * @return PDO
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function connect() {

        try {
            $this->link = $this->_getPDO($this->server, $this->user, $this->password, $this->database, 5);
        } catch (PDOException $e) {
            die(sprintf(Tools::displayError('Link to database cannot be established: %s'), mb_convert_encoding($e->getMessage(), 'UTF-8', 'ISO-8859-1')));
        }

        // UTF-8 support

        if ($this->link->exec('SET NAMES \'utf8mb4\'') === false) {
            die(Tools::displayError('ephenyx Fatal error: no UTF-8 support. Please check your server configuration.'));
        }

        $this->link->exec('SET SESSION sql_mode = \'\'');

        return $this->link;
    }

    /**
     * Destroys the database connection link
     *
     * @see DbCore::disconnect()
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function disconnect() {

        unset($this->link);
    }

    /**
     * Executes an SQL statement, returning a result set as a PDOStatement object or true/false.
     *
     * @see DbCore::_query()
     * @param string $sql
     *
     * @return PDOStatement
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    protected function _query($sql) {

        try {
            return isset($this->link) ? $this->link->query($sql) : null;
        } catch (PhenyxException $e) {
            PhenyxLogger::addLog($e->displayMessage() . ' for query : <br>' . $sql, 4);
            return false;
        }

        //return isset($this->link) ? $this->link->query($sql) : null;

    }

    /**
     * Returns the next row from the result set.
     *
     * @see DbCore::nextRow()
     * @param bool $result
     *
     * @return array|false|null
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function nextRow($result = false) {

        if (!$result) {
            $result = $this->result;
        }

        if (!is_object($result)) {
            return false;
        }

        return $result->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Returns all rows from the result set.
     *
     * @see DbCore::getAll()
     * @param bool $result
     * @return array|false|null
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    protected function getAll($result = false) {

        if (!$result) {
            $result = $this->result;
        }

        if (!is_object($result)) {
            return false;
        }

        return $result->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Returns row count from the result set.
     *
     * @see DbCore::_numRows()
     * @param PDOStatement $result
     * @return int
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    protected function _numRows($result) {

        return $result->rowCount();
    }

    /**
     * Returns ID of the last inserted row.
     *
     * @see DbCore::Insert_ID()
     * @return string|int
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function Insert_ID() {

        return $this->link->lastInsertId();
    }

    /**
     * Return the number of rows affected by the last SQL query.
     *
     * @see DbCore::Affected_Rows()
     * @return int
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function Affected_Rows() {

        return $this->result->rowCount();
    }

    /**
     * Returns error message.
     *
     * @see DbCore::getMsgError()
     * @param bool $query
     * @return string
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function getMsgError($query = false) {

        $error = $this->link->errorInfo();

        return ($error[0] == '00000') ? '' : $error[2];
    }

    /**
     * Returns error code.
     *
     * @see DbCore::getNumberError()
     * @return int
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function getNumberError() {

        $error = isset($this->link) ? $this->link->errorInfo() : null;

        return isset($error[1]) ? $error[1] : 0;
    }

    /**
     * Returns database server version.
     *
     * @see     DbCore::getVersion()
     * @return string
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxException
     */
    public function getVersion() {

        return $this->getValue('SELECT VERSION()');
    }

    public function escape_by_ref(&$string) {

        if (!is_float($string)) {
            $string = $this->_real_escape($string);
        }

    }

    public function _real_escape($string) {

        if (is_int($string) || is_float($string)) {
            return $string;
        }

        $string = str_replace("'", "''", $string);
        return addcslashes($string, "\000\n\r\\\032");

    }

    /**
     * Escapes illegal characters in a string.
     *
     * @see DbCore::_escape()
     * @param string $str
     * @return string
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function _escape($str) {

        if (!is_null($str) && !is_array($str) && !is_object($str)) {
            $search = ["\\", "\0", "\n", "\r", "\x1a", "'", '"'];
            $replace = ["\\\\", "\\0", "\\n", "\\r", "\Z", "‘", '\"'];

            return str_replace($search, $replace, $str);
        }

    }

    public function _translate_escape($str) {

        if (!is_null($str) && !is_array($str) && !is_object($str)) {
            $search = ["'", '&#39;'];
            $replace = ["‘", '‘'];

            return str_replace($search, $replace, $str);
        }

    }

    public function _sescape($str) {

        if (!is_null($str)) {
            $search = ["\\", "\0", "\n", "\r", "\x1a", "'", '"'];
            $replace = ["\\\\", "\\0", "\\n", "\\r", "\Z", "\'", '\"'];

            return str_replace($search, $replace, $str);
        }

    }

    /**
     * Switches to a different database.
     *
     * @see DbCore::set_db()
     *
     * @param string $dbName
     *
     * @return int
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function set_db($dbName) {

        return $this->link->exec('USE ' . pSQL($dbName));
    }

    /**
     * Try a connection to the database and check if at least one table with same prefix exists
     *
     * @see Db::hasTableWithSamePrefix()
     * @param string $server Server address
     * @param string $user   Login for database connection
     * @param string $pwd    Password for database connection
     * @param string $db     Database name
     * @param string $prefix Tables prefix
     * @return bool
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function hasTableWithSamePrefix($server, $user, $pwd, $db, $prefix) {

        try {
            $link = static::_getPDO($server, $user, $pwd, $db, 5);
        } catch (PDOException $e) {
            return false;
        }

        $sql = 'SHOW TABLES LIKE \'' . $prefix . '%\'';
        $result = $link->query($sql);

        return (bool) $result->fetch();
    }

    /**
     * Tries to connect to the database and create a table (checking creation privileges)
     *
     * @param string      $server
     * @param string      $user
     * @param string      $pwd
     * @param string      $db
     * @param string      $prefix
     * @param string|null $engine Table engine
     *
     * @return bool|string True, false or error
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function checkCreatePrivilege($server, $user, $pwd, $db, $prefix, $engine = null) {

        try {
            $link = static::_getPDO($server, $user, $pwd, $db, 5);
        } catch (PDOException $e) {
            return false;
        }

        if ($engine === null) {
            $engine = 'InnoDB';
        }

        $result = $link->query('
        CREATE TABLE `' . $prefix . 'test` (
            `test` tinyint(1) unsigned NOT NULL
        ) ENGINE=' . $engine);

        if (!$result) {
            $error = $link->errorInfo();

            return $error[2];
        }

        $link->query('DROP TABLE `' . $prefix . 'test`');

        return true;
    }

    /**
     * Try a connection to the database
     *
     * @see Db::checkConnection()
     *
     * @param string      $server Server address
     * @param string      $user   Login for database connection
     * @param string      $pwd    Password for database connection
     * @param string      $db     Database name
     * @param bool        $newDbLink
     * @param string|bool $engine
     * @param int         $timeout
     *
     * @return int Error code or 0 if connection was successful
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function tryToConnect($server, $user, $pwd, $db, $newDbLink = true, $engine = null, $timeout = 5) {

        try {
            $link = static::_getPDO($server, $user, $pwd, $db, $timeout);
        } catch (PDOException $e) {
            // hhvm wrongly reports error status 42000 when the database does not exist - might change in the future
            return ($e->getCode() == 1049 || (defined('HHVM_VERSION') && $e->getCode() == 42000)) ? 2 : 1;
        }

        unset($link);

        return 0;
    }

    /**
     * Selects best table engine.
     *
     * @return string
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function getBestEngine() {

        return 'InnoDB';
    }

    /**
     * Try a connection to the database and set names to UTF-8
     *
     * @see Db::checkEncoding()
     *
     * @param string $server Server address
     * @param string $user Login for database connection
     * @param string $pwd Password for database connection
     *
     * @return bool
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function tryUTF8($server, $user, $pwd) {

        try {
            $link = static::_getPDO($server, $user, $pwd, false, 5);
        } catch (PDOException $e) {
            return false;
        }

        $result = $link->exec('SET NAMES \'utf8mb4\'');
        unset($link);

        return ($result === false) ? false : true;
    }

    /**
     * Checks if auto increment value and offset is 1
     *
     * @param string $server
     * @param string $user
     * @param string $pwd
     * @return bool
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function checkAutoIncrement($server, $user, $pwd) {

        try {
            $link = static::_getPDO($server, $user, $pwd, false, 5);
        } catch (PDOException $e) {
            return false;
        }

        $ret = (bool) (($result = $link->query('SELECT @@auto_increment_increment as aii')) && ($row = $result->fetch()) && $row['aii'] == 1);
        $ret &= (bool) (($result = $link->query('SELECT @@auto_increment_offset as aio')) && ($row = $result->fetch()) && $row['aio'] == 1);
        unset($link);

        return $ret;
    }

    /**
     * Set timezone on current connection.
     *
     * @param string $timezone
     *
     * @since   1.0.7
     * @version 1.0.7 Initial version.
     */
    public function setTimeZone($timezone) {

        $now = new DateTime('now', new DateTimeZone($timezone));
        $minutes = $now->getOffset() / 60;
        $sign = ($minutes < 0 ? -1 : 1);
        $minutes = abs($minutes);
        $hours = floor($minutes / 60);
        $minutes -= $hours * 60;
        $offset = sprintf('%+d:%02d', $hours * $sign, $minutes);
        $this->link->exec("SET time_zone='$offset'");
    }

}
