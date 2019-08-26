## 一 、连接信息

### socket接口信息
    ip : 192.168.6.140
    port : 9601
    
    当前游戏限制时间为 10 秒，用户需在10秒内完成操作

### socket连接必传参数
    game_code 游戏名称(英文名称 例:jump 不可改动)
    host 主机域名
    code 游戏产品渠道
    username 用户名
    room_id 房间id
    sid 用户sid
    avatar_url  用户头像url
    site    麦位id
    user_role 用户角色
        ==>
        0 无角色 不在房间
        5 房主 在自己房间
        10 管理员
        15 主播 在他人房间上麦
        20 旁听 在他人房间旁听
        <==
    game_history_id 游戏历史记录id
    
### 数据请求或响应格式

    发送格式
    {
        'action' : 'action_name',
        'data' : {}
    }
    
    

## 二 、游戏客户端请求事件

#### 连接默认触发事件
    {
        action : enter_room,
        data : {}
    }

#### 离开房间事件（断开链接默认触发事件）
    {
        action : leave_room,
        data : {}
    }
    
    
#### 开始游戏
    {
    	"action": "start",
    	"data": {
    		"boxList": [{
    			"skinId": 11,
    			"x": 200,
    			"y": 900
    		}, {
    			"skinId": 12,
    			"x": 553,
    			"y": 696.1953549760622
    		}],
    		"moveViewPos": {
    			"x": 0,
    			"y": 0
    		},
    		"currentBox": 0,
    		"touchStart": false,
    		"touchEnd": false,
    		"jumpNum": 0,
    		"lastBoxIsLeft": false,
    		"isAddBox": false,
    		"countNum": 0,
    		"hasLoser": false
    	}
    }
    
    
#### 更新分数
    {
        action : update_score,
        data : {
            score : 23
        }
    }
    
#### 再来一次
    {
        action : restart,
        data : {}
    }
    
    
#### 房主主动关闭房间
    {
        action : close_room,
        data : {}
    }
    
    
#### 上麦
    {
        action : up_site,
        data : {
            'user_id':
            'site':
        }
    }
    
#### 下麦(包含踢人)
    {
        action : down_site,
        data : {
            'user_id':
        }
    }
    
#### 双人游戏设置押金
    {
        action : set_deposit,
        data : {
            'deposit': 押金(金币)
        }
    }
    
#### 切换模式
    {
        action : switch_mode,
        data : {
            'mode': 0 默认  1 单人   2 多人
        }
    }
    
#### 游戏历史记录
    {
        action : history,
        data : {}
    }
    
#### 游戏失败主动请求
    {
        action : fail,
        data : {}
    }
    
    
#### 控制请求
    {
    	"action": "control",
    	"data": {
    		"boxList": [null, null, null, null, null, null, null, null, {
    			"skinId": 7,
    			"x": 500,
    			"y": -274.33044753169867
    		}, {
    			"skinId": 4,
    			"x": 748,
    			"y": -417.5133142907258
    		}, {
    			"skinId": 8,
    			"x": 1083,
    			"y": -610.9256544692505
    		}, {
    			"skinId": 6,
    			"x": 740,
    			"y": -808.9567968012922
    		}],
    		"moveViewPos": {
    			"x": -513,
    			"y": 1210
    		},
    		"currentBox": 10,
    		"touchStart": true,
    		"touchEnd": false,
    		"jumpNum": 0,
    		"lastBoxIsLeft": true,
    		"isAddBox": false,
    		"countNum": 14,
    		"hasLoser": false
    	}
    }
    
#### 游戏用户支持
    {
        action : support,
        data : [
        {
            'user_id':'',
            'coin_num':''
        }]
    }
    
    
## 三 、 服务端响应事件

#### 游戏历史记录
    {
            action : history,
            data : [
                {
                    'id' : 
                    'game_code' 
                    'mode' ,
                    'deposit',
                    'winner_detail' : {
                        'user_id' ,
                        'avatar_url' ,
                        'username' ,
                        'sid' ,
                        'created_at' 
                    },
                    'owner_detail' :{
                        'user_id' ,
                        'avatar_url' ,
                        'username' ,
                        'sid' ,
                    },
                    'record_list' : [
                        {
                            'avatar_url',
                            'user_id' ,
                            'username',
                            'loop_num',
                            'mode',
                            'created_at'
                        }
                    ],
                    'support_list' : [
                        {
                            'id' => $this->id,
                            'user_detail' => {
                                'user_id' ,
                                'avatar_url' ,
                                'username' ,
                                'sid' ,
                                'created_at' 
                            },
                            'support_user_detail' => {
                                'user_id' ,
                                'avatar_url' ,
                                'username' ,
                                'sid' ,
                                'created_at' 
                            },
                            'coin_num',
                            'created_at'
                        }
                    ],
                    'created_at',
                    'updated_at',
                }
            ]
        }
        
    }

    
