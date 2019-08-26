<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/26 0026
 * Time: 下午 6:28
 */
class  ActivitySer extends Mysqldb
{
    # public
    public static $prize_info = null;
    # private
    private static $mysql;
    private static $appid = null;
    # protected
    protected $redis = null;
    protected $game_db_name = 'gank_activites';
    protected $redis_prefix = 'gank_activites';
    protected $activity_reward_table = 'app_user_reward';
    protected $redis_prize_key = null;
    protected static $activity_user_table = null;
    protected static $activity_prize_dbname = 'app_prize_config';
    protected $login_table = 'receivelogin';
    protected static $consignee_table = 'app_user_consignee';
    protected static $virtual_item_prize_table = 'app_virtual_item_prize';
    protected static $table = null;
    # const app
    const LIMIT_ONE = 1;
    const K_EXPICE_TIME = 3600;

    function __construct($appid)
    {
        if (!isDatas($appid)) {
            log_message::info('activites ser app id is null ');
            return false;
        }

        self::$appid = $appid;
        self::$activity_user_table = "app_" . self::$appid . "_user";
        $this->redis_prize_key = "prize_info:" . self::$appid;
        $xredis_conf = array(
            'redis_prefix' => $this->redis_prefix,
            'k_expire_time' => self::K_EXPICE_TIME
        );
        $this->redis = new Xredis($xredis_conf);
        self::$mysql = new Mysqldb();
        self::$prize_info = $this->getCachePrizeInfo();

        if (!$this->redis || !self::$mysql || !self::$prize_info) {
            log_message::info('redis  or mysql or get prize info is false ');
            return false;
        }
        self::$table = $this->game_db_name . '.' . self::$activity_user_table;
        return true;
    }

    //wechat_sign_activity
    public function GetActivitySign($openId)
    {
        $sql = "SELECT id,openid,rule,create_at FROM wechat_sign_activity WHERE openid='" . $openId . "' limit 1 ";

        if (self::$mysql->query($sql)) {
            return self::$mysql->fetch_row();
        }
        return false;
    }

    public function SetActivitySign($openId, $ruleinfo)
    {
        $sql = "INSERT INTO wechat_sign_activity (openid,rule,create_at) VALUES('" . $openId . "','" . $ruleinfo . "',NOW())";
        if (self::$mysql->query($sql)) {
            error_log('1' . "\n", 3, 'D:\xampp\htdocs\wechat\log\wechat-log.txt');
            return true;
        }
        return false;
    }

    public function EditActivitySign($infostr, $id)
    {
        $up = self::$mysql->update2('wechat_sign_activity', ['rule' => $infostr], 'id=:id', array('id' => $id));
        if ($up) {

            return true;
        }
        return false;
    }

    /***
     * 礼包码变更
     */
    public function EditGiftCode($id)
    {
        $up = self::$mysql->update2('wechat_gift_code', ['status' => 1], 'id=:id', array('id' => $id));
        if ($up) {

            return true;
        }
        return false;
    }

    /**
     * 礼包码获取 1
     */
    public function GetGiftCode($type)
    {
        $sql = "SELECT type,giftcode,status FROM wechat_gift_code WHERE type=" . $type . " AND status=GIFTCODE_STATUS limit 1 ";
        error_log($sql . "\n", 3, 'D:\xampp\htdocs\wechat\log\wechat-sql-log.txt');
        if (self::$mysql->query($sql)) {
            return self::$mysql->fetch_row();
        }
        return false;
    }

    /**
     * 礼包码清理
     */
    public function ClearGiftCode($id)
    {
        $sql = "DELETE FROM wechat_gift_code WHERE id=$id";

        if (self::$mysql->query($sql)) {
            return true;
        }
        return false;
    }

    /***
     * @param  type 对应不同类型获取不同的配置属性
     * 活动配置获取
     */
    public function ActivityConfigList($type = null)
    {
        $sql = "SELECT type,title,desc FROM wechat_activity_config";
        if (self::$mysql->query($sql)) {
            return self::$mysql->fetch_all();
        }
        return false;
    }

