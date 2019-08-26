<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-10-11
 * Time: 下午 06:19
 */
class ActivitesModel
{
    // public
    public $pact = null;
    public $uid = null;
    public $gameid = null;
    public $sid = null;
    public $server = null;
    public $appid = null;
    public $appcode = null;
    public $openid = null;
    public $config_data = null;
    public $channel = null;
    public $channel_id = null;
    public $act_type = null;
    public $act_rule = null;
    public $role_ranking_type = null;
    public $ranking_type = [ONE => 'power', TWO => 'level'];
    // private
    private $redis = null;
    private $time = null;
    private $time_day = null;
    private $utils = null;
    private $cache_key = null;
    private $file_name = null;
    private $service = null;
    private $server_prefix = null;
    // protected
    protected $user_info = null;
    protected $request = null;
    protected $act_start_at = null;
    protected $act_end_at = null;
    protected static $frame_block_at = null;
    protected static $role_prize_uplimit = ZERO;

    public function __construct($act_type = null)
    {
        $this->utils = new Utils();
        $this->httpData(); // 初始化基础数据
        $this->service = new ActivitySer($this->appid);
        if (!$this->service) {
            exit('service is false...');
        }
    }

    /**
     * 初始基础配置数据
     * @return bool
     */
    public function httpData()
    {
        $request = file_get_contents("php://input");
        log_message::info("http php://input DATA ", json_encode($this->request));
        log_message::info("http GET DATA conttents php input ...", json_encode($_GET));
        $this->request = $request;

        $this->appid = self::isParame($_GET, 'appid');

        if (empty($this->appid)) {
            exit(Utils::sendResults("appid is null "));
        }
        $this->appcode = self::isParame($_GET, 'appcode');
        if (empty($this->appcode)) {
            exit(Utils::sendResults("appcode is null "));
        }
        $this->uid = self::isParame($_GET, 'uid');
        if (empty($this->uid)) {
            exit(Utils::sendResults("uid is null "));
        }
        $this->channel = self::isParame($_GET, 'channel');
        if (empty($this->channel)) {
            exit(Utils::sendResults("channel is null "));
        }
        $this->sid = self::isParame($_GET, 'sid');;
        if (empty($this->sid)) {
            exit(Utils::sendResults("sid is null "));
        }
        $this->channel_id = 5; //self::isParame($this->request,'appid');

        // $this->act_type = $act_type; //self::isParame($this->request,'appid');//你自己定义的要
        // self::isParame($this->request,'appid');

        // 生成一个我们这边的唯一标识码
        // self::isParame($this->request,'appid');
        $this->openid = md5($this->appcode . $this->channel . $this->uid . $this->sid);
        $this->time = date(DATE_FORMAT_S, time());
        $this->time_day = date(DATE_FORMAT_D, time());
        $this->file_name = $this->getFileName();
        log_message::info('file name ', $this->file_name);

        $this->config_data = Utils::getFileConfig($this->file_name);

        $this->server_prefix = isDatas($this->config_data, 'server_prefix');

        return true;
    }

    /***
     * 重置活动类型
     * @return bool
     */
    public function respaceActType($act_type = null)
    {
        $request = file_get_contents("php://input");
        $this->request = $request;
        $this->act_type = $act_type; // self::isParame($this->request,'appid');//你自己定义的要
        $this->role_ranking_type = ONE; // 1 战力  2 等级  排行榜 self::isParame($this->request,'appid');
        return true;
    }

    /***
     * 初始化活动类型
     * @param $act_type
     */
    public function initializeHttpData($act_type)
    {
        $this->respaceActType($act_type);
        if (!$this->isActivites($act_type)) {
            log_message::info("活动规则异常");
            exit(Utils::sendResults('活动规则异常'));
        }
    }

    /***
     * 参数是否为空
     * @param $data
     * @return bool
     */
    public static function isParame($data, $key)
    {
        if ($red = isDatas($data, $key)) {
            return $red;
        }
        log_message::info("$key is null ");
        //exit(Utils::sendResults("$key is null"));
        //exit(Utils::errorCode("$key is null ", FAILURE));
        return null;
    }

    /***
     * 暂无调用
     * @return string
     */
    public function getCacheKey()
    {
        return 'global_config';
    }

    /***
     * 获取全局游戏活动配置文件名
     * @param null $appid
     * @return string
     */
    public function getFileName($appid = null)
    {
        return $this->appid . '_' . strtolower($this->appcode) . '.json';
    }

