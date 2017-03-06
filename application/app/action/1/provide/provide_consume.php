<?php
/*
name:本次提供资助消耗的善心币 调用名 provide_consume
desc:本次提供资助消耗的善心币。。。。。。。。。。。。。。。。
config:cid|text|1|社区ID->cid		
*/
if(!in_array($params['cid'],[1,2,3,4,5])){
    $return['errCode'] = 1;
    $return['msg']    = '请选择正确的社区';
    return $return ;
}
if(empty(config('user_id')) || !is_numeric(config('user_id')) || config('user_id')<1){
    $return['errCode'] = 1;
    $return['msg']    = '请先登录！';
    return  $return;
}
$data['user_id'] = config('user_id');
$data['cid']     = $params['cid'];
$return = _service('provideconsume', $data);
return $return;