    /**
     * 设置活动cache
     * @param $key
     * @param $data
     */
    public function setConfigCache($key, $app_id, $data)
    {
        // get redis cache
        // if false get mysql data
        // and set redis
        // and return redis
        //$redis->hSet('paylog', $orderid, $payLogStr);
        $this->redis->hSet($key, $app_id, $data);
    }

    public function setRankingCache()
    {

    }

    /***
     * 后期联合 appid openid channelid
     * @param $openid
     * @return bool|null|string
     *
     */
    public function getCacheUserInfo($openid)
    {
        $userinfo = $this->redis->get(self::$activity_user_table . ":" . $openid);

        if ($userinfo) {
            $this->setCacheEX(self::$activity_user_table . ":" . $openid);
            return $userinfo;
        }
        log_message::info('cache user info is null ');
        return null;
    }

    /***
     * @param $key
     * @return bool
     */
    public function setCacheEX($key)
    {
        $ttl = $this->redis->ttl($key);
        if ($ttl < self::K_EXPICE_TIME) {
            return $this->redis->expire($key, self::K_EXPICE_TIME);
        }
        $this->redis->expire($key, self::K_EXPICE_TIME);
    }

    /***
     * @param $data
     * @return int
     */
    public function setCacheUserInfo($data)
    {
        $openid = $data['openid'];
        $data_str = json_encode($data, JSON_UNESCAPED_UNICODE);

        $ret = $this->redis->setex(self::$activity_user_table . ":" . $openid, self::K_EXPICE_TIME, $data_str);
        if (!$ret) {
            return log_message::info('set cache user info false');
        }
        return true;
        log_message::info('set cache user info true');
    }

    /***
     * 获取用户信息
     * @param $openid
     * @return array|bool
     */
    public function getdbUserInfo($openid)
    {
        $field = "uid,gameid,respce_lottery_total,surplus_lottery_num,openid,
        act_lottery_end_at,lottery_total,create_at,lucky_draw_total,lottery_up_limit,login_at";
        $where = " WHERE openid=:openid LIMIT " . self::LIMIT_ONE;
        $sql = "SELECT $field FROM " . self::$activity_user_table . $where;
        $prepare = array('openid' => $openid);
        if (self::$mysql->query($sql, $prepare) && self::$mysql->rowcount() > ZERO) {
            return self::$mysql->fetch_row();;
        }
        return false;
    }

    /***
     * 修改用户数据
     * @param $data
     * @param $prepare_array
     * @return bool
     */
    public function updateUserInfo($updata, $get_prepare_array)
    {
        $where_str = self::$mysql->formatSqlWhere($get_prepare_array);
        $where_val = $get_prepare_array;
        $up_ret = self::$mysql->update2(self::$activity_user_table, $updata, $where_str, $where_val);
        if ($up_ret) {
            return true;
            log_message::info("update info ok");
        }
        log_message::info("update info false");
        return false;
    }

    public static function formatSqlWhere($data, $addition = null)
    {
        if (isDatas($data)) {
            $where = null;
            foreach ($data as $field_key => $field_val) {
                if (!empty($where)) {
                    $where .= ' AND  ' . $field_key . '=:' . $field_key;
                } else {
                    $where .= $field_key . '=:' . $field_key;
                }
            }
            return $where . $addition;
        }
        return false;
    }

    /***
     * Save Userinfo
     * @param $data ['openid']
     * @return array|bool|mixed|null
     */
    public function saveUserInfo($data)
    {
        $openid = isset($data['openid']) ? $data['openid'] : ZERO;

        $user_info = $this->getCacheUserInfo($openid);

        if ($user_info) {
            log_message::info('return cache user info .');
            return Utils::decodeDate($user_info);
        }
        $db_user_info = $this->getdbUserInfo($openid);

        // 如果db 也不存在那么 cache 与 redis 都有进行录入set
        if (empty($db_user_info)) {
            // 这个时候注意参数 $data 是客户端新用户第一次进来初始的参数
            if (!self::$mysql->insert(self::$table, $data)) {
                log_message::info('insert indo db saveuserinfo false');
            }
            if (!$this->setCacheUserInfo($data)) {
                log_message::info('insert indo  cache saveuserinfo false');
            }
            return $data;
        }
        // 否则之前录入的redis 已经失效大db存在，这时候要重新设置下cache
        // 如果db  有的话直接set cache  的是 get db 的数据 $db_user_info

        if (!$this->setCacheUserInfo($db_user_info)) {
            log_message::info('insert indo  cache saveuserinfo2 false');
        }
        return $db_user_info;
    }

