<?php

define('TIMEZONE', "PRC");                // 时区设置
date_default_timezone_set(TIMEZONE);

define('DBHOST', 'localhost');            // 数据库地址
define('DBUSER', 'root');                 // 数据库用户名
define('DBPORT', 3306);
define('DBPASSWORD', '保密
define('DBNAME', 'gank_activites');              // 默认数据库
define('DBCHARSET', 'utf8');              // 数据库字符集

#wechat defaine
define('WECHAT_APPID', 'wx6a7abf227bcd0d');
define('WECHAT_KEY', '13084de8fbb0038feeaa15c3d');
define('WECHAT_SCOPE_BASE', 'snsapi_base'); // 不弹框
define('WECHAT_SCOPE', 'snsapi_userinfo'); // 弹框

define('WECHAT_SERVER', 'https://open.weixin.qq.com/connect/oauth2/authorize');
define('WECHAT_REDIRECT_URL', urlencode('localhost'));
define('WECHAT_ACCESSTOKEN_URL', 'https://api.weixin.qq.com/sns/oauth2/access_token');

#log dir
define('DIR_SEPARATOR', "/");
define('LINUX_LOGDIRS', dirname(dirname(__FILE__)) . '/log/'); // 日志目录
define('WINDOWS_LOGDIRS', dirname(dirname(__FILE__)) . '/log/error.txt'); // 日志目录
define('LOGDIRS_FILENAME', 'error.log'); // 日志目录
define("LOG_SERVER_URL", dirname(dirname(__FILE__)) . "/log/");
#gift code status
define('GIFTCODE_STATUS', 0); // 正常
define('GIFTCODE_EXPIRED_STATUS', 1); //过期

#log url
# class dir
define('LIBDIR', dirname(__FILE__) . '/'); // lib
define('LIBDIR_TWO', dirname(dirname(__FILE__)) . '/mode/'); // mode

#Redis
define('REDIS_HOST', '192.168.0.140');
define('REDIS_PORT', '6379');
define('REDIS_AUTH', '123456');
# time format
define('DATE_FORMAT_S', "Y-m-d H:i:s");
define('DATE_FORMAT_D', "Y-m-d");

# res
#file upload url
//define('RES_URL', 'http://192.168.181.139:9095');
//define('RES_PATH', dirname(dirname(dirname($_SERVER['DOCUMENT_ROOT']))) . '/res/config/');
define('RES_CONFIG_URL', 'http://192.168.181.1303/res/config');// config file url
define('RES_ATH', dirname(dirname(dirname($_SERVER['DOCUMENT_ROOT']))) . '/res/data/');
define('LOCAL_DATA_PATH', $_SERVER['DOCUMENT_ROOT'] . '/res/data/');
define('LOCAL_CFG_PATH', $_SERVER['DOCUMENT_ROOT'] . '/res/config/');
#排行榜接口地址
define('RANKING_FILE_DESC', '-'); // 文件链接符
define('STAT_RANKING_DIR', 'http://event.djsh5.com/rank/?');    //  暂无用
define('RES_LOAD_RANKING_URL', 'http://bacdjsh5.com/res/data/'); // get 排行数据脚本文件地址
define('RES_LOAD_RANKING_PATH', $_SERVER['DOCUMENT_ROOT'] . '/res/data/'); //  文件的地址目录
define('ACCEPT_FILE_SCRIPT_URL', 'http://192.168.181.137/res/ranking-load-file.php'); // 远程接收排行榜数据文件脚本url

# 常数zero
define('ZERO', 0);
define('ONE', 1);
define('TWO', 2);
define('THREE', 3);
define('FIVE', 5);
define('SIX', 6);
define('HUNDRED', 100);

#activites
define('ACTIVITES_STATUS', 1); // 0 未开启 1 已开启
#activity api url
define('ACTIVITES_API_URL', 'http://192.168.0.140:9096/activity/index.php');
define('ACTIVITES_SUSPENSION_IMG_URL', 'http://192.168.0.140:9096/res/suspension/log.ico');

#error info
define('SUCCESS', 1);
define('FAILURE', -1);
