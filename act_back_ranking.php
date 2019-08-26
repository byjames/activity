<?php

set_time_limit(0);
ini_set("display_errors", "On");
error_reporting(E_ALL | E_STRICT);

require "autoload.php";

$mysql = new  Mysqldb();

$data = [];
$channel_code_data = [];
$time = time();

function getGlobdlConfig()
{
    $mysql = new  Mysqldb();

    $sql = " SELECT * FROM gank_globaldb.tb_config WHERE act_status=" . ONE;

    if ($mysql->query($sql) && $mysql->rowcount() > ZERO) {
        $config_data = $mysql->fetch_all();
        return $config_data;
    }
    return false;
}

function getChannelId($mysql, $channel_str = false)
{
    if (!$mysql) {
        $mysql = new  Mysqldb();
    }
    $where = (!empty($channel_str) && $channel_str != "all") ? " WHERE id in(" . $channel_str . ")" : null;

    $sql = " SELECT id,channel_code FROM gank_globaldb.tb_channel " . $where;

    if ($mysql->query($sql) && $mysql->rowcount() > ZERO) {
        $channel_data = $mysql->fetch_all();
        foreach ($channel_data as $var) {
            $id = $var['id'];
            $channel_code = $var['channel_code'];

            $channel_code_data[$id] = $channel_code;
        }
        return $channel_code_data;
    }
    return false;
}

function backChannelRanking()
{
    $mysql = new  Mysqldb();
    $config_data = getGlobdlConfig();

    $time = time();
    $rank_url = Utils::config('rank_api');
    $dataOut = null;
    $channel_dataOut = null;

    if (!isDatas($config_data)) {
        log_message::info("config data is null");
        return false;
    }

    foreach ($config_data as $key => $val) {
        $appid = trim($val['appid']);
        $appcode = $val['appcode'];
        $channel_id = $val['channel_id'];
        $act_status = $val['act_status'];
        $server_prefix = $val['server_prefix'];
        $server_min = (int)$val['server_min'];
        $server_max = (int)$val['server_max'];
        $channel_data = explode(',', $val['channel_code']);//getChannelId($mysql, $channel_id);
        $act_rule = Utils::decodeDate($val['act_rule']);
        $ranking_data = isDatas($act_rule, 'ranking') ? isDatas($act_rule, 'ranking') : null;
        // 渠道数据标识符 联合sid查询
        if (empty($ranking_data) && $act_status != ONE) {
            log_message::info('活动已关闭 id:' . $val['id']);
            continue;
        }

        $start_at = strtotime(isDatas($ranking_data, 'start_at'));
        $end_at = strtotime(isDatas($ranking_data, 'end_at'));
        $ranking_type = isDatas($ranking_data, 'type'); // 读取指定配置文件

        // 判断活动的有效期
        if ($time >= $start_at && $time <= $end_at) {
            /* log_message::info('活动时间失效 id:' . $val['id']);
             echo Utils::sendResults('活动时间失效 id:' . $val['id']);*/

            // 轮循全局 game config
            foreach ($channel_data as $channelid => $channel_code) {

                $rsort_ranking_data = [];
                for ($i = $server_min; $i <= $server_max; $i++) {
                    // 针对渠道对应查询然后保存排序整理
                    $request_data = array(
                        'g' => $appcode,
                        'noip' => ONE,
                        's' => $server_prefix . $i,
                        'c' => $channel_code,
                        'type' => $ranking_type
                    );
                    $url = $rank_url['host'] . $channel_code;
                    //整理数据
                    $req = Utils::send_request($url, $request_data);

                    if (isDatas($req)) {
                        preg_replace('/^\{"id":(\d{1,})./', '{"id":"\\1",', $req);
                        $rsort_ranking_data[] = Utils::decodeDate($req);
                    }
                }
                $reque_data_out = $rsort_ranking_list = $rsortOut = null;

                if (!isDatas($rsort_ranking_data) || count($rsort_ranking_data) <= ZERO) {
                    //log_message::info(Utils::sendResults('rsort_ranking_data is null :'));
                    continue;
                }

                // all ranking server info
                foreach ($rsort_ranking_data as $datas) {
                    if (!isDatas($datas)) {
                        continue;
                    }
                    foreach ($datas as $val) {
                        $val['power'] = $val['power'];
                        $uid = $val['uid'];

                        $reque_data_out[$uid] = $val;

                    }
                }
                $reque_data_out = array_values($reque_data_out); // respace key
                //整理平台 下 的渠道数据$array, $keys, $type = 'asc', $limit = null
                if (!isDatas($reque_data_out) || count($request_data) <= ZERO) {
                    log_message::info(Utils::sendResults('reque_data_out is null :'));
                    continue;
                }

                // 类型返回的key 都是power
                $t = ONE;
                $rsort_ranking_list = isDatas($reque_data_out)
                    ?
                    Utils::array_sort($reque_data_out, "power", "desc", HUNDRED) : null;

                if (isDatas($rsort_ranking_list)) {

                    foreach ($rsort_ranking_list as $key => $val) {
                        $val['rank'] = $t;
                        $rsortOut [] = $val;
                        $t++;
                    }
                }
                $ranking_rule = isDatas($rsortOut) ? setServerFormat($rsortOut) : null;

                $data = [
                    'appid' => $appid,
                    'channel_id' => $channelid,
                    'server_min' => $server_min,
                    'server_max' => $server_max,
                    "channel_code" => $channel_code,
                    "type" => $ranking_type,
                    'ranking_rule' => $ranking_rule
                ];
                //  保存db

                back_db_channel_ranking($data);
                // 落地日志 文件 每10种执行本次脚本
                loadDataFile($appid, $appcode, $channel_code, $data);
                unset($data);
                unset($reque_data_out);
                unset($rsort_ranking_data);
                unset($rsort_ranking_list);
                unset($rsortOut);
                unset($ranking_rule);
            }
        }
    }
}

