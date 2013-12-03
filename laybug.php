<?php
/**
 * laysoft debug lib
 * @see https://github.com/lay595715148/laybug
 * 
 * @author Lay Li
 * @version 1.0.0 (build 131010)
 */

// 判断Layload是否已经定义
if(defined('INIT_LAYBUG')) {
    return;
}
// 定义标记
define('INIT_LAYBUG', true);

//init set output buffering
ini_set('output_buffering', 'on');
ini_set('implicit_flush', 'off');
//ob_start();
ob_implicit_flush(false);

/**
 * debugger interface, if need to customize,to implement it
 * 
 * @author Lay Li
 * @version 1.0.0 (build 131202)
 *         
 */
interface IDebugger {
    /**
     * log debugger infomation
     *
     * @param string $msg
     *            the message
     * @param int $lv
     *            the debug level
     * @param string $tag
     *            the tag
     * @return void
     */
    public function log($msg, $lv = 1, $tag = '');
    /**
     * print out debugger infomation
     *
     * @param string $msg
     *            the message
     * @param int $lv
     *            the debug level
     * @param string $tag
     *            the tag
     * @return void
     */
    public function out($msg, $lv = 1, $tag = '');
    /**
     * multily print out debugger infomation
     *
     * @param string $msg
     *            the message
     * @param int $lv
     *            the debug level
     * @param string $tag
     *            the tag
     * @return void
     */
    public function pre($msg, $lv = 1, $tag = '');
}

/**
 * Debug工具类,并且已经实现了IDebugger接口
 *
 * @author Lay Li
 * @version 1.0.0 (build 131010)
 */