    /***
     * @param $role_sid
     * @param $min_sid
     * @param $max_sid
     */
    public function isActivites($act_type = null)
    {
        // 游戏是否存在
        if (!$this->isApp()) {
            log_message::info('appid游戏不存在');
            return false;
        }
        // 活动是否开启
        if (!$this->isActivitesStatus()) {
            log_message::info('活动不存在');
            return false;
        }
        // 活动类型是否存在 && $act_type!=-1
        if (!$this->isActivityType()) {
            log_message::info('活动类型不存在');
            return false;
        }
        // 渠道活动的区服范围是否正常
        if (!$this->isServer()) {
            log_message::info('渠道活动区服不存在或范围有误');
            return false;
        }
        // 渠道是否在有效时间范围内 max time && $act_type!=-1
        if (!$this->isActivitesTime()) {
            log_message::info('渠道活动时间已经失效');
            return false;
        }
        return true;
    }

    /****
     * act block
     */
    public function showBlockact()
    {
        // 游戏是否存在
        if (!$this->isApp()) {
            exit(Utils::sendResults("不存在的游戏配置"));
        }
        // 活动是否开启
        if (!$this->isActivitesStatus()) {
            log_message::info('活动不存在');
            exit(Utils::sendResults("活动不存在"));
        }
        if (!$this->isChannel()) {
            log_message::info('渠道不存在');
            exit(Utils::sendResults("渠道不存在"));
        }
        // 渠道活动的区服范围是否正常
        if (!$this->isServer()) {
            log_message::info('区服不存在或范围有误');
            exit(Utils::sendResults("区服不存在或范围有误"));
        }

        $data = [
            'result' => ONE,
            'msg' => '展示活动图标',
            'img' => ACTIVITES_SUSPENSION_IMG_URL, // image url
            'activity' => ACTIVITES_API_URL,
        ];
        return json_encode($data, JSON_UNESCAPED_UNICODE);

    }

    /***
     * 活动时间校验是否为有效期
     * @param $start_at
     * @param $end_at
     * @return bool
     */
    public function isActivitesTime($start_at = null, $end_at = null)
    {
        $act_rule = $this->isActivityType();

        $start_at = strtotime($act_rule['start_at']);
        $end_at = strtotime($act_rule['end_at']);
        log_message::info("当前时间", $this->time, "活动开始时间" . $start_at, "活动结束时间" . $end_at);
        // 如果活动是用户 就需要初始化配置数据抽奖的
        // 首先判断抽奖活动的配置时间与用的更新的配置时间是否一样的
        // 并且获得的时间在当前时间的有效期范围之内才可以否则 是失败的
        // 就是不通过的
        log_message::info("^^^^^^^^^^^^^^", json_encode($this->user_info, JSON_UNESCAPED_UNICODE));
        if ((strtotime($this->time) >= $start_at) && (strtotime($this->time) <= $end_at)) {
            $this->act_start_at = $start_at;
            $this->act_end_at = $end_at;
            // 重置活动开始截止时间
            //$this->editRoleLotteryEndAt();
            return true;
        }
        return false;
    }

    /***
     * 重置用户抽奖活动的截止时间，判断截止时间是否失效如果已经失效重置抽奖次数
     * 必须要进行初始化不然等玩家下一次抽奖的时候数据就不准确了
     * 但是又一点 以为每次抽奖的时候用户的活动数据都会根据玩家对应的数据更新到表中
     * 这样做有什么意义呢？？？
     * @return bool
     */
    public function editRoleLotteryEndAt($lottery)
    {
        $role_lottery_end_at = strtotime($this->time);
        $act_end_at = strtotime($lottery['end_at']);

        if (isDatas($lottery)) {
            // 只有等活动快要过期的时候更新最新的活动
            if ($role_lottery_end_at > ZERO && $role_lottery_end_at > $act_end_at) {

                // 到时候可以按照需求自定义初始的值
                $data = [
                    'act_lottery_end_at' => $act_end_at,
                    'surplus_lottery_num' => ZERO,
                    'login_frequency     ' => ZERO,
                    'login_successive_day' => ZERO,
                    'login_total_day     ' => ZERO,
                    'pay_frequency       ' => ZERO,
                    'pay_amount          ' => ZERO,
                    'lottery_total       ' => ZERO,
                ];

                $get_prepare = [
                    'appid' => $this->appid,
                    'openid' => $this->openid,
                    'channel' => $this->channel,
                    'sid' => $this->sid,
                ];
                $ret = $this->service->updateUserInfo($data, $get_prepare);
                if ($ret) {
                    log_message::info("editRoleLotteryEndAt up db true");
                    return true;
                }
                log_message::info("editRoleLotteryEndAt up false");
            }
        } else {
            log_message::info("editRoleLotteryEndAt data false");
            return false;
        }
    }