function loadDataFile($appid, $appcode, $channelid, $data)
{
    $file_name = "stat_" . $appid . "_" . $appcode . "_" . $channelid . "_ranking" . ".json";
    log_message::info(LOCAL_DATA_PATH);
    // 处理前10名
    $act_rule = json_decode($data['ranking_rule'], true);
    $limit_data = array_slice($act_rule, ZERO, 10);
    unset($data['ranking_rule']);
    unset($act_rule);

    $data['ranking_rule'] = $limit_data;

    $ret = Utils::mkdirFile(LOCAL_DATA_PATH, $file_name, $data);
    if ($ret) {
        $lod_res = Utils::request_upload_curls($file_name, null, RES_LOAD_RANKING_PATH);

        if ($lod_res) {
            log_message::info(" load data file is res");
        } else {
            log_message::info(" load data file is trueee...");
        }
        log_message::info("load data file is true");
        return true;
    }
    log_message::info('load data file is false');
    return false;
}

function setServerFormat($data)
{
    $data = is_string($data) ? json_decode($data, true) : $data;

    $arr = null;
    $serverdata = null;
    $patterns = "/\d+/";

    if (is_array($data)) {
        foreach ($data as $var) {
            $server = $var['serverName'];
            if (!is_numeric($server)) {
                preg_match_all($patterns, $server, $arr);

                $server = $arr[ZERO][ZERO] . "区";
            }
            $var['serverName'] = $server;
            $serverdata[] = $var;
        }
    }
    return json_encode($serverdata,JSON_UNESCAPED_UNICODE);
}

function back_db_channel_ranking($data)
{
    $mysql = new  Mysqldb();
    if ($mysql->insert("gank_activites.tb_stat_back_ranking", $data)) {
        echo Utils::errorCode("录入成功", SUCCESS);
        log_message::info("back channel ranking is true");
        return true;
    }
    echo Utils::errorCode("录入失败");
    log_message::info("back channel ranking is false");
    return false;
}

backChannelRanking();