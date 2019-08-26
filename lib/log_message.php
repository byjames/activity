<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/19 0019
 * Time: 下午 1:54
 */
class log_message
{
    public static function info()
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        $file = str_replace(LOG_SERVER_URL, '', $trace[0]['file']);

        $line = $trace[0]['line'];

        if (isset($trace[1]['function'])) {
            $function = $trace[1]['function'];
        } else {
            $function = 'unknow';
        }

        $message = date(DATE_FORMAT_S, time()) . '[' . $file . ": line: " . $line . ' funtion: ' . $function . ']';

        foreach (func_get_args() as $item) {
            $message .= self::toString($item) . ' ';
        }

        $classname_file = basename($file, '.php') . '.txt';

        $file_name = LOG_SERVER_URL . date(DATE_FORMAT_D, time()) . '_' . $classname_file;

        if (!$file_name) {
            return -1;
        }
        if (self::isCli()) {
            self::echoLine('[INFO]' . date(DATE_FORMAT_S, time()) . ' ' . $message);
        } else {
            if (error_log($message . "\n", 3, $file_name) == FALSE) {
                return -1;
            }
        }
    }

    /**
     * 是不是简单基础类型(null, boolean , string, numeric)
     * @param $object
     * @return bool
     */
    public static function isPrimary($object)
    {
        return is_null($object) || is_bool($object) || is_string($object) || is_numeric($object);
    }

    public static function toString($object)
    {
        if (self::isPrimary($object)) {

            return $object;
        }
        if (is_array($object)) {
            return json_encode($object, JSON_UNESCAPED_UNICODE);
        }
        if (method_exists($object, '__toString')) {
            return get_class($object) . '@{' . $object->__toString() . '}';
        }

        $reflection_object = new ReflectionObject($object);
        $properties = $reflection_object->getProperties();
        $values = array();
        foreach ($properties as $property) {
            if ($property->isStatic() || !$property->isPublic()) {
                continue;
            }
            $name = $property->getName();
            $value = $property->getValue($object);
            if (isPrimary($value)) {
                $values[$name] = $value;
            } elseif (is_array($value)) {
                $values[$name] = json_encode($value, JSON_UNESCAPED_UNICODE);
            } else {
                $values[$name] = '@' . get_class($value);
            }
        }
        return get_class($object) . '@{' . json_encode($values, JSON_UNESCAPED_UNICODE) . '}';
    }

    public static function errorInfo()
    {

        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        $file = str_replace(SERVER_URL, '', $trace[0]['file']);
        $line = $trace[0]['line'];

        if (isset($trace[1]['function'])) {
            $function = $trace[1]['function'];
        } else {
            $function = 'unknow';
        }

        $message = '[' . $file . ":" . $line . ' ' . $function . ']';
        foreach (func_get_args() as $item) {
            $message .= toString($item) . ' ';
        }
        return $message;
    }

    /**
     * 方便调试输出
     */
    public function echoLine()
    {
        $message = '';
        foreach (func_get_args() as $item) {
            $message .= $this->toString($item) . ' ';
        }

        $text = '[PID ' . getmypid() . ']' . $message;
        if (isCli()) {
            echo $text . PHP_EOL;
        } else {
            echo $text . '<br/>';
        }
    }

    public static function isCli()
    {
        return php_sapi_name() == 'cli';
    }
}