    /***
     * 是否存在游戏
     * @return bool
     */
    public function isApp()
    {
        if ($this->appid == isDatas($this->config_data, 'appid')) {
            return true;
        }

        log_message::info("appid", isDatas($this->config_data, 'appid'));
        return false;
    }

    /***
     * 是否存在渠道
     * @return bool
     */
    public function isChannel()
    {
        $channel = explode(',', $this->config_data['channel_code']);
        if (in_array($this->channel, $channel)) {
            log_message::info('channel is null true !!!');
            return true;
        }
        log_message::info('channel is null false !!!');
        return false;
    }

    /***
     * 活动状态是否已经开启
     * @param $status
     * @return bool
     */
    public function isActivitesStatus($status = null)
    {
        $status = $this->config_data['act_status'];

        if (isset($status)) {
            if ($status == ACTIVITES_STATUS) {
                return true;
            }

            log_message::info(Utils::sendResults('status is 0 '));
            return false;
        }
        log_message::info(Utils::sendResults('status is null '));
        return false;
    }

    /***
     *强制弹框
     */
    public function isAccess()
    {
        if (isDatas($this->user_info)) {

            $now_day = date('Ymd', time());

            $online_at = isDatas($this->user_info, 'login_at');
            $frame_block_at = $online_at;

            if ($now_day != $frame_block_at) {

                return Utils::sendResults('强制弹窗活动', null, SUCCESS);
            }
            return Utils::sendResults("不强制弹窗活动");
        }
    }

    /***
     * 开启活动列表
     */
    public function activiti_menu_list()
    {
        //echo '{"result":1,"msg":"成功","val":null,"list":
        //{"inviting":1,"invited":1,"newgift":1,"lottery":1,"backuser":1,"ranking":1}}';
        // $messige, $result_dat = null, $status = null
        $data = array('inviting' => ONE, 'invited' => ZERO, 'newgift' => ZERO, 'backuser' => ZERO);

        if (isDatas($this->act_rule)) {
            foreach ($this->act_rule as $act_key => $act_val) {
                $start_at = strtotime($act_val['start_at']);
                $end_at = strtotime($act_val['end_at']);
                if ((strtotime($this->time) >= $start_at) && (strtotime($this->time) <= $end_at)) {
                    $data[$act_key] = ONE;
                } else {
                    $data[$act_key] = ZERO;
                }
            }

            // 初始抽奖
            if (isset($data['lottery'])) {
                $this->initializeLotteryInfo();
            }
            return Utils::sendResults("活动信息回调成功", ["list" => $data], SUCCESS);
        }
        log_message::info("activiti menu list is null");
        return Utils::sendResults("活动不存在");
    }

    /***
     * 是否存在活动类型
     * @param null $act_type
     * @return bool
     */
    public function isActivityType($act_type = null)
    {
        $this->act_rule = json_decode($this->config_data['act_rule'], true);

        if (isset($this->act_rule) && count($this->act_rule) > ZERO) {

            if (isset($this->act_rule[$this->act_type])) {
                return $this->act_rule[$this->act_type];
            }
            log_message::info('不存在的活动类型...' . $this->act_rule[$this->act_type]);
            return false;
        }
        log_message::info('不存在的活动类型2');
        return false;
    }

    /***
     * 是否在正常的区服范围
     * @param $role_sid
     * @param $min_sid
     * @param $max_sid
     * @return bool
     */
    public function isServer($role_sid = null)
    {
        $min_sid = $this->config_data['server_min'];
        $max_sid = $this->config_data['server_max'];
        $role_sid = $this->sid;
        if (isset($min_sid) && isset($max_sid)) {
            if ($role_sid >= $min_sid && $role_sid <= $max_sid) {
                return true;
            }
            log_message::info('不存在的区服');
            return false;
        }
        log_message::info('区服空');
        return false;
    }

    /**=================================
     * 活动去活动配置
     * @param $file_name
     * @return bool|mixed
     * =================================
     */
    public function isActivitesData()
    {
        if (!isBlank($this->config_data)) {
            return $this->config_data;
        }
        return false;
    }

