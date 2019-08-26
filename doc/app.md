### APP对接文档

> http-get 

###### 1.客户端传入参数

|参数|名称|值类型|是否可空|备注
|---|---|---|---|---|
|name|游戏名称|string|
|username|用户昵称|string|
|room_id|房间ID|int|
|user_id|用户ID|int|
|avater_url|用户头像链接|string|
|user_num_limit|房间人数限制|int|
|site|麦位ID|int|
|owner|是否房主|int||房主[owner=0], 其余非0|


###### 2. 链接参数示例 

```
?name=jump&username=ding&room_id=5435435&user_id=43244&avater_url=avater.png&user_num_limit=4&time=4324&site=34&owner=0
```