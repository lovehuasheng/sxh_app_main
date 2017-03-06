<?php
/*
name:忘记密码接口 调用名 user.mod_user_password
desc:忘记密码接口
config:username|text||用户名  password|text||新密码  confirm_password|text||确认密码  verify|text||验证码
*/
//对进行rsa加密过的密码进行解密
$params['password'] = rsa_decode($params['password']);
$params['confirm_password'] = rsa_decode($params['confirm_password']);
if(empty($params['username'])){
    return returnAction(1,'用户名不能为空！');
}
if(!preg_match('/^(?!^\d+$)(?!^[a-zA-Z]+$)[0-9a-zA-Z]{6,16}$/', $params['password'])){
    return returnAction(1,'密码格式不正确！');
}
if($params['password']!=$params['confirm_password']){
    return returnAction(1,'二次输入的密码不一致！');
}
$return = _service('mod_user_password',$params);
return  $return ;