    public function setConfig($data, $cache_key = null)
    {
        $cache_key = isBlank($cache_key) ? $this->cache_key : $cache_key;
        $ret = $this->service->setConfigCache($cache_key, $this->appid, $data);

        if ($ret) {
            return $ret;
        }
        return false;
    }

    /***
     * @param array $userdata
     * @return bool
     */
    public function initializeUserinfo($userdata = [])
    {
        $act_lottery_rule = Utils::decodeDate(isDatas($this->config_data, 'act_rule'));
        $lottery = isDatas($act_lottery_rule, 'lottery');
        $lottery_end_at = isDatas($lottery, 'end_at') ? isDatas($lottery, 'end_at') : null;
        // rule  $ruleinfo = json_encode($util->SetDay());

        $ruleinfo = $this->utils->SetDay();

        $data = [
            'uid' => $this->uid,
            'sid' => $this->sid,
            'appid' => $this->appid,
            'openid' => $this->openid,
            'channel' => $this->channel,
            'server_prefix' => $this->server_prefix,
            'act_lottery_end_at' => $lottery_end_at,
            'login_at' => date('Ymd', time()),
            //'sign_rule'=>$ruleinfo,
        ];
        $data = isDatas($userdata) ? $userdata : $data;

        $this->user_info = $this->service->saveUserInfo($data);
        //
        //$this->server = $this->server_prefix.$this->sid;
        // 后期要对应server 如果要设置前缀做处理在这里进行设置
        $this->server = $this->sid;

        if ($this->user_info) {
            return $this->user_info;
        }
        return Utils::sendResults("初始化信息失败");
    }

    /**
     *初始用户抽奖基础数据if (strtotime($this->time) >= strtotime($act_endtime)) {
     * // 初始数据 set cache db editRoleLotteryEndAt
     */
    public function initializeLotteryInfo()
    {
        $file_name = $this->getFileName();
        $config_data = Utils::getFileConfig($file_name);
        $config_rule = Utils::decodeDate(isDatas($config_data, 'act_rule'));

        $lottery = isDatas($config_rule, 'lottery');
        $prize_info = ActivitySer::$prize_info;//$this->service->getCachePrizeInfo();
        $prize_respace_at = isDatas($prize_info)
            ?
            isDatas($prize_info[ONE], 'create_at')
                ?
                isDatas($prize_info[ONE], 'create_at') : null
            :
            null;
        // -----------------------------------------------------------
        // | 重置用户每日的实物上限次数 |
        $this->respaceRoleUplimitdata();
        // -----------------------------------------------------------
        if ($lottery) {
            // | 重置用户抽奖活动的截止时间如果过去抽取次数销毁 | 后期对应需求
            $this->editRoleLotteryEndAt($lottery);
            // 初始用户抽奖基础数据
            $start_at = isDatas($lottery, 'start_at');
            $end_at = isDatas($lottery, 'end_at');
            // 活动有效期
            if (strtotime($this->time) >= strtotime($start_at) && strtotime($this->time) <= strtotime($end_at)) {
                //加载抽奖数据充值玩家自身的配置数据 BUG
                $this->replaceLotteryData($start_at, $end_at, true);
                // 每日初始所有奖金池上限次数
                $this->isRespaceLottery($prize_respace_at);
                log_message::info("initializeLotteryInfo false");
                return true;
            }
            log_message::info("initializeLotteryInfo lottery times is false");
            return false;
        }
        log_message::info("initializeLotteryInfo lottery is null");
        return false;

    }

    public function isRespaceLottery($prize_respace_at)
    {
        $now_at = date(DATE_FORMAT_D, time());
        $prize_respace_at = date(DATE_FORMAT_D, strtotime($prize_respace_at));
        if (isDatas($prize_respace_at)) {
            if ($prize_respace_at != $now_at) {
                // get hgetAll prize info ----||||
                $datas = ActivitySer::$prize_info;
                $lotter_list = [];

                foreach ($datas as $key => $var) {

                    $prizeOut = null;
                    $prizeOut = $var;
                    $prizeOut['frequency'] = ZERO;
                    $prizeOut['create_at'] = $this->time;
                    $lotter_list[] = $prizeOut;
                }
                //$this->service->replaceUpperLimit(); // 重置 LOTTERY DB
                //$prizedata = $this->service->getdbPrizeInfo();  // GET DB PRIZE INFO
                //$prizedata = ActivitySer::$prize_info;
                // $this->service->setCachePrizeInfo($prizedata); // respace db cache info
                if (isDatas($lotter_list)) {
                    $this->service->setCachePrizeInfo($lotter_list);
                }
            }
        }
        log_message::info("RespaceLottery   prize_respace_at is null ");

        return false;
    }

