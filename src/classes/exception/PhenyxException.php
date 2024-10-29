<?php

/**
 * Class PhenyxException
 *
 * @since 1.9.1.0
 */
class PhenyxException extends Exception {

    protected $trace;

    /**
     * PhenyxExceptionCore constructor.
     *
     * @param string         $message
     * @param int            $code
     * @param Exception|null $previous
     * @param array|null     $customTrace
     * @param string|null    $file
     * @param int|null       $line
     */
    public function __construct($message = '', $code = 0, Exception $previous = null, $customTrace = null, $file = null, $line = null) {

        parent::__construct($message, $code, $previous);

        if (!$customTrace) {
            $this->trace = $this->getTrace();
        } else {
            $this->trace = $customTrace;
        }

        if ($file) {
            $this->file = $file;
        }

        if ($line) {
            $this->line = $line;
        }

    }

    /**
     * This method acts like an error handler, if dev mode is on, display the error else use a better silent way
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function displayMessage() {

        header('HTTP/1.1 500 Internal Server Error');

        if (_EPH_MODE_DEV_ || getenv('CI')) {
            // Display error message

            echo '<link rel="stylesheet" href="/content/backoffice/blacktie/css/exception.css" type="text/css" media="all" />';
            echo '<div id="ephException"><table id="table_exception" width="100%" border="1">
    <tbody>
        <tr>
            <td><img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAMgAAABkCAYAAADDhn8LAAAACXBIWXMAAAsTAAALEwEAmpwYAAASOGlUWHRYTUw6Y29tLmFkb2JlLnhtcAAAAAAAPD94cGFja2V0IGJlZ2luPSLvu78iIGlkPSJXNU0wTXBDZWhpSHpyZVN6TlRjemtjOWQiPz4gPHg6eG1wbWV0YSB4bWxuczp4PSJhZG9iZTpuczptZXRhLyIgeDp4bXB0az0iQWRvYmUgWE1QIENvcmUgOS4xLWMwMDIgNzkuYTZhNjM5NiwgMjAyNC8wMy8xMi0wNzo0ODoyMyAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6ZGM9Imh0dHA6Ly9wdXJsLm9yZy9kYy9lbGVtZW50cy8xLjEvIiB4bWxuczpwaG90b3Nob3A9Imh0dHA6Ly9ucy5hZG9iZS5jb20vcGhvdG9zaG9wLzEuMC8iIHhtbG5zOnB1cj0iaHR0cDovL3ByaXNtc3RhbmRhcmQub3JnL25hbWVzcGFjZXMvcHJpc211c2FnZXJpZ2h0cy8yLjEvIiB4bWxuczp4bXA9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC8iIHhtbG5zOnhtcE1NPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvbW0vIiB4bWxuczpzdEV2dD0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL3NUeXBlL1Jlc291cmNlRXZlbnQjIiB4bWxuczp0aWZmPSJodHRwOi8vbnMuYWRvYmUuY29tL3RpZmYvMS4wLyIgZGM6Zm9ybWF0PSJpbWFnZS9wbmciIHBob3Rvc2hvcDpDb3VudHJ5PSJSdXNzaWFuIEZlZGVyYXRpb24iIHBob3Rvc2hvcDpDcmVkaXQ9InRpZW5hIC0gc3RvY2suYWRvYmUuY29tIiBwaG90b3Nob3A6U291cmNlPSI2MTkwODgwODUiIHBob3Rvc2hvcDpDb2xvck1vZGU9IjMiIHhtcDpNZXRhZGF0YURhdGU9IjIwMjQtMDktMjJUMDY6MDU6MzcrMDI6MDAiIHhtcDpDcmVhdGVEYXRlPSIyMDI0LTA5LTIyVDA2OjAxOjMwKzAyOjAwIiB4bXA6TW9kaWZ5RGF0ZT0iMjAyNC0wOS0yMlQwNjowNTozNyswMjowMCIgeG1wTU06RG9jdW1lbnRJRD0iYWRvYmU6ZG9jaWQ6cGhvdG9zaG9wOjhmNzhmMzBkLWVlZDQtMjg0Yy05MjcyLWI2YWMzZjc0M2QxOSIgeG1wTU06SW5zdGFuY2VJRD0ieG1wLmlpZDpmNTE0NzIyZi0yMzhlLTFhNDUtYjRmZS01OWZiMjQ0NDEyNjIiIHhtcE1NOk9yaWdpbmFsRG9jdW1lbnRJRD0ieG1wLmRpZDo0NjBiZDlhMC0xODM1LTQ2MjgtODMzMS02NTQ0NTRkYTQ1YTMiIHRpZmY6SW1hZ2VXaWR0aD0iNTAwMCIgdGlmZjpJbWFnZUxlbmd0aD0iNTAwMCIgdGlmZjpYUmVzb2x1dGlvbj0iMzAwLzEiIHRpZmY6WVJlc29sdXRpb249IjMwMC8xIiB0aWZmOlJlc29sdXRpb25Vbml0PSIyIj4gPGRjOmRlc2NyaXB0aW9uPiA8cmRmOkFsdD4gPHJkZjpsaSB4bWw6bGFuZz0ieC1kZWZhdWx0Ij5Db21pY3MgdGV4dCBzb3VuZCBlZmZlY3RzLiBCdWJibGUgc3BlZWNoIHBocmFzZSBPb29wcy4gVmVjdG9yIGlsbHVzdHJhdGlvbjwvcmRmOmxpPiA8L3JkZjpBbHQ+IDwvZGM6ZGVzY3JpcHRpb24+IDxkYzpzdWJqZWN0PiA8cmRmOkJhZz4gPHJkZjpsaT5vb29wczwvcmRmOmxpPiA8cmRmOmxpPmJ1YmJsZTwvcmRmOmxpPiA8cmRmOmxpPmNvbWljPC9yZGY6bGk+IDxyZGY6bGk+Y2FydG9vbjwvcmRmOmxpPiA8cmRmOmxpPmV4cHJlc3Npb248L3JkZjpsaT4gPHJkZjpsaT5hcnQ8L3JkZjpsaT4gPHJkZjpsaT5zcGVlY2g8L3JkZjpsaT4gPHJkZjpsaT5pbGx1c3RyYXRpb248L3JkZjpsaT4gPHJkZjpsaT5leHBsb3Npb248L3JkZjpsaT4gPHJkZjpsaT50ZXh0PC9yZGY6bGk+IDxyZGY6bGk+YmFuZzwvcmRmOmxpPiA8cmRmOmxpPmRlc2lnbjwvcmRmOmxpPiA8cmRmOmxpPmljb248L3JkZjpsaT4gPHJkZjpsaT5sYWJlbDwvcmRmOmxpPiA8cmRmOmxpPmNsb3VkPC9yZGY6bGk+IDxyZGY6bGk+cG9wPC9yZGY6bGk+IDxyZGY6bGk+d29yZDwvcmRmOmxpPiA8cmRmOmxpPmVsZW1lbnQ8L3JkZjpsaT4gPHJkZjpsaT52ZWN0b3I8L3JkZjpsaT4gPHJkZjpsaT5zdHlsZTwvcmRmOmxpPiA8cmRmOmxpPmJvb2s8L3JkZjpsaT4gPHJkZjpsaT5yZXRybzwvcmRmOmxpPiA8cmRmOmxpPmh1bW9yPC9yZGY6bGk+IDxyZGY6bGk+c291bmQ8L3JkZjpsaT4gPHJkZjpsaT5iYWNrZ3JvdW5kPC9yZGY6bGk+IDxyZGY6bGk+Y29tbXVuaWNhdGlvbjwvcmRmOmxpPiA8cmRmOmxpPnN5bWJvbDwvcmRmOmxpPiA8cmRmOmxpPmJ1cnN0PC9yZGY6bGk+IDxyZGY6bGk+c2tldGNoPC9yZGY6bGk+IDxyZGY6bGk+ZHJhd2luZzwvcmRmOmxpPiA8cmRmOmxpPmZ1bm55PC9yZGY6bGk+IDxyZGY6bGk+aGFsZnRvbmU8L3JkZjpsaT4gPHJkZjpsaT5hYnN0cmFjdDwvcmRmOmxpPiA8cmRmOmxpPm9vcHM8L3JkZjpsaT4gPHJkZjpsaT5jb2xvcmZ1bDwvcmRmOmxpPiA8cmRmOmxpPm1lc3NhZ2U8L3JkZjpsaT4gPHJkZjpsaT5zcGVhazwvcmRmOmxpPiA8cmRmOmxpPnZpbnRhZ2U8L3JkZjpsaT4gPHJkZjpsaT5pZGVhPC9yZGY6bGk+IDxyZGY6bGk+ZG90PC9yZGY6bGk+IDxyZGY6bGk+c3RhcjwvcmRmOmxpPiA8cmRmOmxpPnBvc3RlcjwvcmRmOmxpPiA8cmRmOmxpPmNvbW11bmljYXRlPC9yZGY6bGk+IDxyZGY6bGk+cG9wYXJ0PC9yZGY6bGk+IDxyZGY6bGk+cG9wIGFydDwvcmRmOmxpPiA8cmRmOmxpPmV4Y2xhbWF0aW9uPC9yZGY6bGk+IDxyZGY6bGk+YnJpZ2h0PC9yZGY6bGk+IDxyZGY6bGk+c3VycHJpc2VkPC9yZGY6bGk+IDxyZGY6bGk+c21hc2g8L3JkZjpsaT4gPHJkZjpsaT5waHJhc2U8L3JkZjpsaT4gPHJkZjpsaT5vb29wczwvcmRmOmxpPiA8cmRmOmxpPmJ1YmJsZTwvcmRmOmxpPiA8cmRmOmxpPmNvbWljPC9yZGY6bGk+IDxyZGY6bGk+Y2FydG9vbjwvcmRmOmxpPiA8cmRmOmxpPmV4cHJlc3Npb248L3JkZjpsaT4gPHJkZjpsaT5hcnQ8L3JkZjpsaT4gPHJkZjpsaT5zcGVlY2g8L3JkZjpsaT4gPHJkZjpsaT5pbGx1c3RyYXRpb248L3JkZjpsaT4gPHJkZjpsaT5leHBsb3Npb248L3JkZjpsaT4gPHJkZjpsaT50ZXh0PC9yZGY6bGk+IDxyZGY6bGk+YmFuZzwvcmRmOmxpPiA8cmRmOmxpPmRlc2lnbjwvcmRmOmxpPiA8cmRmOmxpPmljb248L3JkZjpsaT4gPHJkZjpsaT5sYWJlbDwvcmRmOmxpPiA8cmRmOmxpPmNsb3VkPC9yZGY6bGk+IDxyZGY6bGk+cG9wPC9yZGY6bGk+IDxyZGY6bGk+d29yZDwvcmRmOmxpPiA8cmRmOmxpPmVsZW1lbnQ8L3JkZjpsaT4gPHJkZjpsaT52ZWN0b3I8L3JkZjpsaT4gPHJkZjpsaT5zdHlsZTwvcmRmOmxpPiA8cmRmOmxpPmJvb2s8L3JkZjpsaT4gPHJkZjpsaT5yZXRybzwvcmRmOmxpPiA8cmRmOmxpPmh1bW9yPC9yZGY6bGk+IDxyZGY6bGk+c291bmQ8L3JkZjpsaT4gPHJkZjpsaT5iYWNrZ3JvdW5kPC9yZGY6bGk+IDxyZGY6bGk+Y29tbXVuaWNhdGlvbjwvcmRmOmxpPiA8cmRmOmxpPnN5bWJvbDwvcmRmOmxpPiA8cmRmOmxpPmJ1cnN0PC9yZGY6bGk+IDxyZGY6bGk+c2tldGNoPC9yZGY6bGk+IDxyZGY6bGk+ZHJhd2luZzwvcmRmOmxpPiA8cmRmOmxpPmZ1bm55PC9yZGY6bGk+IDxyZGY6bGk+aGFsZnRvbmU8L3JkZjpsaT4gPHJkZjpsaT5hYnN0cmFjdDwvcmRmOmxpPiA8cmRmOmxpPm9vcHM8L3JkZjpsaT4gPHJkZjpsaT5jb2xvcmZ1bDwvcmRmOmxpPiA8cmRmOmxpPm1lc3NhZ2U8L3JkZjpsaT4gPHJkZjpsaT5zcGVhazwvcmRmOmxpPiA8cmRmOmxpPnZpbnRhZ2U8L3JkZjpsaT4gPHJkZjpsaT5pZGVhPC9yZGY6bGk+IDxyZGY6bGk+ZG90PC9yZGY6bGk+IDxyZGY6bGk+c3RhcjwvcmRmOmxpPiA8cmRmOmxpPnBvc3RlcjwvcmRmOmxpPiA8cmRmOmxpPmNvbW11bmljYXRlPC9yZGY6bGk+IDxyZGY6bGk+cG9wYXJ0PC9yZGY6bGk+IDxyZGY6bGk+cG9wIGFydDwvcmRmOmxpPiA8cmRmOmxpPmV4Y2xhbWF0aW9uPC9yZGY6bGk+IDxyZGY6bGk+YnJpZ2h0PC9yZGY6bGk+IDxyZGY6bGk+c3VycHJpc2VkPC9yZGY6bGk+IDxyZGY6bGk+c21hc2g8L3JkZjpsaT4gPC9yZGY6QmFnPiA8L2RjOnN1YmplY3Q+IDxkYzp0aXRsZT4gPHJkZjpBbHQ+IDxyZGY6bGkgeG1sOmxhbmc9IngtZGVmYXVsdCI+Q29taWNzIHRleHQgc291bmQgZWZmZWN0cy4gQnViYmxlIHNwZWVjaCBwaHJhc2UgT29vcHMuIFZlY3RvciBpbGx1c3RyYXRpb248L3JkZjpsaT4gPC9yZGY6QWx0PiA8L2RjOnRpdGxlPiA8cHVyOmNyZWRpdExpbmU+IDxyZGY6QmFnPiA8cmRmOmxpPnRpZW5hIC0gc3RvY2suYWRvYmUuY29tPC9yZGY6bGk+IDwvcmRmOkJhZz4gPC9wdXI6Y3JlZGl0TGluZT4gPHhtcE1NOkhpc3Rvcnk+IDxyZGY6U2VxPiA8cmRmOmxpIHN0RXZ0OmFjdGlvbj0ic2F2ZWQiIHN0RXZ0Omluc3RhbmNlSUQ9InhtcC5paWQ6MTlmZjY5NzEtY2YxYi1iYTQyLWIyNjgtNjVlOGUxZWZjZmM5IiBzdEV2dDp3aGVuPSIyMDI0LTA5LTIyVDA2OjA1OjM3KzAyOjAwIiBzdEV2dDpzb2Z0d2FyZUFnZW50PSJBZG9iZSBQaG90b3Nob3AgMjUuOSAoV2luZG93cykiIHN0RXZ0OmNoYW5nZWQ9Ii8iLz4gPHJkZjpsaSBzdEV2dDphY3Rpb249InNhdmVkIiBzdEV2dDppbnN0YW5jZUlEPSJ4bXAuaWlkOmY1MTQ3MjJmLTIzOGUt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"></td>
        </tr>
        <tr>
            <td>
';
            echo '<h2>[' . str_replace('EphenyxShop', 'Ephenyx', get_class($this)) . ']</h2>';
            echo $this->getExtendedMessage();

            echo $this->displayFileDebug($this->file, $this->line);

            // Display debug backtrace
            echo '<ul>';

            foreach ($this->trace as $id => $trace) {
                $relativeFile = (isset($trace['file'])) ? ltrim(str_replace([_EPH_ROOT_DIR_, '\\'], ['', '/'], $trace['file']), '/') : '';
                $currentLine = (isset($trace['line'])) ? $trace['line'] : '';

                if (defined('_EPH_ROOT_DIR_')) {
                    $relativeFile = str_replace(basename(_EPH_ROOT_DIR_) . DIRECTORY_SEPARATOR, 'admin' . DIRECTORY_SEPARATOR, $relativeFile);
                }

                echo '<li>';
                echo '<b>' . ((isset($trace['class'])) ? $trace['class'] : '') . ((isset($trace['type'])) ? $trace['type'] : '') . $trace['function'] . '</b>';
                echo ' - <a style="font-size: 12px; color: #000000; cursor:pointer; color: blue;" onclick="document.getElementById(\'ephTrace_' . $id . '\').style.display = (document.getElementById(\'ephTrace_' . $id . '\').style.display != \'block\') ? \'block\' : \'none\'; return false">[line ' . $currentLine . ' - ' . $relativeFile . ']</a>';

                if (isset($trace['args']) && count($trace['args'])) {
                    echo ' - <a style="font-size: 12px; color: #000000; cursor:pointer; color: blue;" onclick="document.getElementById(\'ephArgs_' . $id . '\').style.display = (document.getElementById(\'ephArgs_' . $id . '\').style.display != \'block\') ? \'block\' : \'none\'; return false">[' . count($trace['args']) . ' Arguments]</a>';
                }

                if ($relativeFile) {
                    $this->displayFileDebug($trace['file'], $trace['line'], $id);
                }

                if (isset($trace['args']) && count($trace['args'])) {
                    $this->displayArgsDebug($trace['args'], $id);
                }

                echo '</li>';
            }

            echo '</ul>';
            echo '</td>
        </tr>
    </tbody>
</table></div>';
        } else {
            header('Content-Type: text/plain; charset=UTF-8');
            // Display error message
            $markdown = '';
            $markdown .= '## ' . str_replace('EphenyxShop', 'Ephenyx', get_class($this)) . '  ';
            $markdown .= $this->getExtendedMessageMarkdown();

            $markdown .= $this->displayFileDebug($this->file, $this->line, null, true);

            // Display debug backtrace

            foreach ($this->trace as $id => $trace) {
                $relativeFile = (isset($trace['file'])) ? ltrim(str_replace([_EPH_ROOT_DIR_, '\\'], ['', '/'], $trace['file']), '/') : '';
                $currentLine = (isset($trace['line'])) ? $trace['line'] : '';

                if (defined('_EPH_ROOT_DIR_')) {
                    $relativeFile = str_replace(basename(_EPH_ROOT_DIR_) . DIRECTORY_SEPARATOR, 'content' . DIRECTORY_SEPARATOR, $relativeFile);
                }

                $markdown .= '- ';
                $markdown .= '**' . ((isset($trace['class'])) ? $trace['class'] : '') . ((isset($trace['type'])) ? $trace['type'] : '') . $trace['function'] . '**';
                $markdown .= " - [line `" . $currentLine . '` - `' . $relativeFile . "`]  \n";

                if (isset($trace['args']) && count($trace['args'])) {
                    $markdown .= " - [" . count($trace['args']) . " Arguments]  \n";
                }

                if ($relativeFile) {
                    $markdown .= $this->displayFileDebug($trace['file'], $trace['line'], $id, true);
                }

                if (isset($trace['args']) && count($trace['args'])) {
                    $markdown .= $this->displayArgsDebug($trace['args'], $id, true);
                }

            }

            header('Content-Type: text/html');
            $markdown = Encryptor::getInstance()->encrypt($markdown);

            echo $this->displayErrorTemplate(_EPH_ROOT_DIR_ . '/error500.phtml', ['markdown' => $markdown]);
        }

        // Log the error to the disk
        $this->logError();
        exit;
    }

    /**
     * Display lines around current line
     *
     * Markdown is returned instead of being printed
     * (HTML is printed because old backwards stuff blabla)
     *
     * @param string $file
     * @param int    $line
     * @param string $id
     * @param bool   $markdown
     *
     * @return string
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @version 1.0.1 Add markdown support - return string
     */
    protected function displayFileDebug($file, $line, $id = null, $markdown = false) {

        $lines = (array) file($file);
        $offset = $line - 6;
        $total = 11;

        if ($offset < 0) {
            $total += $offset;
            $offset = 0;
        }

        $lines = array_slice($lines, $offset, $total);
        ++$offset;

        $ret = '';

        if ($markdown) {
            $ret .= "```php  \n";

            foreach ($lines as $k => $l) {
                $ret .= ($offset + $k) . '. ' . (($offset + $k == $line) ? '=>' : '  ') . ' ' . $l;
            }

            $ret .= "```  \n";
        } else {
            echo '<div class="ephTrace" id="ephTrace_' . $id . '" ' . ((is_null($id) ? 'style="display: block"' : '')) . '><pre>';

            foreach ($lines as $k => $l) {
                $string = ($offset + $k) . '. ' . htmlspecialchars($l);

                if ($offset + $k == $line) {
                    echo '<span class="selected">' . $string . '</span>';
                } else {
                    echo $string;
                }

            }

            echo '</pre></div>';
        }

        return $ret;
    }

