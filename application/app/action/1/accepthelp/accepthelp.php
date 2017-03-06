<?php
/*
name:接受资助 调用名 accepthelp
desc:接受资助。。。。。。。。。。。。。。。。
config:money|text|100|接受资助的金额->money	sed_pwd|text||二级密码->sed_pwd		
*/
$return = [];
if(empty(config('user_id')) || config('user_id')<1 || !is_numeric(config('user_id'))){
   $return['errCode'] = 1;
   $return['msg']    = '请先登录';
   return  $return ;
}
if(!is_numeric($params['cid']) || !in_array($params['cid'],[1,2,3,4,5])){
    $return['errCode'] = 1;
    $return['msg']    = '请选择正确的社区！';
    return  $return;
}
if(intval($params['money'])<500){
    $return['errCode'] = 0;
    $return['msg']  = '接受资助金额必须大于500';
    return $return;
}
if(intval($params['money'])%100){
    $return['errCode'] = 0;
    $return['msg']  = '接受资助金额必须必须是规定金额的倍数';
    return $return;
}
if(empty($params['sed_pwd'])){
   $return['errCode'] = 1;
   $return['msg']    = '密码不能为空';
   return $return ;
}
$data['ip']       = ip2long(ip());
$data['cid']      = $params['cid'];
$data['money']    = $params['money'];
//$arr['sed_pwd']  = $params['sed_pwd'];
$arr['sed_pwd']     = rsa_decode($params['sed_pwd']);
$data['user_id']  = config('user_id');
$return = _service('accepthelp',$data);
return  $return ;