    /**
     * 排行榜数据获取
     */
    public function getRanking()
    {
        $data = null;
        $rank_url = Utils::config('rank_api');
        log_message::info($rank_url);
        $data = array(
            'g' => 'WCNKH3c3TLdlo',
            'noip' => ONE,
            's' => $this->server_prefix . $this->sid,
            'c' => $this->channel,
            'type' => $this->ranking_type[$this->role_ranking_type]
        );
        log_message::info($rank_url['host'] . $this->channel);

        $url = $rank_url['host'] . $this->channel;
        $ret = Utils::decodeDate(Utils::send_request($url, $data));
        if ($ret) {
            return Utils::sendResults("排行榜", ["list" => $ret], SUCCESS);
        }
        return Utils::sendResults("ranking is null");
    }

    /***
     * @return bool|false|string
     */
    public function getRankingInfo()
    {
        $data = [
            "appid" => $this->appid,
            "appcode" => $this->appcode,
            "channel_code" => $this->channel,
        ];
        // get db ranking data
        /*$ret = $this->service->getdbRankingdata($data);*/
        // get file ranking data
        $ret = $this->service->getFileRankingdata($data);
        if ($ret) {
            $ranking_data = Utils::decodeDate(isDatas($ret, 'ranking_rule'));
            $type = isDatas($ret, "type");
            $limit_data = array_slice($ranking_data, ZERO, 10);
            return Utils::sendResults("排行榜", ["type" => $type, "list" => $limit_data], SUCCESS);
        }
        return Utils::sendResults('暂无排行榜数据');
    }

    /***
     *
     */
    public function ranKingVerif()
    {
        return;
    }

    /***
     * @return bool|string
     */
    public function rankingBack()
    {
        $this->service->redis->set('ttt', '哈哈');
        $data = $this->service->redis->get('ttt');
        return $data;
    }


    /***
     * @return mixed 废弃
     */
    public function prizeInfo()
    {
        $ret = $this->service->getPrizeInfo($this->appid);
        if (!$ret) {
            log_message::info('getPrizeInfo is false');
            return $ret;
        }
        log_message::info('getPrizeInfo is ok');
        return $ret;
    }

    /***
     * 重置抽奖配置信息
     * @return false|string
     */
    public function setLottryInfo()
    {
        $data = Utils::config('game_prize');

        $ret = $this->service->setCachePrizeInfo($data);
        if ($ret) {
            return Utils::sendResults('lottery config set ok', [], SUCCESS);
        }
        return Utils::sendResults('lottery config set false', FAILURE);
    }

    /***
     * 抽奖配置
     * @return array|bool|null
     */
    public function respaceLotteryInfo()
    {

        $ret = $this->service->getCachePrizeInfo();
        if ($ret) {
            return Utils::sendResults('lottery config set ok', $ret, SUCCESS);
        }
        return Utils::sendResults('lottery config set false');
    }

    /***
     * 抽奖次数判断
     * @return bool
     */
    public function isLotteresNum()
    {
        $user_lotteres_num = isDatas($this->user_info, 'surplus_lottery_num')
            ?
            (int)isDatas($this->user_info, 'surplus_lottery_num')
            :
            ZERO;
        return ($user_lotteres_num <= ZERO) ? false : true;
    }