    /***
     * Save Userinfo
     * @param $data ['openid']
     * @return array|bool|mixed|null
     */
    public function saveAppInfo($data)
    {
        $openid = isset($data['openid']) ? $data['openid'] : null;

        $user_info = $this->getCacheUserInfo($openid);

        if ($user_info) {
            log_message::info('return cache user info .');
            return Utils::decodeDate($user_info);
        }
        $db_user_info = $this->getdbUserInfo($openid);

        // 如果db 也不存在那么 cache 与 redis 都有进行录入set
        if (empty($db_user_info)) {
            // 这个时候注意参数 $data 是客户端新用户第一次进来初始的参数
            if (!self::$mysql->insert(self::$table, $data)) {
                log_message::info('insert indo db saveuserinfo false');
            }
            if (!$this->setCacheUserInfo($data)) {
                log_message::info('insert indo  cache saveuserinfo false');
            }
            return $data;
        }
        // 否则之前录入的redis 已经失效大db存在，这时候要重新设置下cache
        // 如果db  有的话直接set cache  的是 get db 的数据 $db_user_info

        if (!$this->setCacheUserInfo($db_user_info)) {
            log_message::info('insert indo  cache saveuserinfo2 false');
        }
        return $db_user_info;
    }

    /***
     * 更新用户抽奖数据
     * @param $data
     * @param bool $lottery
     * @param bool $initial true 初始加载 default false
     * @return array|bool
     */
    public function updateUserlotteryInfo($data, $lottery = false, $lottery_up_limit = ZERO)
    {
        $openid = isset($data['openid']) ? $data['openid'] : ZERO;

        $appid = isDatas($data, 'appid');

        $sid = isDatas($data, 'sid');

        // 登录次数总次数 累计
        $login_frequency = isset($data['login_frequency']) ? $data['login_frequency'] : ZERO;
        // 登录累计天数
        $login_successive_day = isset($data['login_successive_day']) ? $data['login_successive_day'] : ZERO;
        // 登录总天数
        $login_total_day = isset($data['login_total_day']) ? $data['login_total_day'] : ZERO;
        // 付费次数
        $pay_frequency = isset($data['pay_frequency']) ? $data['pay_frequency'] : ZERO;
        // 付费金额
        $pay_amount = isset($data['pay_amount']) ? $data['pay_amount'] : ZERO;
        // 抽奖的剩余次数为 付费金额 + 登录天数 - 抽奖次数
        // -- 符合条件的抽奖次数 减去 已经抽了的次数  = 剩余的次数
        // 4f1565100230dbbda013e1bc51f24352
        if ($openid != '4f1565100230dbbda013e1bc51f24352') {
            $sql = "UPDATE " . self::$table . " SET 
            login_frequency = login_frequency+($login_frequency-login_frequency),
            login_successive_day = login_successive_day+($login_successive_day-login_successive_day),
            login_total_day = login_total_day+($login_total_day-login_total_day),
            pay_frequency = pay_frequency+($pay_frequency-pay_frequency),
            pay_amount = pay_amount+($pay_amount-pay_amount),             
            surplus_lottery_num = (pay_frequency+login_successive_day) - lottery_total
            WHERE openid = '" . $openid . "'";
        }
        if ($lottery == true) {
            $sql = "UPDATE " . self::$table . " SET surplus_lottery_num = surplus_lottery_num-1,
            lottery_total=lottery_total+1,lucky_draw_total=lucky_draw_total+1,lottery_up_limit=lottery_up_limit+" . $lottery_up_limit . "
            WHERE openid = '" . $openid . "'";
        }

        $openid = isset($data['openid']) ? $data['openid'] : ZERO;
        $where = 'id=:id';
        //$ret = $this->mysql->update($this->table, $data, $where, array('openid' => $openid));
        if (!empty($sql)) {
            log_message::info("lottery", $sql);

            if (self::$mysql->query($sql) && self::$mysql->rowCount() > ZERO) {

                $data = $this->getdbUserInfo($openid);
                // 重置缓存
                $this->getdbUserInfo($openid);
                $this->setCacheUserInfo($data);
                //surplus_lottery_num 剩余次数
                return $data;
            }
            return false;
        }
    }

