<?php
/*
name:确认收款 调用名 confirm_money
desc:确认收款。。。。。。。。。。。。。。。。
config:match_id|text|1|匹配ID->match_id sed_pwd|text|1|二级密码->sed_pwd create_time|text||匹配记录的创建时间->create_time
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
}$rule = '/^\d{10}$/';
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
$return=_service("confirmmoney",$arr);
return  $return;