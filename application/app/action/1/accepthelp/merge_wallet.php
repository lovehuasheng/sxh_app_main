<?php
/*
name:合并钱包 调用名 merge_wallet
desc:合并钱包。。。。。。。。。。。。。。。。
config:sed_pwd|text||二级密码->sed_pwd	
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
$arr['user_id']     = config('user_id');
//$arr['sed_pwd']  = $params['sed_pwd'];
$arr['sed_pwd']     = rsa_decode($params['sed_pwd']);
$return=_service("mergewallet",$arr);
return  $return;