    /**
     * 获取登录次数 登录天数 连续登录天数
     * @param $appid
     * @param $channelid
     * @param $sid
     * @param $uid
     * @param $act_start_at
     * @param $act_end_at
     * @return array|bool
     */
    public function getOnlineNum($appid, $channel, $sid, $uid, $act_start_at, $act_end_at)
    {
        $login_data = null;
        $dbconfg = Utils::config('game_stat_db');
        $mysql = new Mysqldb($dbconfg);

        $prepare = array(
            'who' => $uid,
            'appid' => $appid,
            'serverid' => $sid,
            'channelid' => $channel,
            'act_start_at' => $act_start_at,
            'act_end_at' => $act_end_at,
        );

        $field = "who,DATE(begdate) as login_at,COUNT(*) as login_frequency";
        $where = " WHERE who=:who AND begdate>=:act_start_at 
        AND begdate<=:act_end_at  AND appid=:appid 
        AND channelid=:channelid AND serverid=:serverid ";

        $sql = "SELECT $field FROM (SELECT * FROM " .
            $this->login_table . $where . " ) as a GROUP BY DATE(a.begdate)";

        if ($mysql->query($sql, $prepare) && $mysql->rowcount() > ZERO) {

            log_message::info('login 查询成功！！！！');
            $data = $mysql->fetch_all();
            $login_day = [];
            $login_num = [];
            $login_successive_day = [];
            foreach ($data as $var) {
                $login_day[] = $var['login_at']; // 登录天数
                $login_num[] = $var['login_frequency']; // 没登录一次记一次
            }
            $login_data = [
                'login_day' => count($login_day), // 累计登录天数
                'login_frequency' => array_sum($login_num), // 登录次数
                'login_successive_day' => count(Utils::successiveTime($login_day)), // 连续登录天数
            ];
            log_message::info(json_encode($login_data));
            return $login_data;
        }
        return false;
    }

    /***
     * 活动付费记录
     * @param $appid
     * @param $channelid
     * @param $sid
     * @param $uid
     * @param $act_start_at
     * @param $act_end_at
     * @return array|bool
     */
    public function getUserPayInfo($appid, $channel, $sid, $uid, $act_start_at, $act_end_at)
    {
        $login_data = null;

        $dbconfg = Utils::config('game_stat_db');

        $prepare = array(
            'who' => $uid,
            'appid' => $appid,
            'channelid' => $channel,
            'act_start_at' => $act_start_at,
            'act_end_at' => $act_end_at,
            'serverid' => $sid
        );
        // 每日 收充 + 1 count(disct begdate)
        $mysql = new Mysqldb($dbconfg);
        $field = "COUNT(*) paynum,SUM(currencyamount) as amount ";
        $where = " WHERE who=:who AND begdate>=:act_start_at 
        AND begdate<=:act_end_at  AND appid=:appid AND 
        channelid=:channelid AND serverid=:serverid LIMIT " . self::LIMIT_ONE;
        $sql = "SELECT $field FROM todaypayment " . $where;

        if ($mysql->query($sql, $prepare) && $mysql->rowcount() > ZERO) {
            log_message::info('pay 查询成功！！！！');
            $data = $mysql->fetch_row();
            log_message::info("&&&&&&JHHHHHHHHH", json_encode($data));
            $pay_frequency = ZERO;
            $pay_amount = ZERO;

            $pay_frequency = empty($data['pay_frequency']) ? ZERO : $data['pay_frequency'];
            $pay_amount = empty($data['pay_amount']) ? ZERO : $data['pay_amount'];

            $pay_data = [
                'pay_frequency' => $pay_frequency,
                'pay_amount' => $pay_amount,
            ];
            log_message::info("555555555555555555&&&&&&JHHHHHHHHH", json_encode($pay_data));
            return $pay_data;
        }
        return false;
    }
    //===========================================================================
    /***抽奖
     * 获取奖励db配置信息
     * @param $openid
     * @return array|bool
     */
    //===========================================================================
    public function getdbPrizeInfo()
    {
        $appid = self::$appid;
        //appid,prize_id,prize,rate,number,upper_limit,prize_type
        $field = "appid,prize_id,prize,rate,number,commodity,upper_limit,creat_at";
        $where = " WHERE appid=:appid";
        $sql = "SELECT $field FROM " . self::$activity_prize_dbname . $where;
        $prepare = array('appid' => $appid);
        if (self::$mysql->query($sql, $prepare) && self::$mysql->rowcount() > ZERO) {
            return self::$mysql->fetch_all();
        }
        return false;
    }

