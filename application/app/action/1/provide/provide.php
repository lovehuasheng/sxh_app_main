<?php
/*
name:提供资助 调用名 provide
desc:提供资助。。。。。。。。。。。。。。。。
config:cid|text|2|社区ID->cid  money|text|1000|挂单金额->money	sed_pwd|text||二级密码->sed_pwd		
*/
if(!is_numeric($params['cid']) || !in_array($params['cid'],[1,2,3,4,5])){
    $return['errCode'] = 1;
    $return['msg']    = '请选择正确的社区！';
    return  $return;
}
if(empty(config('user_id')) || !is_numeric(config('user_id')) || config('user_id')<1){
    $return['errCode'] = 1;
    $return['msg']    = '请先登录！';
    return  $return;
}
if(!is_numeric($params['money'])&&$params['money']<1000){
    $return['errCode'] = 1;
    $return['msg']    = '金额输入错误，只能为大于等于1000数字！';
    return  $return;
}
if($params['money']%100>0){
    $return['errCode'] = 1;
    $return['msg']    = '提供资助金额必须必须是规定金额的倍数';
    return  $return;
}
$data['ip']       = ip2long(ip());
$data['user_id']  = config('user_id');
$data['money']    = $params['money'];
$data['cid']      = $params['cid'];
$data['sed_pwd']  = $params['sed_pwd'];
//$data['sed_pwd']  = rsa_decode($params['sed_pwd']);
$return = _service('provide',$data);
return  $return;