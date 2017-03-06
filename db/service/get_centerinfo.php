<?php
require_once WANG_MS_PATH."model/userinfo.php";
$get_centerinfo = function($data){
    $redis = RedisLib::get_instance();
    $model = new userinfo();
    $userinfo = $model->getUserInfo($data['user_id'],array('user_id'=>$data['user_id']),'verify,name,avatar,grade,referee,referee_id,referee_name,tel_number,phone','sxh_user_info');
    if(!$userinfo[0]){
        return errReturn('请求信息错误',-22);
    }
    $arr = array('未审核','未通过','已通过');
    $userinfo[0]['verify'] = $arr[$userinfo[0]['verify']];
    $userinfo[0]['avatar'] = $userinfo[0]['avatar'] ? getQiNiuPic($userinfo[0]['avatar']) : '';
    $userinfo[0]['phone'] = $userinfo[0]['phone'] ? $userinfo[0]['phone'] : '';
    //如果推荐人信息为空则处理为空
    if($userinfo[0]['referee_id']){
        $phone = $redis->hgetUserinfoByID($userinfo[0]['referee_id'],'phone');
        $userinfo[0]['referee_phone'] = $phone ? $phone : '';
    }else{
        $userinfo[0]['referee_phone'] = '';
    }
    $userinfo[0]['username'] = $redis->getUsernameByID($data['user_id']);
    $userinfo[0]['enroll_url'] = config('ENROLL_CODE').'/User/Enroll/outenrolllink/UserName/'.$userinfo[0]['username'].'.html';
    
//    $user = $model->getUserInfo($data['user_id'],array('id'=>$data['user_id']),'user_token','sxh_user');
//    if(empty($user[0]['user_token'])) {
//         //调用业务逻辑
//        $userinfo[0]['user_token'] = get_user_token($userinfo[0]['user_id'],$userinfo[0]['name'],$userinfo[0]['avatar']);
//    }else{
//        $userinfo[0]['user_token'] = $user[0]['user_token'];
//    }
    if(empty($userinfo[0]['tel_number'])){
        $userinfo[0]['tel_number'] = '189'.str_pad($userinfo[0]['user_id'], 9,'8');
    }
    unset($userinfo[0]['referee_id']);
    return returnErr(0,'请求成功',$userinfo[0]);
};
return $get_centerinfo($arr);