    /**
     * 抽奖配置 return
     */
    public function getLotteryConfig()
    {
        $pirze_lottery = null;
        $prize_order = ZERO;
        $prize_arr = ActivitySer::$prize_info;

        if (!isDatas($prize_arr)) {
            return Utils::sendResults('抽奖网络出错!');
        }
        if (!$this->isLotteresNum()) {
            return Utils::sendResults('抽取次数已用完!');
        }
        foreach ($prize_arr as $key => $val) {
            $arr[$val['prize_id']] = $val['rate'];
        }
        // initial default 根据概率获取奖项id
        $rid = Utils::getPrizeRand($arr);

        $pirze_lottery = $prize_arr[$rid];
        $prize_id = self::isParame($pirze_lottery, 'prize_id');
        $pirze_lottery = $this->service->setLotteryFrequency($prize_id);

        if (isDatas($pirze_lottery)) {
            if (is_string($pirze_lottery)) {
                $pirze_lottery = json_decode($pirze_lottery, true);
            }
            $roleUplimit = $this->getRoleUplimit();
            // 如果实物需要用户填写收货地址的,提前保存记录 备份等待用户填写收货地址防止对方篡改
            $pirze_lottery = self::roleLotteryIntervention($prize_arr, $pirze_lottery, $roleUplimit);

            // 也可以作为用户的抽奖记录 再接再厉除外
            if (isDatas($pirze_lottery, 'upper_limit')) {
                $indata = $this->setpirzeConsigneeLog($pirze_lottery);
                $prize_order = isDatas($indata, 'prize_order')
                    ?
                    isDatas($indata, 'prize_order')
                    :
                    ZERO;
            }
            $prize_id = $pirze_lottery['prize_id'];
            $pirze_name = $pirze_lottery['prize'];
            $prize_type = $pirze_lottery['prize_type'];
            $role_lottery_uplimit = $prize_type > ZERO ? ONE : ZERO;
            // 扣取抽奖次数
            $this->lotteryDeduction($role_lottery_uplimit);
            $prize_desc = isDatas($pirze_lottery, 'prize_desc')
                ?
                isDatas($pirze_lottery, 'prize_desc')
                :
                null;

            $datalist = ["list" => [
                "result" => [[
                    "LOGIN_ACCOUNT" => $this->uid,
                    "GIFT_NAME" => $pirze_name,
                    "GIFT_INDEX" => intval($prize_id),
                    "prize_type" => intval($prize_type),
                    "prize_order" => $prize_order,
                    "prize_desc" => $prize_desc
                ]]
            ]];
            return Utils::sendResults("抽奖成功", $datalist, SUCCESS);
        }
        log_message::info("抽奖数据为空!");
        return Utils::sendResults("prize is null ");
    }

    public function getRoleUplimit()
    {

        // 如果玩家抽实物已经三次 则干预
        $role_prize_uplimit = isDatas($this->user_info, 'lottery_up_limit')
            ?
            isDatas($this->user_info, 'lottery_up_limit')
            :
            ZERO;
        return $role_prize_uplimit;
    }

    /**
     * 用户抽奖干预 上限每日三次
     * @param $prizeData
     * @param $lotteresPrize
     * @return mixed
     */
    public static function roleLotteryIntervention($prizeData, $lotteresPrize, $role_prize_uplimit = ZERO)
    {
        // 如果玩家抽实物已经三次 则干预
        if ($role_prize_uplimit >= HUNDRED && $lotteresPrize['prize_type'] == ONE) {
            log_message::info("抽中实物了");
            return $prizeData[SIX];
        }
        return $lotteresPrize;
    }

    /*** ++++++++++++++++++
     * Lottery 初始的时候加载
     * 备份登录 次数 登录天数 连续登录天数
     * @param null $start_at
     * @param null $end_at
     * @param bool $initial default false if true is initial lottery data
     * @return bool|null
     */
    public function replaceLotteryData($start_at = null, $end_at = null, $initial = false)
    {
        $online_num = $this->service->getOnlineNum($this->appid, $this->channel, $this->sid, $this->uid, $start_at, $end_at);
        $pay_num = $this->service->getUserPayInfo($this->appid, $this->channel_id, $this->sid, $this->openid, $start_at, $end_at);
        $data = [];
        $merge_data = null;
        // 登录次数(活动期间)
        $data['login_frequency'] = $online_num['login_frequency'];
        // 连续登录几天(活动期间)
        $data['login_successive_day'] = $online_num['login_successive_day'];
        // 累计登录天数包括中段没有连续登录的    (活动期间)
        $data['login_total_day'] = $online_num['login_day'];
        // 充值次数(活动期间)
        $data['pay_frequency'] = $pay_num['pay_frequency'];
        // 充值金额 (活动期间)
        $data['pay_amount'] = $pay_num['pay_amount'];
        log_message::info("back user data", json_encode($data));
        $merge_data = array_merge($this->user_info, $data);
        // 处理递增次数
        $user_info = $this->service->updateUserlotteryInfo($merge_data);
        if ($user_info) {
            log_message::info('更新抽奖成功');
            $this->user_info = $user_info;
            return $this->user_info;
        }
        log_message::info('更新抽奖失败');
        return false;
    }

    /***
     * 抽奖次数奖励
     */
    public function lucky_draw_total()
    {
        $lucky_draw_total = isDatas($this->user_info, 'lucky_draw_total')
            ?
            isDatas($this->user_info, 'lucky_draw_total')
            :
            ZERO;
        return $lucky_draw_total;
    }

