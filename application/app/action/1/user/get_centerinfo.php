<?php
/*
name:个人中心信息页面刷新 调用名 user.get_centerinfo
desc:个人中心信息页面刷新
config:
*/
$params['user_id'] = config('user_id');
$return = _service('get_centerinfo',$params);
return  $return ;


