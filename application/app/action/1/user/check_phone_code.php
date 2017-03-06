<?php
/*
name:手机验证码验证接口 调用名 user.check_phone_code
desc:手机验证码验证接口
config:verify|text||验证码
*/
$params['user_id'] = config('user_id');
$return = _service('check_phone_code',$params);
return  $return ;

