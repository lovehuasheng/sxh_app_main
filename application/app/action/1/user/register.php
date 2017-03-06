<?php
/*
name:无身份用户注册接口 调用名 user.register
desc:无身份用户注册接口
config:username|text||登录名  password|text||密码  confirm_password|text||确认密码  name|text||身份姓名  phone|text||电话号  verify|text||验证码  referee_name|text||推荐人
*/

$return = [];
$params['username'] = strtolower($params['username']);
$params['referee_name'] = strtolower($params['referee_name']);
if(!preg_match('/^[0-9a-zA-Z]{6,16}$/', $params['username'])){
   return returnAction(1,'用户名格式不正确','');
}
if(!preg_match('/^(?!^\d+$)(?!^[a-zA-Z]+$)[0-9a-zA-Z]{6,16}$/', $params['password'])){
   return returnAction(1,'密码格式不正确','');
}
if($params['confirm_password'] != $params['password']){
   return returnAction(1,'二次密码输入不一致','');
}
if(!preg_match('/^1[34578]\d{9}$/', $params['phone'])){
   return returnAction(1,'手机格式不正确','');
}
if(!preg_match('/^[^&^=^%^$^@^\)^\)^\~^\+^\[^\]^\}^\{^\<^\>^\*^\d]{2,80}$/i', $params['name'])){
   return returnAction(1,'用户姓名格式不正确','');
}
if(empty($params['referee_name'])){
   return returnAction(1,'推荐人账号不能为空','');
}
$params['ip'] = ip2long(ip()); 
$params['password'] = rsa_decode($params['password']);
$params['confirm_password'] = rsa_decode($params['confirm_password']);
$return = _service('register',$params);
return  $return ;