    /**
     *
     */
    public function lucky_draw_total_prize()
    {
        $class_key = isDatas($_GET, 'class_key')
            ?
            isDatas($_GET, 'class_key')
            :
            null;

        $lucky_draw_total = isDatas($this->user_info, 'lucky_draw_total')
            ?
            isDatas($this->user_info, 'lucky_draw_total')
            :
            ZERO;

        // 3次抽取奖励 gift_title
        //{"result":1,"msg":"\u62b1\u6b49\uff0c\u9080\u8bf7\u6b21\u6570\u4e0d\u8db3","val":null,"list":{"gift":{"GIFT_NAME":"超级称号"}}}
        if ($class_key == "gift_title" && ($lucky_draw_total >= THREE && $lucky_draw_total < SIX)) {
            // 3 的时候不初始化 只有等6的时候初始化 0

        }
        // 6次抽取奖励 "gift_year"

        if ($class_key == "gift_year" && $lucky_draw_total >= SIX) {

        }
    }

    /***
     * 抽奖剩余次数扣取 累计抽取次数增加
     * @return bool
     */
    public function lotteryDeduction()
    {
        $user_info = $this->service->updateUserlotteryInfo($this->user_info, true);
        if ($user_info) {
            $this->user_info = $user_info;
            log_message::info("抽取用户抽取次数成功", SUCCESS);
            return true;
        }
        log_message::info("抽取用户抽取次数失败", FAILURE);
        return false;
    }

    /***
     * 添加用户收货地址
     */
    public function setpirzeConsigneeLog($prize)
    {

        $sign = $this->appid . $this->openid . $this->channel . $this->sid . time() . microtime();

        $prize_order = strtoupper(md5(md5($sign)));
        $prize_desc = isDatas($prize, 'prize_desc') ? isDatas($prize, 'prize_desc') : null;
        $prize_type = $prize['status'];
        $data = [
            'appid' => $this->appid,
            'openid' => $this->openid,
            'channel' => $this->channel,
            'sid' => $this->sid,
            'prize_id' => $prize['prize_id'],
            "prize_name" => $prize["prize"],
            'create_at' => $this->time,
            'prize_order' => $prize_order,
            'prize_desc' => $prize_desc,
            'prize_type' => $prize_type,
            'date' => date('Ymd', time()),
        ];
        $ret = $this->service->savePirzeConsignee($data);
        if ($ret) {
            log_message::info("添加奖品信息成功");
            return $data;
        }
        log_message::info("添加奖品信息失败");
        return false;
    }


    /***
     * 更新用户抽奖地址
     * @return bool
     */
    public function updateShippingAddress()
    {
        $address = isDatas($_GET, 'consignee_address');
        //$user_nick_name = isDatas($this->request, 'consignee_name');
        $user_phone = isDatas($_GET, 'consignee_phone');
        $prize_order = isDatas($_GET, 'prize_order');
        $type = isDatas($_GET, 'type'); // 1 - 0

        log_message::info("updateShippingAddress get data ", json_encode($_GET, JSON_UNESCAPED_UNICODE));

        if (isset($_GET['type']) && $type == ZERO) {
            $data = [
                'uid' => $this->uid,
                'openid' => $this->openid,
                'appid' => $this->appid,
                'channel' => $this->channel,
                'sid' => $this->sid,
                'type' => ONE,
                'consignee_address' => $address,
                'consignee_phone' => $user_phone,
                'prize_order' => $prize_order,
            ];
            $ret = $this->service->editPirzeConsignee($data);
            if ($ret) {
                log_message::info("礼物已被更改领取...");
                return Utils::sendResults("add Address", $data, SUCCESS);
            }
        }
        $data = $this->byShippingList($prize_order);

        if (!isset($_GET['type']) && isDatas($data)) {
            return Utils::sendResults('该礼物已经领取', $data, SUCCESS);
        }
        return Utils::sendResults("update Shipping Address false", FAILURE);
    }

    /***
     * 根据订单号 获取db 玩家抽取奖励日志
     * @param $order_id
     * @return array|bool
     */
    public function byShippingList($order_id)
    {
        $data = $this->service->byuserPrizeOrderInfo($this->uid, $order_id);

        if ($data) {
            log_message::info("shpping list is  true ");
            return $data;
        }
        log_message::info("shpping list is null ");
        return false;
    }

