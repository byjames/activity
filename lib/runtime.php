<?php
header("Access-Control-Allow-Origin: *");
include "define.php";
/***
 * @param $classname
 */
function anshee_autoload($classname)
{
    $class_file = strtolower($classname) . ".php";
    require(LIBDIR . $class_file);

    if (file_exists($class_file)) {
        require($class_file);
        // .mod require_once(LIBDIR_TWO.$class_file);
    } else {

    }
}

/***
 * @param $object
 * @return bool
 */
function isBlank($object)
{
    if (is_null($object) || '' === $object || (is_array($object) && count($object) < 1) || !isset($object)) {
        return true;
    }
    return empty($object);
}

/***
 * @param $object
 * @param null $key
 * @return bool
 */
function isDatas($object, $key = null)
{
    if (is_numeric($object) && !empty($object)){
        return $object;
        log_message::info("is Data number ");
    }
    if (is_string($object) && !empty($object)) {
        return $object;
        log_message::info("is Data string ");
    }
    if (is_array($object) && !empty($object) && count($object) > ZERO && empty($key)) {
        log_message::info("is Data array ");
        return $object;
    }
    if (is_array($object) && !empty($key) && isset($object[$key]) && !empty($object[$key])) {
        log_message::info("is Data array key ");
        return $object[$key];
    }
    log_message::info("data is null",$key);
    return false;
}

function isPrimary($object)
{
    return is_null($object) || is_bool($object) || is_string($object) || is_numeric($object);
}

function isSetData()
{

}
function get_server_ip()
{
    if (!empty($_SERVER['SERVER_ADDR']))
        return $_SERVER['SERVER_ADDR'];
    $result = shell_exec("/sbin/ifconfig");
    if (preg_match_all("/addr:(\d+\.\d+\.\d+\.\d+)/", $result, $match) !== 0) {
        foreach ($match[0] as $k => $v) {
            if ($match[1][$k] != "127.0.0.1")
                return $match[1][$k];
        }
    }
    return false;
}

function getIp()
{
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $ip = getHostByName(php_uname('n'));
        return $ip;
    } else {
        return get_server_ip();
    }
}
//
spl_autoload_register("anshee_autoload");