#### 等待游戏开始
    {
        "action": "waiting",
        "data": {
            "room_id": "12345",
            "status": "wait",
            "online_num": "1",
            "owner_id": "23",
            "reset": "false",
            "users": [
                {
                    "user_id": "23",
                    "room_id": "12345",
                    "user_fd": "1",
                    "score": "0",
                    "username": "dingyaoli",
                    "avatar_url": "avater.png",
                    "created_at": "1526431097",
                    "updated_at": "1526431097"
                }
            ]
        }
    }

#### 再来一局
     {
     	"action": "restart",
     	"data": {
     		"room_id": "1234545",
     		"status": "wait",
     		"loop_num": "0",
     		"timestamp": "1526869334",
     		"owner_id": "12312",
     		"next_user_id": "12312",
     		"reset": "true",
     		"online_num": 2,
     		"users": [{
     			"loop_num": "1",
     			"room_id": "1234545",
     			"user_id": "12312",
     			"avatar_url": "http:\/\/cdnqq1.pxqb.leshu.com\/pxqb\/h5171226\/res\/resource\/ui\/Common\/activity_icon\/activity_icon_chuangjuedhuodong.png",
     			"created_at": "1526868163",
     			"username": "userName1526868161512",
     			"score": "4",
     			"updated_at": "1526869334",
     			"user_fd": "199",
     			"status": "wait"
     		}, {
     			"loop_num": "1",
     			"room_id": "1234545",
     			"user_id": "123125",
     			"avatar_url": "http:\/\/cdnqq1.pxqb.leshu.com\/pxqb\/h5171226\/res\/resource\/ui\/Common\/activity_icon\/activity_icon_chuangjuedhuodong.png",
     			"created_at": "1526868178",
     			"username": "userName1526868176407",
     			"score": "2",
     			"updated_at": "1526869334",
     			"user_fd": "201",
     			"status": "wait"
     		}]
     	}
     }
    
#### 开始游戏
    {
    	"action": "start",
    	"data": {
    		"control_data": {
    			"boxList": [{
    				"skinId": 4,
    				"x": 200,
    				"y": 900
    			}, {
    				"skinId": 5,
    				"x": 479,
    				"y": 738.9192748960944
    			}],
    			"moveViewPos": {
    				"x": 0,
    				"y": 0
    			},
    			"currentBox": 0,
    			"touchStart": false,
    			"touchEnd": false,
    			"jumpNum": 0,
    			"lastBoxIsLeft": false,
    			"isAddBox": false,
    			"countNum": 0,
    			"hasLoser": false
    		},
    		"next_user": {
    			"loop_num": "1",
    			"room_id": "1234545",
    			"user_id": "12312",
    			"avatar_url": "http:\/\/cdnqq1.pxqb.leshu.com\/pxqb\/h5171226\/res\/resource\/ui\/Common\/activity_icon\/activity_icon_chuangjuedhuodong.png",
    			"created_at": "1526868163",
    			"username": "userName1526868161512",
    			"score": "4",
    			"updated_at": "1526869334",
    			"user_fd": "199",
    			"status": "start"
    		}
    	}
    }
    
