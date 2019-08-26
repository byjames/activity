<?php
ini_set("display_errors", "On");
error_reporting(E_ALL | E_STRICT);
header("Access-Control-Allow-Origin: *");
require "autoload.php";
// 获取协议号 'roll';//
$pact = !isDatas($_GET, 'a') ? null : isDatas($_GET, 'a');

log_message::info("获取协议号", $pact);
$activites_mode = new  ActivitesModel();
// 初始用户信息
$activites_mode->initializeUserinfo();

switch ($pact) {
    case "role_click_status": // ----------
        echo $activites_mode->isAccess();
        break;
    case "show_block":// 是为了控制悬浮图标 ----------
        $res = $activites_mode->showBlockact();
        log_message::info("pact show activity", $res);
        echo $res;
        break;
    case "show_activity":// 是为了展示活动列表左菜单栏 1 展示 0不展示 判断游戏渠道有多少个活动* ----------
        // 抽奖 lottery
        // 邀请有礼 inviting invited 控制tab
        // 新服有礼 newgift
        // 老用户回归 backuser
        $activites_mode->initializeHttpData('lottery');
        $res = $activites_mode->activiti_menu_list();
        log_message::info("pact show activity", $res);
        echo $res;
        break;
    case 'ranking': // 排行榜 ----
        log_message::info('pact ranking');
        $activites_mode->initializeHttpData('ranking');
        //$ranking = $activites_mode->getRanking();
        //getdbRankingInfo
        $ranking = $activites_mode->getRankingInfo();
        echo $ranking;
        break;
   /* case 'upload_lottery_cache':
        $activites_mode->initializeHttpData('lottery');
        // set cacge $lottery_info = $activites_mode->setLottryInfo();
        // set cache set db respaceLotteryInfo
        $lottery_info = $activites_mode->setLottryInfo();
        echo $lottery_info;
        break;*/
    case 'saveaddress': // 抽中实物填写收货地址信息 -------
        $activites_mode->initializeHttpData('lottery');
        $ret = $activites_mode->updateShippingAddress();
        echo $ret;
        break;
    /*case "build_user_code":  // 获取用户邀请码
        log_message::info('pact build_user_code');
        echo '{"result":1,"msg":"已生成过邀请码","val":"6eq46496","list":null}';
        break;
    case "get_gift_status":  // 获取礼物状态
        log_message::info('pact get_gift_status');
        echo '{"result":1,"msg":"成功","val":null,"list":{"gift_title":"dasdad","gift_year":0,"gift_new_100":0,"gift_new_200":0,"gift_new_300":0,"gift_new_pay":0,"gift_back_1":0,"gift_back_2":0,"gift_back_3":0}}';
        break;*/
    case "roll":            // 抽奖 ------- 需要
        // 初始用户抽奖信息
        //$activites_mode->initializeLotteryInfo();
        log_message::info("pact roll");
        $activites_mode->initializeHttpData('lottery');
        $res = $activites_mode->getLotteryConfig();
        echo $res;
        break;
    case 'get_chance_times': // 可抽取次数 剩余抽奖次数
        // 初始用户抽奖信息
        //$activites_mode->initializeLotteryInfo();
        log_message::info("pact get_chance_times");
        $activites_mode->initializeHttpData('lottery');
        $surplus_lottery_num = $activites_mode->getLotterySurplusNum();
        echo $surplus_lottery_num;
        break;
    case "get_lottery_num": // 已抽奖次数
        // 初始用户抽奖信息
        //$activites_mode->initializeLotteryInfo();
        log_message::info("pact get_lottery_num");
        $activites_mode->initializeHttpData('lottery');
        $lottery_total = $activites_mode->getUserLotteryTotal();
        echo $lottery_total;
        break;
    case "my_rewards":      // 总共获取的奖励

        log_message::info("pact my_rewards");
        $activites_mode->initializeHttpData('lottery');
        $user_winning_record = $activites_mode->userWinningRecord();
        echo $user_winning_record;
        break;
    case "lucky_draw_total_prize":// 抽奖次数奖励
        log_message::info("pact lucky_draw_total_prize");
        $activites_mode->initializeHttpData('lottery');
        $user_winning_record = $activites_mode->lucky_draw_total_prize();
        echo $user_winning_record;
        break;
    /*case "signin": // 签到
        $activites_mode->initializeHttpData('signin');
        break;
    case "signin_times_list":
        $activites_mode->initializeHttpData('signin');
        echo $activites_mode->getSignTimeList();
        // 返回当前天的签到 list
        break;*/
    default;

        log_message::info("pact pact is false end");
        Utils::errorCode('pact is false', FAILURE);
        break;
}
