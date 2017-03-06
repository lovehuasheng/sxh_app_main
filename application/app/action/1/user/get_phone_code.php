<?php
/*
name:获取手机验证码接口 调用名 user.get_phone_code
desc:获取手机验证码接口 type 验证码类型 1注册验证码 2找回密码验证码 3查看收款人信息验证码 4取消挂单验证码 5异常登录手机验证码获取
config:type|text||验证码类型  username|text||用户名  phone|text||手机号
*/
if(empty($params['type']) || !in_array($params['type'], array(1,2,3,4,5))){
    return returnAction(1,'验证短信类型不在范围内');
}
if($params['type'] == 1 && !preg_match('/^1[34578]\d{9}$/', $params['phone'])){
    return returnAction(1,'手机号码格式有误');
}
if(in_array($params['type'], array(2,5)) && empty($params['username'])){
    return returnAction(1,'用户名不能为空');
}
$params['ip'] = ip2long(ip()); 
$return = _service('get_phone_code',$params);
return  $return ;