class Debugger implements IDebugger {
    const DEBUG_LEVEL_DEBUG = 1;
    const DEBUG_LEVEL_INFO = 2;
    const DEBUG_LEVEL_WARN = 4;
    const DEBUG_LEVEL_ERROR = 8;
    const DEBUG_LEVEL_ALL = 15;
    /**
     * the flag of print out
     *
     * @var boolean int
     */
    private static $out = false;
    /**
     * the flag of syslog
     *
     * @var boolean int
     */
    private static $log = false;
    /**
     * Delay debugger in microseconds
     *
     * @var boolean int
     */
    private static $sleep = false;
    /**
     *
     * @var the instance of current debugger
     */
    private static $instance = null;
    /**
     * 获取debugger实例
     * @return IDebugger
     */
    private static function getInstance() {
        if(! self::$instance) {
            self::$instance = self::getInstanceByClassname('Debugger');
        }
        return self::$instance;
    }
    /**
     * 通过类名获取一个实例
     *
     * @param string $classname
     *            类名
     * @return IDebugger
     */
    private static function getInstanceByClassname($classname = 'Debugger') {
        $class = null;
        if(self::checkClassname($classname)) {
            $class = new $classname();
        }
        if(! ($class instanceof IDebugger)) {
            unset($class);
        }
        return $class;
    }
    /**
     * 检测是否符合类名格式
     *
     * @param string $classname
     *            类名
     * @return boolean
     */
    private static function checkClassname($classname) {
        if(is_string($classname) && $classname && class_exists($classname)) {
            return true;
        } else {
            return false;
        }
    }
    /**
     * 当前数值与给出的debug级别是否匹配
     *
     * @param int $set
     *            the level number
     * @param int $lv
     *            default is 1
     * @return boolean
     */
    private static function regular($set, $lv = 1) {
        $ret = $lv & $set;
        return $ret === $lv ? true : false;
    }
    /**
     * 注册一个实现了IDebbuger接口的对象实例，并将旧的对象替换掉
     *
     * @param string|IDebugger $instance            
     */
    public static function register($instance) {
        if($instance && is_string($instance)) {
            self::$instance = self::getInstanceByClassname($instance);
        } else if($instance instanceof IDebugger) {
            self::$instance = $instance;
        }
    }
    /**
     * initialize Debugger, if $instance is valid, it will replaces current debugger or create
     *
     * @param boolean|array<boolean|int> $debug
     *            optional
     * @param string|Idebugger $instance
     * @return void
     */
    public static function initialize($debug = '', $instance = '') {
        if(is_bool($debug)) {
            self::$out = self::$log = $debug;
        } else if(is_array($debug)) {
            $debug['out'] = isset($debug['out']) ? $debug['out'] : isset($debug[0]) ? $debug[0] : false;
            $debug['log'] = isset($debug['log']) ? $debug['log'] : isset($debug[1]) ? $debug[1] : false;
            $debug['sleep'] = isset($debug['sleep']) ? $debug['sleep'] : isset($debug[2]) ? $debug[2] : false;
            self::$out = ($debug['out'] === true) ? true : intval($debug['out']);
            self::$log = ($debug['log'] === true) ? true : intval($debug['log']);
            self::$sleep = $debug['sleep'] ? intval($debug['sleep']) : false;
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
        
        if($instance) {
            self::register($instance);
        }
    }
    /**
     * print out debug infomation
     *
     * @param string|array|object $msg
     *            the message
     * @param string $tag
     *            the tag
     * @return void
     */
    public static function debug($msg, $tag = '') {
        if(self::$out === true || (self::$out && self::regular(intval(self::$out), self::DEBUG_LEVEL_DEBUG))) {
            self::getInstance()->pre($msg, self::DEBUG_LEVEL_DEBUG, $tag);
            ob_flush();
            flush();
            usleep(self::$sleep);
        }
        if(self::$log === true || (self::$log && self::regular(intval(self::$log), self::DEBUG_LEVEL_DEBUG))) {
            self::getInstance()->log(json_encode($msg), self::DEBUG_LEVEL_DEBUG, $tag);
        }
    }
    /**
     * print out info infomation
     *
     * @param string $msg
     *            the message
     * @param string $tag
     *            the tag
     * @return void
     */
    public static function info($msg, $tag = '') {
        if(self::$out === true || (self::$out && self::regular(intval(self::$out), self::DEBUG_LEVEL_INFO))) {
            self::getInstance()->out($msg, self::DEBUG_LEVEL_INFO, $tag);
            ob_flush();
            flush();
            usleep(self::$sleep);
        }
        if(self::$log === true || (self::$log && self::regular(intval(self::$log), self::DEBUG_LEVEL_INFO))) {
            self::getInstance()->log($msg, self::DEBUG_LEVEL_INFO, $tag);
        }
    }
    /**
     * print out warning infomation
     *
     * @param string $msg
     *            the message
     * @param string $tag
     *            the tag
     * @return void
     */
    public static function warning($msg, $tag = '') {
        if(self::$out === true || (self::$out && self::regular(intval(self::$out), self::DEBUG_LEVEL_WARN))) {
            self::getInstance()->out($msg, self::DEBUG_LEVEL_WARN, $tag);
            ob_flush();
            flush();
            usleep(self::$sleep);
        }
        if(self::$log === true || (self::$log && self::regular(intval(self::$log), self::DEBUG_LEVEL_WARN))) {
            self::getInstance()->log($msg, self::DEBUG_LEVEL_WARN, $tag);
        }
    }
    /**
     * print out warning infomation
     *
     * @param string $msg
     *            the message
     * @param string $tag
     *            the tag
     * @return void
     */
    public static function warn($msg, $tag = '') {
        self::warning($msg, $tag);
    }
    /**
     * print out error infomation
     *
     * @param string $msg
     *            the message
     * @param string $tag
     *            the tag
     * @return void
     */
    public static function error($msg, $tag = '') {
        if(self::$out === true || (self::$out && self::regular(intval(self::$out), self::DEBUG_LEVEL_ERROR))) {
            self::getInstance()->out($msg, self::DEBUG_LEVEL_ERROR, $tag);
            ob_flush();
            flush();
            usleep(self::$sleep);
        }
        if(self::$log === true || (self::$log && self::regular(intval(self::$log), self::DEBUG_LEVEL_ERROR))) {
            self::getInstance()->log($msg, self::DEBUG_LEVEL_ERROR, $tag);
        }
    }
    
    /**
     * cut string
     *
     * @param string $string
     *            the target string
     * @param number $front
     *            the front bumber
     * @param number $follow
     *            the tail bumber
     * @param string $dot
     *            the dots
     * @return string
     */
    protected function cutString($string, $front = 10, $follow = 0, $dot = '...') {
        $strlen = strlen($string);
        if($strlen < $front + $follow) {
            return $string;
        } else {
            $front = abs(intval($front));
            $follow = abs(intval($follow));
            $pattern = '/^(.{' . $front . '})(.*)(.{' . $follow . '})$/';
            $bool = preg_match($pattern, $string, $matches);
            if($bool) {
                $front = $matches[1];
                $follow = $matches[3];
                return $front . $dot . $follow;
            } else {
                return $string;
            }
        }
    }
    /**
     * parse level to CSS
     *
     * @param int|string $lv
     *            the debug level string or level number code
     * @return string
     */
    protected function parseColor($lv) {
        switch($lv) {
            case Debugger::DEBUG_LEVEL_DEBUG:
            case 'DEBUG':
                $lv = 'color:#0066FF';
                break;
            case Debugger::DEBUG_LEVEL_INFO:
            case 'INFO':
                $lv = 'color:#006600';
                break;
            case Debugger::DEBUG_LEVEL_WARN:
            case 'WARN':
                $lv = 'color:#FF9900';
                break;
            case Debugger::DEBUG_LEVEL_ERROR:
            case 'ERROR':
                $lv = 'color:#FF0000';
        }
        return $lv;
    }
    /**
     * parse level to string or integer
     *
     * @param int|string $lv
     *            the debug level string or level number code
     * @return string int
     */
    protected function parseLevel($lv) {
        switch($lv) {
            case Debugger::DEBUG_LEVEL_DEBUG:
                $lv = 'DEBUG';
                break;
            case Debugger::DEBUG_LEVEL_INFO:
                $lv = 'INFO';
                break;
            case Debugger::DEBUG_LEVEL_WARN:
                $lv = 'WARN';
                break;
            case Debugger::DEBUG_LEVEL_ERROR:
                $lv = 'ERROR';
                break;
            case 'DEBUG':
                $lv = Debugger::DEBUG_LEVEL_DEBUG;
                break;
            case 'INFO':
                $lv = Debugger::DEBUG_LEVEL_INFO;
                break;
            case 'WARN':
                $lv = Debugger::DEBUG_LEVEL_WARN;
                break;
            case 'ERROR':
                $lv = Debugger::DEBUG_LEVEL_ERROR;
                break;
        }
        return $lv;
    }
    /**
     * get client ip
     *
     * @return string
     */
    protected function ip() {
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
    
    /**
     * syslog infomation
     *
     * @param string $msg
     *            the message
     * @param int $lv
     *            the debug level
     * @param string $tag
     *            the tag
     * @return void
     */
    public function log($msg, $lv = 1, $tag = '') {
        $stack = debug_backtrace();
        $first = array_shift($stack);
        $second = array_shift($stack);
        while($second['class'] == 'Debugger') { // 判定是不是还在Debugger类里
            $first = $second;
            $second = array_shift($stack);
        }
        $file = $first['file'];
        $line = $first['line'];
        $method = $second['function'];
        $type = $second['type'];
        $class = $second['class'];
        
        if(! $method)
            $method = $class;
        if(! $tag || ! is_string($tag))
            $tag = 'MAIN';
        $lv = $this->parseLevel($lv);
        $ip = $this->ip();
        switch($lv) {
            case Debugger::DEBUG_LEVEL_DEBUG:
            case 'DEBUG':
                syslog(LOG_DEBUG, date('Y-m-d H:i:s') . '.' . floor(microtime() * 1000) . "\t$ip\t[LAYWORK]\t[$lv]\t[$tag]\t[$file]\t$class$type$method($line)\t$msg");
                break;
            case Debugger::DEBUG_LEVEL_INFO:
            case 'INFO':
                syslog(LOG_INFO, date('Y-m-d H:i:s') . '.' . floor(microtime() * 1000) . "\t$ip\t[LAYWORK]\t[$lv]\t[$tag]\t[$file]\t$class$type$method($line)\t$msg");
                break;
            case Debugger::DEBUG_LEVEL_WARN:
            case 'WARN':
                syslog(LOG_WARNING, date('Y-m-d H:i:s') . '.' . floor(microtime() * 1000) . "\t$ip\t[LAYWORK]\t[$lv]\t[$tag]\t[$file]\t$class$type$method($line)\t$msg");
                break;
            case Debugger::DEBUG_LEVEL_ERROR:
            case 'ERROR':
                syslog(LOG_ERR, date('Y-m-d H:i:s') . '.' . floor(microtime() * 1000) . "\t$ip\t[LAYWORK]\t[$lv]\t[$tag]\t[$file]\t$class$type$method($line)\t$msg");
                break;
            default:
                syslog(LOG_INFO, date('Y-m-d H:i:s') . '.' . floor(microtime() * 1000) . "\t$ip\t[LAYWORK]\t[$lv]\t[$tag]\t[$file]\t$class$type$method($line)\t$msg");
                break;
        }
    }
    /**
     * print infomation
     *
     * @param string $msg
     *            the message
     * @param int $lv
     *            the debug level
     * @param string $tag
     *            the tag
     * @return void
     */
    public function out($msg, $lv = 1, $tag = '') {
        $stack = debug_backtrace();
        $first = array_shift($stack);
        $second = array_shift($stack);
        while($second['class'] == 'Debugger') { // 判定是不是还在Debugger类里
            $first = $second;
            $second = array_shift($stack);
        }
        $file = $first['file'];
        $line = $first['line'];
        $method = $second['function'];
        $type = $second['type'];
        $class = $second['class'];
        
        if(! $method)
            $method = $class;
        if(! $tag || ! is_string($tag))
            $tag = 'MAIN';
        $lv = $this->parseLevel($lv);
        $ip = $this->ip();
        echo '<pre style="padding:0px;font-family:Consolas;margin:0px;border:0px;' . $this->parseColor($lv) . '">';
        echo date('Y-m-d H:i:s') . '.' . floor(microtime() * 1000) . "\t$ip\t[$lv]\t[<span title=\"$tag\">" . $this->cutString($tag, 4, 0) . "</span>]\t[<span title=\"$file\">" . $this->cutString($file, 8, 16) . "</span>]\t<span title=\"$class\">" . end(explode("\\", $class)) . "</span>$type$method($line)\t$msg\r\n";
        echo '</pre>';
    }
    /**
     * print mixed infomation
     *
     * @param mixed $msg
     *            the message
     * @param int $lv
     *            the debug level
     * @param string $tag
     *            the tag
     * @return void
     */
    public function pre($msg, $lv = 1, $tag = '') {
        $stack = debug_backtrace();
        $first = array_shift($stack);
        $second = array_shift($stack);
        while($second['class'] == 'Debugger') { // 判定是不是还在Debugger类里
            $first = $second;
            $second = array_shift($stack);
        }
        $file = $first['file'];
        $line = $first['line'];
        $method = $second['function'];
        $type = $second['type'];
        $class = $second['class'];
        
        if(! $method)
            $method = $class;
        if(! $tag || ! is_string($tag))
            $tag = 'MAIN';
        $lv = $this->parseLevel($lv);
        $ip = $this->ip();
        echo '<pre style="padding:0px;font-family:Consolas;margin:0px;border:0px;' . $this->parseColor($lv) . '">';
        echo date('Y-m-d H:i:s') . '.' . floor(microtime() * 1000) . "\t$ip\t[$lv]\t[<span title=\"$tag\">" . $this->cutString($tag, 4, 0) . "</span>]\t[<span title=\"$file\">" . $this->cutString($file, 8, 16) . "</span>]\t<span title=\"$class\">" . end(explode("\\", $class)) . "</span>$type$method($line)\r\n";
        echo '</pre>';
        echo '<pre style="padding:0 0 0 1em;font-family:Consolas;margin:0px;border:0px;' . $this->parseColor($lv) . '">';
        print_r($msg);
        echo '</pre>';
    }
}
?>
