<?php
/*
name:注册前用户名的唯一性验证接口 调用名 user.check_second_password
desc:注册前用户名的唯一性验证接口
config:username|text||用户名
*/
if(!preg_match('/^[0-9a-zA-Z]{6,16}$/', $params['username'])){
    return returnAction(1,'用户名须为6-16位字母或数字!');
}
$return = _service('check_username',$params);
return  $return ;