    /***
     *
     */
    public function respcedbLottery()
    {


    }

    /***
     * @return array|bool|null
     */
    public function getCachePrizeInfo()
    {
        $prize_info = null;
        $data = null;
        $redis_data = null;
        $cache_data = null;
        $data = Utils::config('game_prize');
        // 直接覆盖重置数据

        $prize_info = $this->redis->hGetAll($this->redis_prize_key);

        if ($prize_info) {

            foreach ($prize_info as $key => $var) {

                $redis_data[] = json_decode($var, true);
            }
            $cache_data = Utils::prizeFormat($redis_data);

            log_message::info('getCachePrizeInfo is cache info ', json_encode($cache_data));
            return $cache_data;
        }
        $dbdata = $this->getdbPrizeInfo();

        if ($dbdata) {
            // grt db set cache
            $this->setCachePrizeInfo($dbdata);
            $dbdata = Utils::prizeFormat($dbdata);
            log_message::info('cache prize info is null ');
            return $dbdata;
        } else {
            // DB CACHE 都没有获取配置文件
            if ($data) {
                $data = Utils::prizeFormat($data);
                log_message::info("getCachePrizeInfo set db prize ");
                $this->respaceLottery($data);
                return $data;
            }

        }
        return false;
    }

    /***
     * 同步更新 抽奖配置 与 db 配置
     * @param $respace_data
     * @return bool
     */
    public function respaceLottery($respace_data)
    {
        if ($respace_data) {
            $res_db = $this->setdbPrizeInfo($respace_data);
            $res_cache = $this->setCachePrizeInfo($respace_data);
            if ($res_db && $res_cache) {
                log_message::info("更新 prize db 与 cache 成功");
                return $respace_data;
            }
            log_message::info("更新 prize db 与 cache 失败");
            return false;
        }
        log_message::info(" respace_data is null ");
        return false;
    }

    /**
     * 设置 db 奖励配置
     * @param $data
     * @return bool
     */
    public function setdbPrizeInfo($data)
    {
        $prize_out = null;

        $fields = 'appid,prize_id,prize,rate,number,upper_limit,prize_type,status';
        foreach ($data as $key => $var) {
            $prize_out[$key] = [
                "appid" => '"' . self::$appid . '"',
                "prize_id" => $var['prize_id'],
                "prize" => '"' . $var['prize'] . '"',
                "rate" => $var['rate'],
                "number" => $var['number'],
                "upper_limit" => $var['upper_limit'],
                "prize_type" => $var['prize_type'],
                "status" => $var['status'],// DEFAULT 0  1 实物需发货
            ];
        }
        if (isDatas($prize_out)) {
            if (self::$mysql->insertBatch(self::$activity_prize_dbname, $fields, $prize_out)) {
                return true;
            }
            log_message::info("setdbPrizeInfo false ");
            return false;
        }
        log_message::info("prize_out is null");
        return false;
    }

    /***
     * 录入奖品到redis
     * @param $data
     * @param null $appid
     * @return int
     */
    public function setCachePrizeInfo($data)
    {
        // 整理hmset 索规定的格式 并录入缓存 ..[key=>string],[key,string]
        // 后期同步db 后台可同步
        $data = Utils::prizeAssembly($data);
        // ..[key=>string],[key,string]
        $ret = $this->redis->hMset($this->redis_prize_key, $data);
        if (!$ret) {
            log_message::info('set cache prize info false');
            return false;
        }
        return true;
    }

