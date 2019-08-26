<?php
include_once "lib/config.php";
include "lib/mysql.lib.php";
include "lib/util.php";
include "lib/wechat.activity.db.php";

$openId = '4819ced';

$day = (isset($_GET['type']) && !empty($_GET['type'])) ? $_GET['type'] : null;

if (!empty($day) && empty($day))
{

}