<?php
/*
name:提取管理奖 调用名 out_manage_account
desc:提取管理奖。。。。。。。。。。。。。。。。
config:money_sum|int||提取金额  password|string||二级密码  check_token|string||令牌
*/
//if(!preg_match('/^[0-9a-zA-Z]{6,16}$/', $params['username'])){
//    return returnAction(1,'用户名须为6-16位字母或数字!');
//}
//验证参数ID
$params['user_id'] = intval(config('user_id'));
if($params['user_id'] == 0) {
    return returnAction(1,'参数错误');
}
if($params['money_sum']<500 || $params['money_sum']%100 != 0){
    return returnAction(1,'提取管理金额必须大于500且是100的倍数');
}
//token验证
if(empty($params['check_token'])){
    return returnAction(1,'令牌不能为空');
}
//对进行rsa加密过的密码进行解密
$params['password'] = rsa_decode($params['password']);
$params['ip'] = ip2long(ip()); 
$return = _service('out_manage_account',$params);
return  $return ;