    /**
     * Display arguments list of traced function
     * Markdown is returned instead of being printed
     * (HTML is printed because old backwards stuff blabla)
     *
     * @param array  $args List of arguments
     * @param string $id ID of argument
     * @param bool   $markdown
     *
     * @return string
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @version 1.0.1 Add markdown support - return string
     */
    protected function displayArgsDebug($args, $id, $markdown = false) {

        $ret = '';

        if ($markdown) {
            $ret .= '```';

            foreach ($args as $arg => $value) {
                $ret .= 'Argument [' . Tools::safeOutput($arg) . "]  \n";
                $ret .= Tools::safeOutput(print_r($value, true));
                $ret .= "\n";
            }

            $ret .= "```  \n";
        } else {
            echo '<div class="ephArgs" id="ephArgs_' . $id . '"><pre>';

            foreach ($args as $arg => $value) {
                echo '<b>Argument [' . Tools::safeOutput($arg) . "]</b>\n";
                echo Tools::safeOutput(print_r($value, true));
                echo "\n";
            }

            echo '</pre>';
        }

        return $ret;
    }

    /**
     * Log the error on the disk
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    protected function logError() {

        $logger = new FileLogger();
        $logger->setFilename(_EPH_ROOT_DIR_ . '/log/' . date('Ymd') . '_exception.log');
        $logger->logError($this->getExtendedMessage(false));
    }

    /**
     * @deprecated 2.0.0
     */
    protected function getExentedMessage($html = true) {

        Tools::displayAsDeprecated();

        return $this->getExtendedMessage($html);
    }

