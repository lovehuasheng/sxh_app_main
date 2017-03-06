<?php
/*
name:二级密码验证接口 调用名 user.check_second_password
desc:二级密码验证接口
config:password|text||二级密码
*/
$params['user_id'] = config('user_id');
//对进行rsa加密过的密码进行解密
$params['password'] = rsa_decode($params['password']);
$return = _service('check_second_password',$params);

return  $return ;

