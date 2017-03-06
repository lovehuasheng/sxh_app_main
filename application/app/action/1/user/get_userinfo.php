<?php
/*
name:查询完善资料接口 调用名 user.mod_user_password
desc:查询完善资料接口
config:
*/
$params['user_id'] = config('user_id');
$return = _service('get_userinfo',$params);
return  $return ;

