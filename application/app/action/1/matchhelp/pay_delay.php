<?php
/*
name:延时打款 调用名 pay_delay
desc:用户申请延时打款。。。。。。。。。。。。。。。。
config:match_id|text|1|匹配ID->match_id sed_pwd|text|123456a|二级密码->sed_pwd delay_time|text||延时的小时个数->delay_time create_time|text||匹配ID创建时间->create_time
*/
if(empty(config('user_id')) || !is_numeric(config('user_id')) || config('user_id')<1){
    $return['errCode'] = 1;
    $return['msg']    = '请先登录！';
    return  $return;
}
if(empty($params['sed_pwd'])){
    $return['errCode'] = 1;
    $return['msg']    = '二级密码不能为空！';
    return  $return;
}
if(!is_numeric($params['match_id']) || $params['match_id']<1){
    $return['errCode'] = 1;
    $return['msg']    = '请选择正确的记录';
    return  $return ;
}
if(!is_numeric($params['delay_time']) || $params['delay_time']<1 || $params['delay_time']>24){
    $return['errCode'] = 1;
    $return['msg']    = '请选择正确的延时时间';
    return  $return ;
}
$rule = '/^\d{10}$/';
if(!preg_match($rule, $params['create_time']) || $params['create_time']>time() || $params['create_time'] < strtotime("2016-01-01") ){
    $return['errCode'] = 1;
    $return['msg']    = '请选择正确的时间';
    return  $return ;
}
$arr['match_id']    = $params['match_id'];
$arr['user_id']     = config('user_id');
//$arr['sed_pwd']  = $params['sed_pwd'];
$arr['sed_pwd']     = rsa_decode($params['sed_pwd']);
$arr['create_time'] = $params['create_time'];
$arr['delay_time']  = $params['delay_time'];
$return=_service("paydelay",$arr);
return  $return;