<?php
require "autoload.php";
// 签到领取奖励
$openId = mb_substr(md5(time()), 0, 7);
$openId = '4819ced';
//$sign_time = $_GET['signtime'];
$sign_time = isset($_POST['signtime']) ? $_POST['signtime'] : null;

if (empty($openId)) {
    return false;
}
$mysql = new Mysqldb();
$util = new Utils();
$SignMode = new  ActivitySer();
$info = $SignMode->GetActivitySign($openId);

// 地一次
if (!isset($info['openid']) || empty($info['openid']) && empty($sign_time)) {

    $ruleinfo = json_encode($util->SetDay());
    if ($SignMode->SetActivitySign($openId, $ruleinfo)) {
        //log_message::info(1);
        echo json_encode($util->GetdefaultConfig());
        exit;
    }
}
if (!empty($sign_time) && (!empty($info['rule']))) {

    //log_message::info('2' . "\n", 3);
    $ruleOut = json_decode($info['rule'], true);
    $datainfo = $util->SetSignDay($ruleOut, $sign_time);
    $infostr = json_encode($util->SetMesage($datainfo, '活动规则信息', 0));
    $SignMode->EditActivitySign($infostr, $info['id']);

    if ($infostr) {
        //log_message::info('4' . "\n", 3);
        echo $infostr;
    }
} else {
    //log_message::info('5' . "\n", 3);
    echo json_encode($util->GetdefaultConfig());
}




