<?php
/*
name:取消提供资助 调用名 provide_cancel
desc:取消提供资助。。。。。。。。。。。。。。。。
config:id|text|1|提供资助的ID->id  sed_pwd|text||二级密码->sed_pwd	create_time|text||订单创建时间->create_time	
*/
if(empty(config('user_id')) || !is_numeric(config('user_id')) || config('user_id')<1){
    $return['errCode'] = 1;
    $return['msg']    = '请先登录！';
    return  $return ;
}
if(empty($params['sed_pwd'])){
    $return['errCode'] = 1;
    $return['msg']    = '请输入二级密码！';
    return  $return ;
}
if(!is_numeric($params['id']) || $params['id']<1){
    $return['errCode'] = 1;
    $return['msg']    = '请选择正确的记录';
    return  $return ;
}
$rule = '/^\d{10}$/';

if(!preg_match($rule, $params['create_time']) || $params['create_time']>time() || $params['create_time'] < strtotime("2016-01-01") ){
    $return['errCode'] = 1;
    $return['msg']    = '请选择正确的时间';
    return  $return ;
}
$arr['id']          = $params['id'];
$arr['user_id']     = config('user_id');
//$arr['sed_pwd']     = $params['sed_pwd'];
$arr['sed_pwd']     = rsa_decode($params['sed_pwd']);
$arr['create_time'] = $params['create_time'];
$return=_service("providecancel",$arr);
return  $return;