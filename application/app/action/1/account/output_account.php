<?php
/*
name:转出善种子、善心币 调用名 output_account
desc:转出类型 1表示善种子，2表示善心币。。。。。。。。。。。。。。。。
config:recipient_account|string||接收人账号  money_type|int||转出类型   money_sum|int||转出数量  password|string||二级密码  notes|string||转出类型备注  check_token|string||令牌
*/
//if(!preg_match('/^[0-9a-zA-Z]{6,16}$/', $params['username'])){
//    return returnAction(1,'用户名须为6-16位字母或数字!');
//}
//验证参数ID
$params['user_id'] = intval(config('user_id'));
if(!isset($params['user_id']) ||  $params['user_id'] == 0) {
    return returnAction(1,'参数错误');
}
//token验证
if(empty($params['check_token'])){
    return returnAction(1,'令牌不能为空');
}
if(!preg_match('/^[\x{4e00}-\x{9fa5}\w-\.]{1,30}$/u', trim($params['notes']))){
    return returnAction(1,'备注信息只能包含1-30位中文、数字、字母、“-”、“.”');
}
if(!in_array($params['money_type'], array(1,2))){
    return returnAction(1,'转出类型不正确');
}
$params['recipient_account'] = strtolower($params['recipient_account']);
if(empty($params['recipient_account'])|| intval($params['money_sum'])<1){
    return returnAction(1,'参数不完整');
}
//对进行rsa加密过的密码进行解密
$params['password'] = rsa_decode($params['password']);
$params['ip'] = ip2long(ip()); 
$return = _service('output_account',$params);
return  $return ;