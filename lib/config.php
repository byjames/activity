<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/26 0026
 * Time: 下午 12:46
 */
/*'host' => 'rm-uf68i53m6uaapa9t0.mysql.rds.aliyuncs.com',
'user' => 'unicorndb',
'port' => 3306,
'password' => 'HHgankStudio@it520',
'dbname' => 'unicorn_log',
'dbcharset' => 'utf8'*/
$daytimes = date(DATE_FORMAT_S,time());
return $config = [
    'rank_api' => [
        'host' => 'http://event.djsh5.com/rank/?',
        'game_cache' => [
            'host' => '192.168.0.140',
            'port' => 6379,
            'auth' => '123456'
        ]
    ],
    'game_db' => [
        'host' => '192.168.0.140',
        'user' => 'liumj',
        'port' => 3306,
        'password' => 'lmj3503791673',
        'dbname' => 'gank_activites',
        'dbcharset' => 'utf8'
    ],
    'game_stat_db'=>[
        'host' => '192.168.0.140',
        'user' => 'root',
        'port' => 3306,
        'pass' => 'lmj3503791673',
        'dbname' => 'test',
        'dbcharset' => 'utf8'
    ],
    'game_prize' => [
        '1' => array('prize_id' => 1, 'prize' => '5元京东卡', 'rate' => 200, 'number' => 1, 'status' => 0, 'upper_limit' => 165,'frequency'=>0,'prize_type'=>0,'create_at'=>$daytimes),
        '2' => array('prize_id' => 2, 'prize' => '爱奇艺月卡', 'rate' => 50, 'number' => 1, 'status' => 0, 'upper_limit' => 50,'frequency'=>0,'prize_type'=>0,'create_at'=>$daytimes),
        '3' => array('prize_id' => 3, 'prize' => '独角兽充电宝', 'rate' => 1, 'number' => 1, 'status' => 1, 'upper_limit' => 10,'frequency'=>0,'prize_type'=>0,'create_at'=>$daytimes),
        '4' => array('prize_id' => 4, 'prize' => '10元京东卡', 'rate' => 100, 'number' => 1, 'status' => 0, 'upper_limit' => 100,'frequency'=>0,'prize_type'=>0,'create_at'=>$daytimes),
        '5' => array('prize_id' => 5, 'prize' => '独角兽挂饰', 'rate' => 2, 'number' => 1, 'status' => 1, 'upper_limit' => 15,'frequency'=>0,'prize_type'=>0,'create_at'=>$daytimes),
        '6' => array('prize_id' => 6, 'prize' => '谢谢参与', 'rate' => 1000, 'status' => 0, 'number' => 0, 'upper_limit' => 0,'frequency'=>0,'prize_type'=>0,'create_at'=>$daytimes),
    ]

];