    /**
     * Return the content of the Exception
     * @return string content of the exception.
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    protected function getExtendedMessage($html = true) {

        $format = '<p><b>%s</b><br /><i>at line </i><b>%d</b><i> in file </i><b>%s</b></p>';

        if (!$html) {
            $format = strip_tags(str_replace('<br />', ' ', $format));
        }

        return sprintf(
            $format,
            $this->getMessage(),
            $this->getLine(),
            ltrim(str_replace([_EPH_ROOT_DIR_, '\\'], ['', '/'], $this->getFile()), '/')
        );
    }

    /**
     * Return the content of the Exception
     * @return string content of the exception.
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    protected function getExtendedMessageMarkdown() {

        $format = "\n**%s**  \n *at line* **%d** *in file* `%s`  \n";

        return sprintf(
            $format,
            $this->getMessage(),
            $this->getLine(),
            ltrim(str_replace([_EPH_ROOT_DIR_, '\\'], ['', '/'], $this->getFile()), '/')
        );
    }

    /**
     * Display a phtml template file
     *
     * @param string $file
     * @param array  $params
     *
     * @return string Content
     *
     * @since 1.9.1.0
     */
    protected function displayErrorTemplate($file, $params = []) {

        foreach ($params as $name => $param) {
            $$name = $param;
        }

        ob_start();

        include $file;

        $content = ob_get_contents();

        if (ob_get_level() && ob_get_length() > 0) {
            ob_end_clean();
        }

        return $content;
    }

}