    /***
     * 用户抽奖剩余次数
     * @return bool|false|string
     */
    public function getLotterySurplusNum()
    {
        $data = [];

        if ($this->user_info && !empty($this->user_info)) {

            $surplus_lottery_num = isDatas($this->user_info, 'surplus_lottery_num')
                ?
                isDatas($this->user_info, 'surplus_lottery_num')
                :
                ZERO;

            $data = [
                'val' => $surplus_lottery_num,
                'list' => ['chance_times' => $surplus_lottery_num]
            ];
            log_message::info("被调用", $data, SUCCESS);

            return Utils::sendResults("接口调用成功", $data, SUCCESS);
        }
        return Utils::sendResults("初始化失败", $data, FAILURE);
    }

    /***
     * 用户已经抽取次数
     * @return bool|false|string
     */
    public function getUserLotteryTotal()
    {
        //$userInfo = $this->service->saveUserInfo(['openid' => $this->openid]);
        if ($this->user_info && !empty($this->user_info)) {

            $lottery_total = is_array($this->user_info)
                ?
                isDatas($this->user_info, 'lottery_total')
                :
                json_decode($this->user_info, true)['lottery_total'];

            $lottery_total = empty($lottery_total)
                ?
                ZERO
                :
                $lottery_total;

            $data = [
                'result' => SUCCESS,
                'msg' => "接口调用成功",
                'val' => $lottery_total,
            ];

            $data = json_encode($data, JSON_UNESCAPED_UNICODE);
            log_message::info("getUserLotteryTotal data info ", $data);

            return $data;
        }
        log_message::info("user lottery num is null", FAILURE);
        return false;
    }

    /***
     * 干预重置用户日抽奖的上限次数
     * @param $data
     */
    public function respaceRoleUplimitdata()
    {
        $online_at = isDatas($this->user_info, 'login_at');
        //self::$frame_block_at = $online_at;

        if ($online_at && $online_at != date('Ymd', time())) {

            // 重置
            $data = $this->user_info;
            $data['login_at'] = date('Ymd', time());
            $data['lottery_up_limit'] = ZERO;

            $where_data = [
                'appid' => $this->appid,
                'openid' => $this->openid,
                'channel' => $this->channel,
                'sid' => $this->server
            ];
            $db_res = $this->service->updateUserInfo($data, $where_data);
            $cache_res = $this->service->setCacheUserInfo($data);

            if ($db_res && $cache_res) {
                $this->user_info = $data;
                log_message::info('干预重置的玩家上限抽奖次数成功');
                return true;
            }
            log_message::info('干预重置的玩家上限抽奖次数失败');
            return false;
        }
        log_message::info("respaceUserInfo is time is true");

    }

    /**
     * 暂无调用
     * @return false|string
     */
    public function userWinningRecord()
    {

        $data = $this->service->userPrizeInfo($this->appid, $this->openid, $this->channel, $this->sid);
        if (isDatas($data)) {
            if (is_string($data)) {
                $data = json_decode($data, true);
            }
            foreach ($data as $var) {
                $rewards[] = [
                    'ID' => intval($var['prize_id']),
                    'GIFT_NAME' => $var['prize_name'],
                    'TIME' => $var['create_at'],
                    'prize_desc' => $var['prize_desc'],
                    'prize_order' => $var['prize_order'],
                    'prize_type' => intval($var['prize_type']),
                    'consignee_address' => $var['consignee_address'],
                    'consignee_phone' => $var['consignee_phone'],
                    'type' => (int)$var['type'],
                ];
            }
            $user_prize_list = [
                "result" => SUCCESS,
                "msg" => "接口调用成功",
                "val" => null,
                "list" => [
                    "rewards" => $rewards
                ]
            ];
            return json_encode($user_prize_list, JSON_UNESCAPED_UNICODE);
        }
        return Utils::sendResults("暂无奖品可领取");
    }

    /***
     * 无调用
     */
    public function closeUserLotteryEndAt()
    {

    }

    /***
     * 无调用
     */
    public function setdbprize()
    {

        $this->service->getCachePrizeInfo();
    }

    //-----------------------------签到-------------------------------

    /***
     * 无调用 玩家签到
     */
    public function roleSign()
    {

        $this->utils->SetSignDay();

    }

    /**
     * 无调用 签到列表
     * @return false|string
     */
    public function getSignTimeList()
    {
        $timelist = Utils::decodeDate(Utils::$timearray);
        if ($timelist) {
            $timelist = ['list' => $timelist];
            return Utils::sendResults("签到时间列表", $timelist, SUCCESS);
        }
        return Utils::sendResults("签到列表为空");
    }
    //~

}