    /***
     * 覆盖抽奖配置规则
     * @param $appid
     * @return bool|mixed|null
     */
    public function setLotteryFrequency($prize_id)
    {
        if ($this->redis->exists($this->redis_prize_key)) {
            $data = json_decode($this->redis->hGet($this->redis_prize_key, $prize_id), true);

            if (isDatas($data)) {
                $upper_limit = (int)$data['upper_limit'];
                $frequency = $data['frequency'];
                // 重置cache 物品抽奖的累计次数 递增 | frequency
                return $this->setUpperLimit($frequency, $upper_limit, $data);
            }
            log_message::info("setLotteryFrequency false");
            return false;
        }
        log_message::info("setLotteryFrequency false cache is null");
        return false;
    }

    /***
     * 重置cache 物品抽奖的累计次数
     * @param $role_upper_limit
     * @param $game_upper_limit
     * @param $data
     * @return bool|mixed|null
     */
    public function setUpperLimit($role_upper_limit, $game_upper_limit, $data)
    {
        log_message::info('-----------------', json_encode($data, JSON_UNESCAPED_UNICODE));

        $commodity = $data['status']; // 0 虚拟 1 实物
        $prize_id = $data['prize_id'];
        $prize_value = null;
        $isNayUpperLimit = self::isNayUpperLimit($role_upper_limit, $game_upper_limit);
        //  如果奖品抽取次数小于规定的限制次数
        if ($isNayUpperLimit) {
            $role_upper_limit += ONE;
            $data['frequency'] = $role_upper_limit;
            $prize_value = json_encode($data, JSON_UNESCAPED_UNICODE);
            log_message::info("########################", $prize_value);
            // 重置prize cache 次数
            log_message::info('^^^^^^^^^^^^^^^^^^^^^cishu', $prize_id, $prize_value);
            $this->redis->hSet($this->redis_prize_key, $prize_id, $prize_value);
            return self::prizeIsCommodity($data, $commodity);
        } else {
            log_message::info('set uo limit is nay InterventionLottery');
            // 如果抽奖达到上限则重新随机奖励
            return self::interventionLottery();
        }
    }

