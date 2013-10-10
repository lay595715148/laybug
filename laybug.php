<?php
/**
 * laysoft debug lib
 * @see https://github.com/lay595715148/laybug
 * 
 * @author Lay Li
 * @version: 1.0.0 (build 131010)
 */

if(!class_exists('Debugger', false)) {
    /**
     * Debug工具类
     * 
     * @author Lay Li
     */
    class Debugger {
        const DEBUG_LEVEL_DEBUG = 1;
        const DEBUG_LEVEL_INFO = 2;
        const DEBUG_LEVEL_WARN = 4;
        const DEBUG_LEVEL_ERROR = 8;
        const DEBUG_LEVEL_ALL = 15;
        public static $out = false;
        public static $log = false;
        /**
         * initialize Debugger
         * @return void
         */
        public static function initialize($debug = '') {
            if(is_bool($debug)) {
                self::$out = self::$log = $debug;
            } else if(is_array($debug)) {
                $debug['out'] = isset($debug['out']) ? $debug['out'] : isset($debug[0])?$debug[0]:false;
                $debug['log'] = isset($debug['log']) ? $debug['log'] : isset($debug[1])?$debug[1]:false;
                self::$out = ($debug['out'] === true)?true:intval($debug['out']);
                self::$log = ($debug['log'] === true)?true:intval($debug['log']);
            } else if(is_int($debug)) {
                self::$out = self::$log = $debug;
            } else if($debug === '') {
                $debug = Laywork::get('debug');
                if($debug === '' || $debug === null) {
                    self::$out = self::$log = false;
                } else {
                    self::initialize($debug);
                }
            } else {
                self::$out = self::$log = false;
            }
        }
        /**
         * print out debug infomation
         * @return void
         */
        public static function debug($msg, $tag = '', $line = '', $method = '', $class = '') {
            if(self::$out === true || (self::$out && in_array(self::$out, array(1, 3, 5, 7, 9, 11, 13, 15)))) {
                self::pre($msg, self::DEBUG_LEVEL_DEBUG, $tag, $line, $method, $class);
            }
            if(self::$log === true || (self::$log && in_array(self::$log, array(1, 3, 5, 7, 9, 11, 13, 15)))) {
                self::log(json_encode($msg), self::DEBUG_LEVEL_DEBUG, $tag, $line, $method, $class);
            }
        }
        /**
         * print out info infomation
         * @return void
         */
        public static function info($msg, $tag = '', $line = '', $method = '', $class = '') {
            if(self::$out === true || (self::$out && in_array(self::$out, array(2, 3, 6, 7, 10, 11, 14, 15)))) {
                self::out($msg, self::DEBUG_LEVEL_INFO, $tag, $line, $method, $class);
            }
            if(self::$log === true || (self::$log && in_array(self::$log, array(2, 3, 6, 7, 10, 11, 14, 15)))) {
                self::log($msg, self::DEBUG_LEVEL_INFO, $tag, $line, $method, $class);
            }
        }
        /**
         * print out warning infomation
         * @return void
         */
        public static function warning($msg, $tag = '', $line = '', $method = '', $class = '') {
            if(self::$out === true || (self::$out && in_array(self::$out, array(4, 5, 6, 7, 12, 13, 14, 15)))) {
                self::out($msg, self::DEBUG_LEVEL_WARN, $tag, $line, $method, $class);
            }
            if(self::$log === true || (self::$log && in_array(self::$log, array(4, 5, 6, 7, 12, 13, 14, 15)))) {
                self::log($msg, self::DEBUG_LEVEL_WARN, $tag, $line, $method, $class);
            }
        }
        /**
         * print out warning infomation
         * @return void
         */
        public static function warn($msg, $tag = '', $line = '', $method = '', $class = '') {
            self::warning($msg, $tag, $line, $method, $class);
        }
        /**
         * print out error infomation
         * @return void
         */
        public static function error($msg, $tag = '', $line = '', $method = '', $class = '') {
            if(self::$out === true || (self::$out && in_array(self::$out, array(8, 9, 10, 11, 12, 13, 14, 15)))) {
                self::out($msg, self::DEBUG_LEVEL_ERROR, $tag, $line, $method, $class);
            }
            if(self::$log === true || (self::$log && in_array(self::$log, array(8, 9, 10, 11, 12, 13, 14, 15)))) {
                self::log($msg, self::DEBUG_LEVEL_ERROR, $tag, $line, $method, $class);
            }
        }
        
        /**
         * syslog infomation
         * @return void
         */
        public static function log($msg = '', $lv = 1, $tag = '', $line = '', $method = '', $class = '') {
            if(!$method) $method = $class;
            if(!$tag || !is_string($tag)) $tag = 'main';
            $lv = self::parseLevel($lv);
            $ip = self::ip();
            switch($lv) {
                case self::DEBUG_LEVEL_DEBUG:
                case 'DEBUG':
                    syslog(LOG_DEBUG, date('Y-m-d H:i:s').'.'.floor(microtime()*1000)." $ip LAYWORK [$lv] [$tag] $method:$line $msg");
                    break;
                case self::DEBUG_LEVEL_INFO:
                case 'INFO':
                    syslog(LOG_INFO, date('Y-m-d H:i:s').'.'.floor(microtime()*1000)." $ip LAYWORK [$lv] [$tag] $method:$line $msg");
                    break;
                case self::DEBUG_LEVEL_WARN:
                case 'WARN':
                    syslog(LOG_WARNING, date('Y-m-d H:i:s').'.'.floor(microtime()*1000)." $ip LAYWORK [$lv] [$tag] $method:$line $msg");
                    break;
                case self::DEBUG_LEVEL_ERROR:
                case 'ERROR':
                    syslog(LOG_ERR, date('Y-m-d H:i:s').'.'.floor(microtime()*1000)." $ip LAYWORK [$lv] [$tag] $method:$line $msg");
                    break;
                default:
                    syslog(LOG_INFO, date('Y-m-d H:i:s').'.'.floor(microtime()*1000)." $ip LAYWORK [$lv] [$tag] $method:$line $msg");
                    break;
            }
        }
        /**
         * print infomation
         * @return void
         */
        public static function out($msg = '', $lv = 1, $tag = '', $line = '', $method = '', $class = '') {
            if(!$method) $method = $class;
            if(!$tag || !is_string($tag)) $tag = 'main';
            $lv = self::parseLevel($lv);
            $ip = self::ip();
            echo '<pre style="padding:0px;margin:0px;border:0px;">';
            echo date('Y-m-d H:i:s').'.'.floor(microtime()*1000)." $ip [$lv] [$tag] $method:$line $msg\r\n";
            echo '</pre>';
        }
        /**
         * print mixed infomation
         * @return void
         */
        public static function pre($msg = '', $lv = 1, $tag = '', $line = '', $method = '', $class = '') {
            if(!$method) $method = $class;
            if(!$tag || !is_string($tag)) $tag = 'main';
            $lv = self::parseLevel($lv);
            $ip = self::ip();
            echo '<pre style="padding:0px;margin:0px;border:0px;">';
            echo date('Y-m-d H:i:s').'.'.floor(microtime()*1000)." $ip [$lv] [$tag] $method:$line\r\n";
            echo '</pre>';
            echo '<pre style="padding:0px;margin:0 0 0 20px;border:0px;">';
            print_r($msg);
            echo '</pre>';
        }
        /**
         * parse level to string or integer
         * @return string|integer
         */
        public static function parseLevel($lv) {
            switch($lv) {
                case self::DEBUG_LEVEL_DEBUG:
                    $lv = 'DEBUG';
                    break;
                case self::DEBUG_LEVEL_INFO:
                    $lv = 'INFO';
                    break;
                case self::DEBUG_LEVEL_WARN:
                    $lv = 'WARN';
                    break;
                case self::DEBUG_LEVEL_ERROR:
                    $lv = 'ERROR';
                    break;
                case 'DEBUG':
                    $lv = self::DEBUG_LEVEL_DEBUG;
                    break;
                case 'INFO':
                    $lv = self::DEBUG_LEVEL_INFO;
                    break;
                case 'WARN':
                    $lv = self::DEBUG_LEVEL_WARN;
                    break;
                case 'ERROR':
                    $lv = self::DEBUG_LEVEL_ERROR;
                    break;
            }
            return $lv;
        }
        /**
         * get client ip
         * @return string
         */
        public static function ip() {
            if(getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
                $ip = getenv('HTTP_CLIENT_IP');
            } elseif(getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
                $ip = getenv('HTTP_X_FORWARDED_FOR');
            } elseif(getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
                $ip = getenv('REMOTE_ADDR');
            } elseif(isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
                $ip = $_SERVER['REMOTE_ADDR'];
            }
            return preg_match('/[\d\.]{7,15}/', $ip, $matches) ? $matches[0] : '';
        }
    }
    class_alias('Debugger', 'Laybug');
}
?>