#### 开始游戏，服务器告知当前开始游戏的用户信息
    {
    	"action": "next",
    	"data": {
    		"next_user": {
    			"loop_num": "1",
    			"room_id": "1234545",
    			"user_id": "123125",
    			"avatar_url": "http:\/\/cdnqq1.pxqb.leshu.com\/pxqb\/h5171226\/res\/resource\/ui\/Common\/activity_icon\/activity_icon_chuangjuedhuodong.png",
    			"created_at": "1526868178",
    			"username": "userName1526868176407",
    			"score": "14",
    			"updated_at": "1526869155",
    			"user_fd": "201",
    			"status": "start"
    		},
    		"users": [{
    			"loop_num": "1",
    			"room_id": "1234545",
    			"user_id": "12312",
    			"avatar_url": "http:\/\/cdnqq1.pxqb.leshu.com\/pxqb\/h5171226\/res\/resource\/ui\/Common\/activity_icon\/activity_icon_chuangjuedhuodong.png",
    			"created_at": "1526868163",
    			"username": "userName1526868161512",
    			"score": "15",
    			"updated_at": "1526869157",
    			"user_fd": "199",
    			"status": "start"
    		}, {
    			"loop_num": "1",
    			"room_id": "1234545",
    			"user_id": "123125",
    			"avatar_url": "http:\/\/cdnqq1.pxqb.leshu.com\/pxqb\/h5171226\/res\/resource\/ui\/Common\/activity_icon\/activity_icon_chuangjuedhuodong.png",
    			"created_at": "1526868178",
    			"username": "userName1526868176407",
    			"score": "14",
    			"updated_at": "1526869155",
    			"user_fd": "201",
    			"status": "start"
    		}],
    		"loser": ""
    	}
    }
    
#### 游戏结束
    {{
     	"action": "end",
     	"data": {
     		"users": [{
     			"loop_num": "8",
     			"room_id": "1234545",
     			"user_id": "12312",
     			"avatar_url": "http:\/\/cdnqq1.pxqb.leshu.com\/pxqb\/h5171226\/res\/resource\/ui\/Common\/activity_icon\/activity_icon_chuangjuedhuodong.png",
     			"created_at": "1526717032",
     			"username": "userName1526717031195",
     			"score": "10",
     			"updated_at": "1526717054",
     			"user_fd": "63",
     			"status": "fail"
     		}, {
     			"loop_num": "2",
     			"room_id": "1234545",
     			"user_id": "12314",
     			"avatar_url": "http:\/\/cdnqq1.pxqb.leshu.com\/pxqb\/h5171226\/res\/resource\/ui\/Common\/activity_icon\/activity_icon_chuangjuedhuodong.png",
     			"created_at": "1526717033",
     			"username": "userName1526717032022",
     			"score": "3",
     			"updated_at": "1526717039",
     			"user_fd": "64",
     			"status": "fail"
     		}],
     		"loser": {
     			"loop_num": "8",
     			"room_id": "1234545",
     			"user_id": "12312",
     			"avatar_url": "http:\/\/cdnqq1.pxqb.leshu.com\/pxqb\/h5171226\/res\/resource\/ui\/Common\/activity_icon\/activity_icon_chuangjuedhuodong.png",
     			"created_at": "1526717032",
     			"username": "userName1526717031195",
     			"score": "10",
     			"updated_at": "1526717054",
     			"user_fd": "63",
     			"status": "fail"
     		}
     	}
     }
    
#### 房主关闭房间
    {
        action : close_room,
        data : {}
    }
    
#### 房间押注信息
    {
        "action": "support", 
        "data": {
            "support_list": [
                {
                    "user": {
                        "updated_at": "1528191693", 
                        "game_user_id": "3", 
                        "user_fd": "3", 
                        "loop_num": "0", 
                        "sid": "6545433scd6a9c8dba16750c39244824b28053afa8", 
                        "username": "天呐", 
                        "avatar_url": "https://img.momoyuedu.cn/chance/users/avatar/20180428095ae3d3dc8a0b3.jpg", 
                        "score": "0", 
                        "site": "0", 
                        "owner": "1", 
                        "user_id": "6545433", 
                        "room_id": "556857"
                    }, 
                    "support_user": {
                        "updated_at": "1528191693", 
                        "game_user_id": "2", 
                        "user_fd": "2", 
                        "loop_num": "0", 
                        "sid": "434343scd6a9c8dba16750c39244824b28053afa8", 
                        "username": "天呐", 
                        "avatar_url": "https://img.momoyuedu.cn/chance/users/avatar/20180428095ae3d3dc8a0b3.jpg", 
                        "score": "0", 
                        "site": "2", 
                        "owner": "1", 
                        "user_id": "434343", 
                        "room_id": "556857"
                    }, 
                    "coin_num": 23, 
                    "created_at": 1528192047
                }
            ]
        }
    }
    
#### 错误回复
    {
        action : error,
        data : {
            'reason' : ''
        }
    }
