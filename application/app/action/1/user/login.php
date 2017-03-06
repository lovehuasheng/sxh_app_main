<?php
/*
name:无身份用户登录接口 调用名 user.login
desc:描述app登录需提供phone_code设备唯一编号，当返回值user_login_need_code 等于一的时候需提交验证码verify。。。。。。。。。。。。。。。。
config:username|text||登录名->username  password|text||密码->password 
*/
$return = [];
if(!empty(config('user_id')) || config('user_id')>0){
   $return['errCode'] = 1;
   $return['msg']    = '请勿重复登录';
   return  $return ;
}
if(empty($params['username'])){
   $return['errCode'] = 1;
   $return['msg']    = '用户名不能为空';
   return  $return ;
}
if(empty($params['password'])){
   $return['errCode'] = 1;
   $return['msg']    = '密码不能为空';
   return $return ;
}
$data['ip']         = ip2long(ip());
$data['phone_code'] = $params['b'];/*手机端登录时带有的设备唯一ID*/
$data['login_type'] = 1;/*用户登录方式*/
$data['username']   = $params['username'];
$data['password']   =  $params['password'];
//$data['password']   = rsa_decode($params['password']);
if(isset($params['verify'])){
    $data['verify']   = $params['verify'];
}
$return = _service('login',$data);
if($return['errCode'] == 0){
    config("user_id",$return['data']['user_id']); /*设置登陆成功的用户ID*/
}
return  $return ;