    /***
     * 抽到实物需要收货地址弹框相应
     * 此类型key 不会再 cache 里设置并返回，只允许代码里判断有效类型
     * @param $appid
     * @return bool|mixed|null
     */
    public static function prizeIsCommodity($data, $commodity)
    {
        $id = $data['prize_id'];
        $status = (int)$data['status'];
        $up_limit = $data['upper_limit'];
        if ($commodity == ONE) {
            $data['prize_type'] = ONE;
        }
        if ($up_limit != ZERO && $status != ONE) {
            $ret = self::getdbVirtualItemPrize($id);
            log_message::info("******************", json_encode($ret, JSON_UNESCAPED_UNICODE));
            if ($ret) {

                $card_info = '卡密:' . $ret['password'];

                $data['prize_desc'] = $card_info;

                //$data['prize_desc'] = '卡号:'.$ret['account'].' 卡密:'.$ret['account'];
                self::setdbVirtualItemPrize($id);
                log_message::info("prizeIsCommodity VirtualItem data is true");
            } else {
                log_message::info("prizeIsCommodity VirtualItem data is pres");
                unset($data);
                //
                $data = self::$prize_info[SIX];
            }
            // 最后重置一个吧
            //
            log_message::info("prizeIsCommodity VirtualItem data is null");
        }

        // 如果虚拟判断是否卡密 对应 prize id 获取卡密配置信息
        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    /***
     * 干预奖品信息 如果抽奖达到上限则重新随机奖励
     */
    public function interventionLottery($data = [])
    {
        $prize_all = self::$prize_info;
        foreach ($prize_all as $key => $val) {
            $arr[$val['prize_id']] = $val['rate'];
        }
        do {
            log_message::info("********************* prize_all ", json_encode($arr));

            $rid = Utils::getPrizeRand($arr);

            log_message::info("********************* rid ", $rid);
            //$pirze_lottery = $prize_all[$rid - ONE];
            $pirze_lottery = $prize_all[$rid];

            $game_upper_limit = (int)$pirze_lottery['upper_limit'];
            $role_upper_limit = (int)$pirze_lottery['frequency'];
            return $this->setLotteryFrequency($pirze_lottery['prize_id']);
        } while (Utils::isUpperlimit($role_upper_limit, $game_upper_limit));
    }

    /***
     * 获取
     * @param $id
     * @return bool|false|string
     */
    public function byPrizeidInfo($id = null, $all = false)
    {
        $data = $this->redis->hGet($this->redis_prize_key, $id);

        if ($all == true) {
            $data = $this->redis->hGetAll($this->redis_prize_key);
        }

        if (isDatas($data)) {
            return $data;
        }
        log_message::info("getPrizeidInfo is null");
        return false;
    }

    /***
     * 干预判断 如果 不是无限制的类型并且 game 上限大于或等于用户已抽取的次数
     * 表示已经达到上限符合干预条件需要系统重新随机奖品
     * @param $role_upper_limit
     * @param $game_upper_limit
     * @return bool
     */
    public static function isUpperlimit($role_upper_limit, $game_upper_limit)
    {
        if (($game_upper_limit != ZERO && $game_upper_limit >= $role_upper_limit)) {
            return true;
        }
        return false;
    }

    /****
     * 判断有效范围内可增加抽取次数并返回客户端
     * @param $role_upper_limit
     * @param $game_upper_limit
     * @return bool
     */
    public static function isNayUpperLimit($role_upper_limit, $game_upper_limit)
    {
        if ($game_upper_limit == ZERO || ($game_upper_limit != ZERO && $role_upper_limit < $game_upper_limit)) {
            return true;
        }
        return false;
    }


    /***
     * 重置上限次数
     */
    public function replaceUpperLimit()
    {
        $where_data = ['appid' => self::$appid];
        $edit_data = ['frequency' => ZERO];
        $where_str = self::$mysql->formatSqlWhere($where_data);

        $up = self::$mysql->update2(self::$activity_prize_dbname, $edit_data, $where_str, $where_data);
        if ($up) {
            log_message::info('抽奖上限次数重置成功！');
            return true;
        }
        log_message::info('抽奖上限次数重置失败！');
        return false;
    }

    public function getdbLotteryInfo()
    {

    }
    // --------------------------------------------------------------------
    // 抽奖活动用户信息，用户抽取次数
    // --------------------------------------------------------------------

    /***
     * @param $appid
     * @param $channelid
     * @param $uid
     * @param $act_start_at
     * @param $act_end_at
     * @param $openid
     * @return array|bool
     */
    public function getdbLotteryTotal($appid, $channelid, $uid, $act_start_at, $act_end_at, $openid)
    {
        $dbconfg = Utils::config('game_stat_db');

        $mysql = new Mysqldb($dbconfg);
        $field = " COUNT(*) as cont_frequency,COUNT(DISTINCT DATE(begdate)) cont_day";
        $where = " WHERE who=:who appid=:appid AND channelid=:channelid AND begdate>=:start_at 
        AND begdate<=:end_at LIMIT" . self::LIMIT_ONE;
        $sql = "SELECT $field FROM " . $this->login_table . $where;

        $prepare = array(
            'who' => $uid,
            'appid' => $appid,
            'channelid' => $channelid,
            'start_at' => $act_start_at,
            'end_at' => $act_end_at,
        );

        if ($mysql->query($sql, $prepare) && $mysql->rowcount() > ZERO) {
            return $mysql->fetch_row();
        }
        return false;
    }

    /***
     * 设置用户收货地址
     * @param $data
     * @return bool
     */
    public static function savePirzeConsignee($data)
    {
        /*$addItion = " ON DUPLICATE KEY UPDATE
		type=1, 
		consignee_address ={$data['consignee_address']} ,
		consignee_name = {$data['consignee_name']},
		consignee_phone={$data['consignee_phone']},";*/

        $ret = self::$mysql->insert(self::$consignee_table, $data);
        if ($ret) {
            return true;
        }
        return false;
    }

    /***
     * @param $data
     * @return bool
     */
    public static function editPirzeConsignee($data)
    {
        $openid = $data['openid'];
        $appid = $data['appid'];
        $channel = $data['channel'];
        $sid = $data['sid'];
        $prize_order = $data['prize_order'];
        $type = $data['type'];

        $edit_data = [
            'type' => $type,
            'consignee_address' => $data['consignee_address'],
            'consignee_phone' => $data['consignee_phone'],
        ];
        $where_str = "openid=:openid AND appid=:appid 
        AND channel=:channel AND sid=:sid AND prize_order=:prize_order";
        $where_data = [
            'openid' => $openid,
            'appid' => $appid,
            'channel' => $channel,
            'sid' => $sid,
            'prize_order' => $prize_order,
        ];
        $up = self::$mysql->update2(self::$consignee_table, $edit_data, $where_str, $where_data);
        if ($up) {
            log_message::info('地址更新成功！');
            return true;
        }
        log_message::info('地址更新失败！');
        return false;
    }

    /***
     * 获取用户抽奖记录
     */
    public function userPrizeInfo($appid, $openid, $channel, $sid)
    {
        $sql = " SELECT * FROM " . self::$consignee_table . " 
        WHERE openid=:openid 
        AND appid=:appid
        AND channel=:channel
        AND sid =:sid";

        $prepare = array(
            'openid' => $openid,
            'appid' => $appid,
            'channel' => $channel,
            'sid' => $sid,
        );
        if (self::$mysql->query($sql, $prepare) && self::$mysql->rowcount() > ZERO) {
            return self::$mysql->fetch_all();
        }
        return false;
    }

    /***
     * 获取用户抽奖记录
     */
    public function byuserPrizeOrderInfo($uid, $prize_order)
    {
        $sql = " SELECT * FROM  " . self::$consignee_table . " 
        WHERE uid=:uid AND prize_order=:prize_order";

        $prepare = array(
            'uid' => $uid,
            'prize_order' => $prize_order
        );
        if (self::$mysql->query($sql, $prepare) && self::$mysql->rowcount() > ZERO) {
            return self::$mysql->fetch_row();
        }
        return false;
    }

    /**
     * @param $where_data
     * @return bool|mixe
     */
    public static function getdbRankingdata($where_data)
    {
        $sql = "SELECT * FROM tb_stat_back_ranking ";

        $addition = 'ORDER BY id desc limit 1';
        $sql .= ' WHERE ' . self::$mysql->formatSqlWhere($where_data, $addition);

        if (self::$mysql->query($sql, $where_data) && self::$mysql->rowcount() > ZERO) {
            return self::$mysql->fetch_row();
            log_message::info("globalconfig true");
        }
        log_message::info("globalconfig false", $sql);
        return false;
    }

    /**
     * @param $data
     * @return bool|mixed
     */
    public function getFileRankingdata($data)
    {
        $appid = $data['appid'];
        $appcode = $data['appcode'];
        $channelid = $data['channel_code'];

        $file_name = "stat_" . $appid . "_" . $appcode . "_" . $channelid . "_ranking" . ".json";

        if ($file_name) {
            $res = Utils::getFileConfig($file_name, RES_LOAD_RANKING_URL);
            if ($res) {
                log_message::info("ranking data is true");
                return $res;
            }
        }
        log_message::info("ranking name is null ");
        return false;
    }

    public function getRankingList($data)
    {
        $sql = "select * from WHERE ";
        //self::$mysql-
    }

    // get item acccount info

    /***
     * @param $itemid
     * @return array|bool
     */
    public static function getdbVirtualItemPrize($itemid)
    {
        // AND status=:status
        $sql = " SELECT account,password,`desc`,itemid FROM " . self::$virtual_item_prize_table . " 
        WHERE itemid=:itemid  LIMIT " . self::LIMIT_ONE;

        $prepare = array(
            'itemid' => $itemid,
            //'status' => ZERO, // 0待领取 1 已领取 获取new prize itme
        );
        if (self::$mysql->query($sql, $prepare) && self::$mysql->rowcount() > ZERO) {
            return self::$mysql->fetch_row();
        }
        return false;
    }

    /***
     * @param $itemid
     * @return bool
     */
    public static function setdbVirtualItemPrize($itemid)
    {
        $where_data = ['itemid' => $itemid];
        $edit_data = ['status' => ONE];
        $where_str = self::$mysql->formatSqlWhere($where_data);

        $up = self::$mysql->update2(self::$virtual_item_prize_table, $edit_data, $where_str, $where_data);
        if ($up) {
            log_message::info('重置虚拟配置成功！');
            return true;
        }
        log_message::info('重置虚拟配置失败！');
        return false;
    }
    // --------------get appid -------


}