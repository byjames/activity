# 游戏记录表设计

# 游戏用户
game_users
    id
    sid
    user_id
    avatar_url
    username
    created_at
    updated_at

restart的时候重新生成一条记录
# 游戏房间记录
game_room_histories
	id
	game_code	//索引
	game_history_id //索引
	created_at
	updated_at
	
	game_mode
	# 房主id
	owner_id
	# 房间押金
	deposit
	# 获胜者id
	winner_id

# 游戏押注记录
game_user_supports
	id
	game_room_history_id
	game_user_id
	support_game_user_id
	coin_num
	created_at

# 游戏记录表
game_records
	id
	game_room_history_id
	game_user_id
	loop_num
	created_at